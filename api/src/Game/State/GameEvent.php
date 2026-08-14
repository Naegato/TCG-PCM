<?php

declare(strict_types=1);

namespace App\Game\State;

use App\Enum\GameEventTypeEnum;

readonly class GameEvent
{
    public const PLAYER_EVENT = 'player_event';
    public const GAME_EVENT = 'game_event';

    public function __construct(
        public int $id,
        public GameEventTypeEnum $type,
        public string $eventOrigin,
        public array $data,
        public ?int $parentId = null,
        /**
         * Identifies this event within a single GameEventResolver::resolve() pass, assigned
         * as the event enters the resolution queue. Unrelated to $id, which stays 0 until the
         * event is persisted (most events never are) — $parentId references this field, not $id.
         */
        public int $localId = 0,
    ) {}

    public static function game(GameEventTypeEnum $type, array $data, ?int $parentId = null): self
    {
        return new self(0, $type, self::GAME_EVENT, $data, $parentId);
    }

    public static function player(GameEventTypeEnum $type, array $data): self
    {
        return new self(0, $type, self::PLAYER_EVENT, $data);
    }

    public function shouldBePersisted(): bool
    {
        if (self::PLAYER_EVENT === $this->eventOrigin) {
            return true;
        }

        return match ($this->type) {
            GameEventTypeEnum::CARD_RUNTIME_VALUE => true,
            default => false,
        };
    }

    #[\NoDiscard]
    public function withData(array $data): self
    {
        return clone($this, ['data' => array_merge($this->data, $data)]);
    }

    #[\NoDiscard]
    public function withId(int $id): self
    {
        return clone($this, ['id' => $id]);
    }

    #[\NoDiscard]
    public function withLocalId(int $localId): self
    {
        return clone($this, ['localId' => $localId]);
    }
}
