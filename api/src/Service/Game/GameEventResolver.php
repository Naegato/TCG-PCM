<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Enum\GameEventTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\MonsterCardState;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\Exception\CardCannotAttackExpcetion;
use App\Game\Exception\NotEnoughCoinsException;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Service\Game\Factory\GameContextFactoryInterface;

class GameEventResolver
{
    /**
     * @var GameEvent[]
     */
    private array $eventQueue = [];

    /**
     * @var GameEvent[]
     */
    private array $resolvedEvents = [];

    private array $pendingDeath = [];

    public function __construct(
        private CardRuntimeMap $cardRuntimeMap,
        private GameContextFactoryInterface $gameContextFactory,
        private GameEventApplierInterface $gameEventApplier,
    ) {}

    public function setGameContextFactory(GameContextFactoryInterface $factory): GameContextFactoryInterface
    {
        $previousFactory = $this->gameContextFactory;

        $this->gameContextFactory = $factory;

        return $previousFactory;
    }

    public function resolve(GameEvent $mainEvent, GameState $state): ResolutionResult
    {
        $this->pushEventsToQueue([$mainEvent]);

        try {
            while ($event = array_shift($this->eventQueue)) {
                $state = $this->doResolveEvent($event, $state);
            }

            return new ResolutionResult($this->resolvedEvents, $state);
        } finally {
            $this->eventQueue = [];
            $this->resolvedEvents = [];
        }
    }

    private function doResolveEvent(GameEvent $event, GameState $state): GameState
    {
        // First we apply the event
        $state = $this->gameEventApplier->apply($event, $state);

        // Then we can generate logic reactions
        $events = $this->generateReactions($event, $state);
        $this->pushEventsToQueue($events);

        // Then we process aware cards
        $events = $this->collectEventsFromAwareCards($event, $state);
        $this->pushEventsToQueue($events);

        // Finally we generate system events that should be checked after each event resolution, such as player death, monster death, etc.
        $events = $this->generateSystemsEvent($state, $event);
        $this->pushEventsToQueue($events);

        $this->resolvedEvents[] = $event;

        // clean pendingDeath
        if (\in_array(
            $event->type,
            [
                GameEventTypeEnum::MONSTER_DIED,
                GameEventTypeEnum::PLAYER_DIED,
            ],
            true,
        )) {
            $idToRemove = GameEventTypeEnum::MONSTER_DIED === $event->type ? $event->data['cardId'] : $event->data['characterCardId'];

            $this->pendingDeath = array_filter($this->pendingDeath, static fn($a) => $idToRemove !== $a);
        }

        return $state;
    }

