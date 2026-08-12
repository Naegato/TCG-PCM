<?php

declare(strict_types=1);

namespace App\Service\Debug;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;

final class DebugGameActionConverter
{
    public function __construct(
        private CardIdGeneratorInterface $cardIdGenerator,
        private CardFactoryInterface $cardFactory,
    ) {}

    /**
     * @return GameEvent[]
     */
    public function convert(DebugGameAction $action, GameState $state): array
    {
        return match ($action->actionId) {
            DebugGameAction::GIVE_CARD => $this->giveCard($action, $state),
            DebugGameAction::SET_STATS => $this->setStats($action, $state),
            DebugGameAction::FORCE_END_TURN => $this->forceEndTurn($state),
            DebugGameAction::FORCE_SET_CURRENT_PLAYER => $this->forceSetCurrentPlayer($action, $state),
            DebugGameAction::REMOVE_CARD => $this->removeCard($action, $state),
            default => throw new \LogicException(\sprintf('Unknown debug action "%s"', $action->actionId)),
        };
    }

    /**
     * @return GameEvent[]
     */
    private function giveCard(DebugGameAction $action, GameState $state): array
    {
        if (null === ($playerId = $action->payload['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \InvalidArgumentException('give_card requires a playerId');
        }

        if (null === ($cardTemplateId = $action->payload['cardTemplateId'] ?? null) || !\is_string($cardTemplateId)) {
            throw new \InvalidArgumentException('give_card requires a cardTemplateId');
        }

        $zone = $action->payload['zone'] ?? 'hand';

        $this->assertValidPlayer($playerId, $state);

        $cardInstanceId = $this->cardIdGenerator->generateCardId($cardTemplateId);

        $events = [
            GameEvent::player(GameEventTypeEnum::CARD_GENERATED, [
                'playerId' => $playerId,
                'cardTemplateId' => $cardTemplateId,
                'cardInstanceId' => $cardInstanceId,
            ]),
        ];

        if ('board' === $zone) {
            $template = $this->cardFactory->create($cardTemplateId);

            if ($template instanceof AbstractMonsterCard) {
                $events[] = GameEvent::player(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, [
                    'playerId' => $playerId,
                    'cardId' => $cardInstanceId,
                    'cardHealthPoints' => $template->getHealPoints(),
                ]);
            } else {
                $events[] = GameEvent::player(GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, [
                    'playerId' => $playerId,
                    'cardId' => $cardInstanceId,
                ]);
            }
        } else {
            $events[] = GameEvent::player(GameEventTypeEnum::CARD_DRAWN, [
                'playerId' => $playerId,
                'cardId' => $cardInstanceId,
            ]);
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function setStats(DebugGameAction $action, GameState $state): array
    {
        if (null === ($playerId = $action->payload['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \InvalidArgumentException('set_stats requires a playerId');
        }

        $this->assertValidPlayer($playerId, $state);
        $player = $state->getPlayer($playerId);
        $events = [];

        if (\is_int($healthPoints = $action->payload['healthPoints'] ?? null)) {
            $delta = $healthPoints - $player->healthPoints;

            if ($delta > 0) {
                $events[] = GameEvent::player(GameEventTypeEnum::HEAL_APPLIED, [
                    'targetId' => $playerId,
                    'amount' => $delta,
                ]);
            } elseif ($delta < 0) {
                $events[] = GameEvent::player(GameEventTypeEnum::DAMAGE_DEALT, [
                    'targetId' => $playerId,
                    'damage' => abs($delta),
                ]);
            }
        }

        if (\is_int($coins = $action->payload['coins'] ?? null)) {
            $delta = $coins - $player->coins;

            if ($delta > 0) {
                $events[] = GameEvent::player(GameEventTypeEnum::COINS_GAINED, [
                    'playerId' => $playerId,
                    'amount' => $delta,
                ]);
            } elseif ($delta < 0) {
                $events[] = GameEvent::player(GameEventTypeEnum::COINS_LOST, [
                    'playerId' => $playerId,
                    'amount' => abs($delta),
                ]);
            }
        }

        return $events;
    }

    /**
     * @return GameEvent[]
     */
    private function forceEndTurn(GameState $state): array
    {
        return [
            GameEvent::player(GameEventTypeEnum::TURN_ENDED, [
                'playerId' => $state->currentPlayer,
            ]),
        ];
    }

    /**
     * @return GameEvent[]
     */
    private function forceSetCurrentPlayer(DebugGameAction $action, GameState $state): array
    {
        if (null === ($playerId = $action->payload['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \InvalidArgumentException('force_set_current_player requires a playerId');
        }

        $this->assertValidPlayer($playerId, $state);

        return [
            GameEvent::player(GameEventTypeEnum::CURRENT_PLAYER_SET, [
                'playerId' => $playerId,
            ]),
        ];
    }

    /**
     * @return GameEvent[]
     */
    private function removeCard(DebugGameAction $action, GameState $state): array
    {
        if (null === ($cardId = $action->payload['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \InvalidArgumentException('remove_card requires a cardId');
        }

        if (!$state->getCardState($cardId)) {
            throw new \InvalidArgumentException(\sprintf('Card "%s" not found in this game', $cardId));
        }

        return [
            GameEvent::player(GameEventTypeEnum::CARD_DISCARDED, [
                'cardId' => $cardId,
            ]),
        ];
    }

    private function assertValidPlayer(string $playerId, GameState $state): void
    {
        if ($playerId !== $state->player1->player->id && $playerId !== $state->player2->player->id) {
            throw new \InvalidArgumentException(\sprintf('Player "%s" not found in this game', $playerId));
        }
    }
}
