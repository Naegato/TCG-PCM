<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Enum\CardTriggerEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\MonsterCardState;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\Exception\CardCannotAttackExpcetion;
use App\Game\Exception\InvalidTargetException;
use App\Game\Exception\NotEnoughCoinsException;
use App\Game\GameContext;
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
     * @var array<int, GameEvent>
     */
    private array $resolvedEvents = [];

    // Tracks characters/monsters already reported dead for the current resolve() call, to avoid
    // re-emitting PLAYER_DIED/MONSTER_DIED on every subsequent event while health stays at 0. Only
    // cleared once resolve() finishes (see finally block below) — clearing it as soon as the death
    // event itself resolves used to re-open the window while its own CARD_TRIGGERED_ACTION reactions
    // were still being drained, causing generateSystemsEvent() to redetect the same death and loop.
    private array $pendingDeath = [];

    private int $nextLocalId = 1;

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

            return new ResolutionResult(array_values($this->resolvedEvents), $state);
        } finally {
            $this->eventQueue = [];
            $this->resolvedEvents = [];
            $this->nextLocalId = 1;
            $this->pendingDeath = [];
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

        $this->resolvedEvents[$event->localId] = $event;

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

            $events[] = GameEvent::game(
                GameEventTypeEnum::PLAYER_DIED,
                [
                    'playerId' => $playerState->player->id,
                    'characterCardId' => $playerState->characterCardId,
                ],
                $currentEvent?->localId,
            );

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
                $events[] = GameEvent::game(
                    GameEventTypeEnum::MONSTER_DIED,
                    [
                        'playerId' => $cardState->ownerId,
                        'cardId' => $monsterCardId,
                    ],
                    $currentEvent?->localId,
                );

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
                $events[] = GameEvent::game(
                    GameEventTypeEnum::TURN_STARTED,
                    [
                        'playerId' => $playerId,
                    ],
                    $event->localId,
                );
                break;
            case GameEventTypeEnum::TURN_STARTED:
                $events[] = GameEvent::game(
                    GameEventTypeEnum::COINS_GAINED,
                    [
                        'playerId' => $playerId,
                        'amount' => $this->calculateCoinsGain($state),
                    ],
                    $event->localId,
                );
                $events[] = GameEvent::game(
                    GameEventTypeEnum::CARD_DRAWN,
                    [
                        'playerId' => $playerId,
                    ],
                    $event->localId,
                );
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
                $ctx = $this->gameContextFactory->createGameContext($state, $playerId, $event->localId);
                $playData = \is_array($event->data['data'] ?? null) ? $event->data['data'] : [];

                if ($card instanceof AbstractConsumableCard) {
                    $this->assertValidTarget($card, $playData, $ctx);
                }

                match (true) {
                    $card instanceof AbstractMonsterCard => $card->onMonsterPlayed($ctx),
                    $card instanceof AbstractPassiveCard => $card->onCardPlace($ctx),
                    $card instanceof AbstractConsumableCard => $card->play($ctx, $playData),
                };

                $events = $ctx->flushEvents();

                if ($card instanceof AbstractConsumableCard) {
                    $events[] = GameEvent::game(
                        GameEventTypeEnum::CARD_DISCARDED,
                        [
                            'cardId' => $cardId,
                            'playerId' => $playerId,
                        ],
                        $event->localId,
                    );
                }
                break;
            case GameEventTypeEnum::CARD_TRIGGERED_ACTION:
                $events = $this->doHandleCardTriggeredAction($event, $state);
                break;
            default:
                break;
        }

        return $events;
    }

    private function assertValidTarget(AbstractConsumableCard $card, array $data, GameContext $ctx): void
    {
        if (!$card->requiresTarget()) {
            return;
        }

        $targetId = $data['target'] ?? null;

        if (!\is_string($targetId)) {
            throw new InvalidTargetException('A target is required to play this card.');
        }

        $targetCardState = $ctx->state->getCardState($targetId);

        if (null === $targetCardState) {
            throw new InvalidTargetException('Target card not found.');
        }

        $targetCard = $this->cardRuntimeMap->getByState($targetCardState);

        $entityFlag = match (true) {
            $targetCard instanceof AbstractMonsterCard => AbstractConsumableCard::TARGET_TYPE_MONSTER,
            $targetCard instanceof AbstractPassiveCard => AbstractConsumableCard::TARGET_TYPE_PASSIVE,
            $targetCard instanceof AbstractCharacterCard => AbstractConsumableCard::TARGET_TYPE_CHARACTER,
            default => 0,
        };

        $ownershipFlag = $targetCardState->ownerId === $ctx->playerId
            ? AbstractConsumableCard::TARGET_SELF_CARDS
            : AbstractConsumableCard::TARGET_OPPONENT_CARDS;

        $targetType = $card->getTargetType();

        if (0 === ($targetType & $entityFlag) || 0 === ($targetType & $ownershipFlag)) {
            throw new InvalidTargetException('This card cannot target the selected card.');
        }
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

            $events[] = GameEvent::game(
                GameEventTypeEnum::CARD_STATE_UPDATED,
                [
                    'cardId' => $cardId,
                    'canAttack' => true,
                ],
                null,
            );
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

        $events[] = GameEvent::game(
            GameEventTypeEnum::COINS_LOST,
            [
                'playerId' => $event->data['playerId'],
                'amount' => $cardCost,
            ],
            $event->localId,
        );

        if ($card instanceof AbstractConsumableCard) {
            $events[] = GameEvent::game(
                GameEventTypeEnum::CARD_CONSUMED,
                [
                    'playerId' => $event->data['playerId'],
                    'cardId' => $event->data['cardId'],
                    'data' => $event->data['data'] ?? [],
                ],
                $event->localId,
            );
        } elseif ($card instanceof AbstractPassiveCard) {
            $events[] = GameEvent::game(
                GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA,
                [
                    'playerId' => $event->data['playerId'],
                    'cardId' => $event->data['cardId'],
                ],
                $event->localId,
            );
        } elseif ($card instanceof AbstractMonsterCard) {
            $events[] = GameEvent::game(
                GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA,
                [
                    'playerId' => $event->data['playerId'],
                    'cardId' => $event->data['cardId'],
                    'cardHealthPoints' => $card->getHealPoints(),
                ],
                $event->localId,
            );
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
            $events[] = GameEvent::game(
                GameEventTypeEnum::DAMAGE_DEALT,
                [
                    'targetId' => $targetId,
                    'damage' => $card->getAttack(),
                    'sourceId' => $attackerId,
                ],
                $event->localId,
            );
        } elseif (\in_array($targetId, $state->getOtherPlayerState()->playArea->monsterCards, true)) {
            $target = $this->cardRuntimeMap->getByState($state->getCardState($event->data['targetId']));

            if (!$target instanceof AbstractMonsterCard) {
                throw new \LogicException('Target must be a monster card');
            }

            $baseDamage = $card->getAttack();
            $ctx = $this->gameContextFactory->createGameContext($state, $attackerCardState->ownerId, $event->localId);
            $reducedDamage = $target->reduceDamage($ctx, $baseDamage);

            $events[] = GameEvent::game(
                GameEventTypeEnum::DAMAGE_DEALT,
                [
                    'targetId' => $targetId,
                    'damage' => $reducedDamage,
                    'sourceId' => $attackerId,
                ],
                $event->localId,
            );

            $events = array_merge($events, $ctx->flushEvents());
        } else {
            throw new \LogicException('Invalid targetId '.$event->data['targetId']);
        }

        $ctx = $this->gameContextFactory->createGameContext($state, $attackerCardState->ownerId, $event->localId);
        $card->onAttack($ctx);

        return array_merge(
            [
                GameEvent::game(
                    GameEventTypeEnum::CARD_STATE_UPDATED,
                    [
                        'cardId' => $attackerId,
                        'canAttack' => false,
                    ],
                    $event->localId,
                ),
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

            $ctx = $this->gameContextFactory->createGameContext($state, $playerId, $event->localId);
            $card->onMonsterDeath($ctx);

            $events = $ctx->flushEvents();
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function doHandleCardTriggeredAction(GameEvent $event, GameState $state): array
    {
        $cardId = $event->data['cardId'] ?? null;
        $trigger = $event->data['trigger'] ?? null;

        if (!\is_string($cardId) || !\is_string($trigger)) {
            throw new \LogicException('Invalid CARD_TRIGGERED_ACTION event data');
        }

        $card = $this->findCard($state, $cardId);
        $ctx = $this->gameContextFactory->createGameContext($state, $state->currentPlayer, $event->localId);
        $triggerType = CardTriggerEnum::from($trigger);

        match (true) {
            CardTriggerEnum::TURN_START === $triggerType && $card instanceof TurnAwareInterface => $card->onTurnStart(
                $this->findResolvedEventByLocalId($event->parentId),
                $ctx,
            ),
            CardTriggerEnum::TURN_END === $triggerType && $card instanceof TurnAwareInterface => $card->onTurnEnd(
                $this->findResolvedEventByLocalId($event->parentId),
                $ctx,
            ),
            CardTriggerEnum::CARD_DRAWN === $triggerType && $card instanceof CardAwareInterface => $card->onCardDrawn(
                $this->requireStringData($event, 'drawnCardId'),
                $ctx,
            ),
            CardTriggerEnum::CARD_PLAYED === $triggerType && $card instanceof CardAwareInterface => $card->onCardPlayed(
                $this->findCard($state, $this->requireStringData($event, 'sourceCardId')),
                $ctx,
            ),
            CardTriggerEnum::CARD_DEATH === $triggerType && $card instanceof DeathAwareInterface => $card->onCardDeath(
                $this->findCard($state, $this->requireStringData($event, 'sourceCardId')),
                $ctx,
            ),
            CardTriggerEnum::PLAYER_DEATH === $triggerType && $card instanceof DeathAwareInterface => $card->onPlayerDeath($ctx, $this->requireStringData(
                $event,
                'deadPlayerId',
            )),
            default => null,
        };

        return $ctx->flushEvents();
    }

    private function findCard(GameState $state, string $cardId): AbstractCard
    {
        $cardState = $state->getCardState($cardId) ?? throw new \LogicException('Card state not found for cardId '.$cardId);

        return $this->cardRuntimeMap->getByState($cardState);
    }

    private function requireStringData(GameEvent $event, string $key): string
    {
        $value = $event->data[$key] ?? null;

        if (!\is_string($value)) {
            throw new \LogicException(\sprintf('%s is required for %s event', $key, $event->type->value));
        }

        return $value;
    }

    /**
     * CARD_TRIGGERED_ACTION carries only its parent's localId, not a copy of the parent event
     * itself — GameEvent::$data must stay plain/serializable (it's published to the front as-is).
     * The parent is guaranteed to already be resolved by the time this runs: BFS resolves an event
     * fully — including pushing everything it caused, like this one — before dequeuing that event's
     * own children.
     */
    private function findResolvedEventByLocalId(?int $localId): GameEvent
    {
        if (null === $localId) {
            throw new \LogicException('No resolved event found for localId null');
        }

        return $this->resolvedEvents[$localId] ?? throw new \LogicException('No resolved event found for localId '.$localId);
    }

    /**
     * @return GameEvent[]
     */
    private function collectEventsFromAwareCards(GameEvent $event, GameState $state): array
    {
        $events = [];

        switch ($event->type) {
            case GameEventTypeEnum::TURN_ENDED:
                $cards = $this->getTurnAwareCards($state);

                $events = array_map(static fn($card) => GameEvent::game(
                    GameEventTypeEnum::CARD_TRIGGERED_ACTION,
                    [
                        'cardId' => $card->getInstanceId(),
                        'trigger' => CardTriggerEnum::TURN_END->value,
                    ],
                    $event->localId,
                ), $cards);

                break;
            case GameEventTypeEnum::TURN_STARTED:
                $cards = $this->getTurnAwareCards($state);

                $events = array_map(static fn($card) => GameEvent::game(
                    GameEventTypeEnum::CARD_TRIGGERED_ACTION,
                    [
                        'cardId' => $card->getInstanceId(),
                        'trigger' => CardTriggerEnum::TURN_START->value,
                    ],
                    $event->localId,
                ), $cards);

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

                $events = array_map(static fn($card) => GameEvent::game(
                    GameEventTypeEnum::CARD_TRIGGERED_ACTION,
                    [
                        'cardId' => $card->getInstanceId(),
                        'trigger' => CardTriggerEnum::CARD_DRAWN->value,
                        'drawnCardId' => $cardId,
                    ],
                    $event->localId,
                ), $cards);

                break;
            case GameEventTypeEnum::CARD_PLAYED:
                $cards = $this->getCardAwareCards($state);
                $cardId = $event->data['cardId'] ?? null;
                if (!\is_string($cardId)) {
                    throw new \LogicException('cardId is required for CARD_DRAWN event');
                }

                $events = array_map(static fn($card) => GameEvent::game(
                    GameEventTypeEnum::CARD_TRIGGERED_ACTION,
                    [
                        'cardId' => $card->getInstanceId(),
                        'trigger' => CardTriggerEnum::CARD_PLAYED->value,
                        'sourceCardId' => $cardId,
                    ],
                    $event->localId,
                ), $cards);

                break;
            case GameEventTypeEnum::MONSTER_DIED:
                $cards = $this->getDeathAwareCards($state);
                $cardId = $event->data['cardId'] ?? null;
                if (!\is_string($cardId)) {
                    throw new \LogicException('cardId is required for CARD_DRAWN event');
                }

                $events = array_map(static fn($card) => GameEvent::game(
                    GameEventTypeEnum::CARD_TRIGGERED_ACTION,
                    [
                        'cardId' => $card->getInstanceId(),
                        'trigger' => CardTriggerEnum::CARD_DEATH->value,
                        'sourceCardId' => $cardId,
                    ],
                    $event->localId,
                ), $cards);

                break;
            case GameEventTypeEnum::PLAYER_DIED:
                $cards = $this->getDeathAwareCards($state);
                $playerId = $event->data['playerId'] ?? null;
                if (!\is_string($playerId)) {
                    throw new \LogicException('cardId is required for PLAYER_DIED event');
                }

                $events = array_map(static fn($card) => GameEvent::game(
                    GameEventTypeEnum::CARD_TRIGGERED_ACTION,
                    [
                        'cardId' => $card->getInstanceId(),
                        'trigger' => CardTriggerEnum::PLAYER_DEATH->value,
                        'deadPlayerId' => $playerId,
                    ],
                    $event->localId,
                ), $cards);

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
        foreach ($events as $event) {
            $this->eventQueue[] = $event->withLocalId($this->nextLocalId++);
        }
    }
}
