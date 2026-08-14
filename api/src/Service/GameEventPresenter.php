<?php

declare(strict_types=1);

namespace App\Service;

use App\Api\DTO\CardDTO;
use App\Enum\GameEventTypeEnum;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Service\Game\GameStateConverter;

final class GameEventPresenter
{
    private const CARD_IMAGE_BASE_URL = 'cards/';

    private int $player1Offset = 0;
    private int $player2Offset = 0;

    /**
     * present() is called once per topic a given event is broadcast to (e.g. once for
     * the public topic, once more for a private topic), but an inferred CARD_DRAWN
     * cardId must only ever be computed — and its offset consumed — once per event,
     * not once per presentation. Keyed by spl_object_id() of the event.
     *
     * @var array<int, string|null>
     */
    private array $inferredCardIdByEvent = [];

    public function __construct(
        private GameStateConverter $gameStateConverter,
    ) {}

    public function present(GameEvent $event, GameState $state, bool $isPrivate, ?string $viewerId = null): array
    {
        return [
            // localId/parentId, not the persisted id — the latter stays 0 for almost every event
            // (see GameEventResolver) and is meaningless to the front. These let the front group
            // an event with whichever CARD_TRIGGERED_ACTION caused it.
            'id' => $event->localId,
            'parentId' => $event->parentId,
            'type' => $event->type->value,
            'data' => $event->data,
            'view' => array_merge(
                [
                    'playerId' => $event->data['playerId'] ?? $event->data['targetId'] ?? null,
                ],
                $this->buildView($event, $state, $isPrivate, $viewerId),
            ),
        ];
    }

    private function buildView(GameEvent $event, GameState $state, bool $isPrivate, ?string $viewerId = null): array
    {
        if (null === ($event->data['playerId'] ?? $event->data['targetId'] ?? null)) {
            return $this->handleAnonymousEvent($event, $state, $isPrivate, $viewerId);
        }
        /** @var string $player */
        $player = $event->data['playerId'] ?? $event->data['targetId'];

        return match ($event->type) {
            GameEventTypeEnum::CARD_DRAWN => $this->cardDrawnView($event, $state, $isPrivate, $viewerId),
            GameEventTypeEnum::TURN_STARTED, GameEventTypeEnum::CURRENT_PLAYER_SET => [
                'currentPlayer' => $state->currentPlayer,
            ],
            GameEventTypeEnum::CARD_DISCARDED, GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA => [
                'cardId' => $event->data['cardId'] ?? null,
                'card' => GameEventTypeEnum::CARD_DISCARDED === $event->type
                    ? null
                    : $this->normalizeCardDTO($this->gameStateConverter->createCardDTO($state->cards[$event->data['cardId']])),
            ],
            GameEventTypeEnum::COINS_GAINED, GameEventTypeEnum::COINS_LOST => [
                'total' => $state->getPlayer($player)->coins,
            ],
            GameEventTypeEnum::HEAL_APPLIED, GameEventTypeEnum::DAMAGE_DEALT => $this->healthView($event, $state),
            GameEventTypeEnum::MONSTER_DIED => [
                'cardId' => $event->data['cardId'] ?? null,
            ],
            GameEventTypeEnum::CARD_REDRAWN => [
                'cardId' => $event->data['cardId'] ?? null,
            ],
            GameEventTypeEnum::CARD_STOLEN => [
                'cardId' => $event->data['cardId'] ?? null,
                'fromPlayerId' => $event->data['fromPlayerId'] ?? null,
                'toPlayerId' => $event->data['toPlayerId'] ?? null,
            ],
            default => [],
        };
    }

    private function handleAnonymousEvent(GameEvent $event, GameState $state, bool $isPrivate, ?string $viewerId): array
    {
        return match ($event->type) {
            GameEventTypeEnum::EFFECT_ADDED, GameEventTypeEnum::CARD_STATE_UPDATED => $this->cardStateView($event, $state),
            default => [],
        };
    }