    /**
     * This method generates system events that should be checked after each event resolution, such as player death, monster death, etc.
     *
     * @return GameEvent[]
     */
    private function generateSystemsEvent(GameState $state, ?GameEvent $currentEvent = null): array
    {
        $events = [];

        foreach ([$state->player1, $state->player2] as $playerState) {
            if ($playerState->healthPoints > 0) {
                continue;
            }

            if (\in_array($playerState->characterCardId, $this->pendingDeath, true)) {
                continue;
            }

            $events[] = GameEvent::game(GameEventTypeEnum::PLAYER_DIED, [
                'playerId' => $playerState->player->id,
                'characterCardId' => $playerState->characterCardId,
            ]);

            $this->pendingDeath[] = $playerState->characterCardId;
        }

        foreach ($state->getAllMonsters() as $monsterCardId) {
            if (\in_array($monsterCardId, $this->pendingDeath, true)) {
                continue;
            }

            $cardState = $state->getCardState($monsterCardId);
            if (!$cardState) {
                continue;
            }

            $card = $this->cardRuntimeMap->getByState($cardState);

            if (!$card instanceof AbstractMonsterCard) {
                continue;
            }

            if ($card->getCurrentHealthPoints() <= 0) {
                $events[] = GameEvent::game(GameEventTypeEnum::MONSTER_DIED, [
                    'playerId' => $cardState->ownerId,
                    'cardId' => $monsterCardId,
                ]);

                $this->pendingDeath[] = $monsterCardId;
            }
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function generateReactions(GameEvent $event, GameState $state): array
    {
        $events = [];
        $playerId = $state->currentPlayer;

        switch ($event->type) {
            case GameEventTypeEnum::TURN_ENDED:
                $events[] = GameEvent::game(GameEventTypeEnum::TURN_STARTED, [
                    'playerId' => $playerId,
                ]);
                break;
            case GameEventTypeEnum::TURN_STARTED:
                $events[] = GameEvent::game(GameEventTypeEnum::COINS_GAINED, [
                    'playerId' => $playerId,
                    'amount' => $this->calculateCoinsGain($state),
                ]);
                $events[] = GameEvent::game(GameEventTypeEnum::CARD_DRAWN, [
                    'playerId' => $playerId,
                ]);
                $events = array_merge($events, $this->restoreMonstersAttack($state, $playerId));
                break;
            case GameEventTypeEnum::CARD_PLAYED:
                $events = $this->doGenerateReactionsForCardPlayed($event, $state);
                break;
            case GameEventTypeEnum::ATTACKED:
                $events = $this->doGenerateReactionsForAttack($event, $state);
                break;
            case GameEventTypeEnum::PLAYER_DIED:
            case GameEventTypeEnum::MONSTER_DIED:
                $events = $this->doGenerareReactionsForDeath($event, $state);
                break;
            case GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA:
            case GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA:
            case GameEventTypeEnum::CARD_CONSUMED:
                $cardId = $event->data['cardId'];
                /** @var AbstractMonsterCard|AbstractPassiveCard|AbstractConsumableCard $card */
                $card = $this->cardRuntimeMap->getByState($state->getCardState($cardId));
                $ctx = $this->gameContextFactory->createGameContext($state, $playerId);

                match (true) {
                    $card instanceof AbstractMonsterCard => $card->onMonsterPlayed($ctx),
                    $card instanceof AbstractPassiveCard => $card->onCardPlace($ctx),
                    // @todo introduce target on card to guess data
                    $card instanceof AbstractConsumableCard => $card->play($ctx, \is_array($event->data['data'] ?? null) ? $event->data['data'] : []),
                };

                $events = $ctx->flushEvents();

                if ($card instanceof AbstractConsumableCard) {
                    $events[] = GameEvent::game(GameEventTypeEnum::CARD_DISCARDED, [
                        'cardId' => $cardId,
                        'playerId' => $playerId,
                    ]);
                }
                break;
            default:
                break;
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function restoreMonstersAttack(GameState $state, string $playerId): array
    {
        $events = [];

        foreach ($state->getPlayer($playerId)->playArea->monsterCards as $cardId) {
            $currentState = $state->getCardState($cardId);

            if (!$currentState instanceof MonsterCardState || $currentState->canAttack) {
                continue;
            }

            $events[] = GameEvent::game(GameEventTypeEnum::CARD_STATE_UPDATED, [
                'cardId' => $cardId,
                'canAttack' => true,
            ]);
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function doGenerateReactionsForCardPlayed(GameEvent $event, GameState $state): array
    {
        if (!($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('cardId is required to play a card');
        }

        if (!($cardState = $state->cards[$cardId] ?? null)) {
            throw new \LogicException(\sprintf('Card with id %s not found in game state', $cardId));
        }

        if (!\is_string($event->data['playerId'] ?? null)) {
            throw new \LogicException('playerId is required to play a card');
        }

        $card = $this->cardRuntimeMap->getByState($cardState);
        $events = [];

        $cardCost = $card->getCost();

        if ($state->getCurrentPlayerState()->coins < $cardCost) {
            throw new NotEnoughCoinsException($cardCost, $state->getCurrentPlayerState()->coins);
        }

        $events[] = GameEvent::game(GameEventTypeEnum::COINS_LOST, [
            'playerId' => $event->data['playerId'],
            'amount' => $cardCost,
        ]);

        if ($card instanceof AbstractConsumableCard) {
            $events[] = GameEvent::game(GameEventTypeEnum::CARD_CONSUMED, [
                'playerId' => $event->data['playerId'],
                'cardId' => $event->data['cardId'],
                'data' => $event->data['data'] ?? [],
            ]);
        } elseif ($card instanceof AbstractPassiveCard) {
            $events[] = GameEvent::game(GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, [
                'playerId' => $event->data['playerId'],
                'cardId' => $event->data['cardId'],
            ]);
        } elseif ($card instanceof AbstractMonsterCard) {
            $events[] = GameEvent::game(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, [
                'playerId' => $event->data['playerId'],
                'cardId' => $event->data['cardId'],
                'cardHealthPoints' => $card->getHealPoints(),
            ]);
        } else {
            throw new \LogicException('Card must be either a playable or passive card');
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function doGenerateReactionsForAttack(GameEvent $event, GameState $state): array
    {
        if (!\is_string($event->data['attackerId'] ?? null)) {
            throw new \LogicException('attackerId is required for attack event');
        }

        if (!\is_string($event->data['targetId'] ?? null)) {
            throw new \LogicException('targetId is required for attack event');
        }

        $attackerCardState = $state->getCardState($attackerId = $event->data['attackerId']);

        if (!$attackerCardState) {
            throw new \LogicException('Attacker card state not found for cardId '.$event->data['attackerId']);
        }

        $card = $this->cardRuntimeMap->getByState($attackerCardState);

        if (!$card instanceof AbstractMonsterCard) {
            throw new \LogicException('Only monster cards can attack');
        }

        if (!$card->canAttack()) {
            throw new CardCannotAttackExpcetion('Card cannot attack');
        }

        $events = [];
        $targetId = $event->data['targetId'];
        // if player
        if (\in_array($targetId, [$state->getOtherPlayerState()->characterCardId, $state->getOtherPlayerState()->player->id], true)) {
            $events[] = GameEvent::game(GameEventTypeEnum::DAMAGE_DEALT, [
                'targetId' => $targetId,
                'damage' => $card->getAttack(),
                'sourceId' => $attackerId,
            ]);
        } elseif (\in_array($targetId, $state->getOtherPlayerState()->playArea->monsterCards, true)) {
            $target = $this->cardRuntimeMap->getByState($state->getCardState($event->data['targetId']));

            if (!$target instanceof AbstractMonsterCard) {
                throw new \LogicException('Target must be a monster card');
            }

            $baseDamage = $card->getAttack();
            $ctx = $this->gameContextFactory->createGameContext($state, $attackerCardState->ownerId);
            $reducedDamage = $target->reduceDamage($ctx, $baseDamage);

            $events[] = GameEvent::game(GameEventTypeEnum::DAMAGE_DEALT, [
                'targetId' => $targetId,
                'damage' => $reducedDamage,
                'sourceId' => $attackerId,
            ]);

            $events = array_merge($events, $ctx->flushEvents());
        } else {
            throw new \LogicException('Invalid targetId '.$event->data['targetId']);
        }

        $ctx = $this->gameContextFactory->createGameContext($state, $attackerCardState->ownerId);
        $card->onAttack($ctx);

        return array_merge(
            [
                GameEvent::game(GameEventTypeEnum::CARD_STATE_UPDATED, [
                    'cardId' => $attackerId,
                    'canAttack' => false,
                ]),
            ],
            $events,
            $ctx->flushEvents(),
        );
    }

    /**
     * @return GameEvent[]
     */
    private function doGenerareReactionsForDeath(GameEvent $event, GameState $state): array
    {
        $events = [];

        if (GameEventTypeEnum::PLAYER_DIED === $event->type) {
            return [];
        }

        if (GameEventTypeEnum::MONSTER_DIED === $event->type) {
            $cardId = $event->data['cardId'] ?? null;

            if (!$cardId || !\is_string($cardId)) {
                throw new \LogicException('cardId is required for MONSTER_DIED event');
            }

            if (!($cardState = $state->getCardState($cardId))) {
                throw new \LogicException('Card state not found for cardId '.$cardId);
            }

            $card = $this->cardRuntimeMap->getByState($cardState);

            if (!$card instanceof AbstractMonsterCard) {
                throw new \LogicException('Card with id '.$cardId.' is not a monster card');
            }

            if (!($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
                throw new \LogicException('No playerId found');
            }

            $ctx = $this->gameContextFactory->createGameContext($state, $playerId);
            $card->onMonsterDeath($ctx);

            $events = $ctx->flushEvents();
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function collectEventsFromAwareCards(GameEvent $event, GameState $state): array
    {
        $events = [];
        $ctx = $this->gameContextFactory->createGameContext($state, $state->currentPlayer);

        switch ($event->type) {
            case GameEventTypeEnum::TURN_ENDED:
                $cards = $this->getTurnAwareCards($state);

                foreach ($cards as $card) {
                    $card->onTurnEnd($event, $ctx);
                    $events = array_merge($events, $ctx->flushEvents());
                }

                break;
            case GameEventTypeEnum::TURN_STARTED:
                $cards = $this->getTurnAwareCards($state);

                foreach ($cards as $card) {
                    $card->onTurnStart($event, $ctx);
                    $events = array_merge($events, $ctx->flushEvents());
                }

                break;
            case GameEventTypeEnum::CARD_DRAWN:
                $cards = $this->getCardAwareCards($state);

                if ([] === $cards) {
                    return [];
                }

                $cardId = $state->getLastAddedCardId();

                if (!$cardId) {
                    throw new \LogicException('No card drawn for CARD_DRAWN event');
                }

                foreach ($cards as $card) {
                    $card->onCardDrawn($cardId, $ctx);
                    $events = array_merge($events, $ctx->flushEvents());
                }

                break;
            case GameEventTypeEnum::CARD_PLAYED:
                $cards = $this->getCardAwareCards($state);
                $cardId = $event->data['cardId'] ?? null;
                if (!\is_string($cardId)) {
                    throw new \LogicException('cardId is required for CARD_DRAWN event');
                }
                $cardState = $state->getCardState($cardId) ?? throw new \LogicException('Card state not found for cardId '.$cardId);
                $playedCard = $this->cardRuntimeMap->getByState($cardState);

                foreach ($cards as $card) {
                    $card->onCardPlayed($playedCard, $ctx);
                    $events = array_merge($events, $ctx->flushEvents());
                }
                break;
            case GameEventTypeEnum::MONSTER_DIED:
                $cards = $this->getDeathAwareCards($state);
                $cardId = $event->data['cardId'] ?? null;
                if (!\is_string($cardId)) {
                    throw new \LogicException('cardId is required for CARD_DRAWN event');
                }
                $cardState = $state->getCardState($cardId) ?? throw new \LogicException('Card state not found for cardId '.$cardId);
                $playedCard = $this->cardRuntimeMap->getByState($cardState);

                foreach ($cards as $card) {
                    $card->onCardDeath($playedCard, $ctx);
                    $events = array_merge($events, $ctx->flushEvents());
                }
                break;
            case GameEventTypeEnum::PLAYER_DIED:
                $cards = $this->getDeathAwareCards($state);
                $playerId = $event->data['playerId'] ?? null;
                if (!\is_string($playerId)) {
                    throw new \LogicException('cardId is required for PLAYER_DIED event');
                }
                foreach ($cards as $card) {
                    $card->onPlayerDeath($ctx, $playerId);
                    $events = array_merge($events, $ctx->flushEvents());
                }
                break;
            default:

            // @todo maybe log unknown event type
        }

        return $events;
    }

    /**
     * @return array<AbstractCard&TurnAwareInterface>
     */
    private function getTurnAwareCards(GameState $gameState): array
    {
        $cards = [];

        foreach ($this->getAllActiveCards($gameState) as $card) {
            if (!$card instanceof TurnAwareInterface) {
                continue;
            }

            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * @return array<AbstractCard&CardAwareInterface>
     */
    private function getCardAwareCards(GameState $gameState): array
    {
        $cards = [];

        foreach ($this->getAllActiveCards($gameState) as $card) {
            if (!$card instanceof CardAwareInterface) {
                continue;
            }

            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * @return array<AbstractCard&DeathAwareInterface>
     */
    private function getDeathAwareCards(GameState $gameState): array
    {
        $cards = [];

        foreach ($this->getAllActiveCards($gameState) as $card) {
            if (!$card instanceof DeathAwareInterface) {
                continue;
            }

            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * @return iterable<AbstractCard>
     */
    private function getAllActiveCards(GameState $gameState): iterable
    {
        foreach ($gameState->getAllActiveCards() as $card) {
            if (!($state = $gameState->getCardState($card))) {
                // @todo maybe log
                continue;
            }

            yield $this->cardRuntimeMap->getByState($state);
        }
    }

    private function calculateCoinsGain(GameState $state): int
    {
        // maybe round based

        return 3;
    }

    /**
     * @param GameEvent[] $events
     */
    private function pushEventsToQueue(array $events): void
    {
        $this->eventQueue = array_merge($this->eventQueue, $events);
    }
}