    private function cardStateView(GameEvent $event, GameState $state): array
    {
        /** @var string $cardId */
        $cardId = $event->data['cardId'];
        $cardState = $state->getCardState($cardId);

        if (null === $cardState) {
            return ['cardId' => $cardId];
        }

        return [
            'cardId' => $cardId,
            'card' => $this->normalizeCardDTO($this->gameStateConverter->createCardDTO($cardState)),
        ];
    }

    private function cardDrawnView(GameEvent $event, GameState $state, bool $isPrivate, ?string $viewerId = null): array
    {
        /** @var string $playerId */
        $playerId = $event->data['playerId'];
        $player = $state->getPlayer($playerId);

        if (\is_string($event->data['cardId'] ?? null)) {
            // the event already knows which card it is (e.g. explicit draws, debug tooling) — trust it
            $cardId = $event->data['cardId'];
        } else {
            // random deck draw: the drawn card isn't known ahead of time, so infer it from the
            // (already-updated) hand by counting back from the end as we process each draw in the batch.
            // present() is called once per topic this event is broadcast to, so cache the result the
            // first time and consume the offset only once, or a second (private-topic) presentation of
            // the same draw would shift the offset and resolve to the wrong card.
            $eventKey = spl_object_id($event);

            if (\array_key_exists($eventKey, $this->inferredCardIdByEvent)) {
                $cardId = $this->inferredCardIdByEvent[$eventKey];
            } else {
                $offset = $player === $state->player1 ? $this->player1Offset : $this->player2Offset;
                $cardId = array_slice($player->hand, -($offset + 1), 1)[0] ?? null;

                $player === $state->player1 ? ++$this->player1Offset : ++$this->player2Offset;

                $this->inferredCardIdByEvent[$eventKey] = $cardId;
            }
        }

        if (!$playerId || !$cardId) {
            throw new \LogicException('CARD_DRAWN requires playerId and cardId');
        }

        $view = [
            'playerId' => $playerId,
            'cardId' => $cardId,
        ];

        if ($isPrivate && $viewerId === $playerId) {
            $cardState = $state->cards[$cardId] ?? null;

            if (!$cardState) {
                throw new \LogicException(\sprintf('Card %s not found in state', $cardId));
            }

            $view['card'] = $this->normalizeCardDTO($this->gameStateConverter->createCardDTO($cardState));
        }

        return $view;
    }

    private function normalizeCardDTO(CardDTO $card): array
    {
        $path = $card->image;

        return [
            'name' => $card->name,
            'description' => $card->description,
            'type' => $card->type?->name,
            'rarity' => $card->rarity->name,
            'serie' => $card->set->name,
            'image' => filter_var($path, FILTER_VALIDATE_URL) ? $path : self::CARD_IMAGE_BASE_URL.strtolower($path),
            'requiresTarget' => $card->requiresTarget,
            'targetType' => $card->targetType,
            'cost' => $card->cost,
            'hp' => $card->hp,
            'attack' => $card->attack,
            'instanceId' => $card->instanceId,
            'effects' => $card->effects,
            'isActive' => $card->isActive,
        ];
    }

    private function healthView(GameEvent $event, GameState $state): array
    {
        $targetId = $event->data['targetId'] ?? null;

        if (!\is_string($targetId)) {
            return [];
        }

        if ($targetId === $state->player1->characterCardId) {
            return [
                'playerId' => $state->player1->player->id,
                'total' => $state->player1->healthPoints,
            ];
        }

        if ($targetId === $state->player2->characterCardId) {
            return [
                'playerId' => $state->player2->player->id,
                'total' => $state->player2->healthPoints,
            ];
        }

        $cardState = $state->getCardState($targetId);

        if (null !== $cardState) {
            return [
                'cardId' => $targetId,
                'card' => $this->normalizeCardDTO($this->gameStateConverter->createCardDTO($cardState)),
            ];
        }

        return [
            'total' => $state->getPlayer($targetId)->healthPoints,
        ];
    }
}
