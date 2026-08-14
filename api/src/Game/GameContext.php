<?php

declare(strict_types=1);

namespace App\Game;

use App\Enum\CardEffectEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayerState;

class GameContext
{
    /**
     * @var GameEvent[] $events
     */
    private array $events = [];

    public function __construct(
        public readonly GameState $state,
        public readonly string $playerId,
        public readonly ?int $parentEventId = null,
    ) {}

    public function drawCards(int $count, ?string $playerId = null): void
    {
        $playerId ??= $this->playerId;

        for ($i = 0; $i < $count; $i++) {
            $this->pushGameEvent(GameEventTypeEnum::CARD_DRAWN, ['playerId' => $playerId]);
        }
    }

    public function attack(int $damage, ?string $playerId = null): void
    {
        $playerId ??= $this->getOpponent()->id;

        $this->pushGameEvent(GameEventTypeEnum::DAMAGE_DEALT, ['targetId' => $playerId, 'damage' => $damage]);
    }

    public function heal(int $amount, ?string $playerId = null): void
    {
        $playerId ??= $this->playerId;

        $this->pushGameEvent(GameEventTypeEnum::HEAL_APPLIED, ['targetId' => $playerId, 'amount' => $amount]);
    }

    /**
     * @return GameEvent[]
     */
    public function flushEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function pushGameEvent(GameEventTypeEnum $type, array $payload = []): void
    {
        $this->events[] = GameEvent::game($type, $payload, $this->parentEventId);
    }

    public function getOpponent(): Player
    {
        return $this->state->player1->player->id === $this->playerId ? $this->state->player2->player : $this->state->player1->player;
    }

    public function getCurrentPlayerState(): PlayerState
    {
        return $this->state->currentPlayer === $this->state->player1->player->id ? $this->state->player1 : $this->state->player2;
    }

    public function rollDice(int $faces): int
    {
        $result = $this->state->randomizer->roll($faces);

        $this->pushGameEvent(GameEventTypeEnum::DICE_ROLLED, [
            'faces' => $faces,
            'result' => $result,
        ]);

        return $result;
    }

    public function randomBetween(float $min, float $max): float
    {
        $result = $this->state->randomizer->randomBetweenFloat($min, $max);

        $this->pushGameEvent(GameEventTypeEnum::DICE_ROLLED, [
            'min' => $min,
            'max' => $max,
            'result' => $result,
        ]);

        return $result;
    }

    public function randomIntBetween(int $min, int $max): int
    {
        $result = $this->state->randomizer->randomBetweenInt($min, $max);

        $this->pushGameEvent(GameEventTypeEnum::DICE_ROLLED, [
            'min' => $min,
            'max' => $max,
            'result' => $result,
        ]);

        return $result;
    }

    public function runtimeValueEffect(mixed $value): mixed
    {
        if (\is_callable($value)) {
            $value = $value();
        }

        $this->pushGameEvent(GameEventTypeEnum::CARD_RUNTIME_VALUE, ['value' => $value]);

        return $value;
    }

    public function addEffect(CardEffectEnum $effect, string $cardId, ?array $effectValues = null): void
    {
        $this->pushGameEvent(GameEventTypeEnum::EFFECT_ADDED, [
            'effect' => $effect->value,
            'cardId' => $cardId,
            'effectValues' => $effectValues,
        ]);
    }

    public function getCard(string $instanceId): CardState
    {
        return $this->state->cards[$instanceId];
    }

    public function isCurrentPlayer(?string $playerId): bool
    {
        if ($playerId === null) {
            return false;
        }

        return $this->state->currentPlayer === $playerId;
    }

    public function getOneRandomCard(?string $playerId): string
    {
        $pool = null === $playerId ? $this->state->getAllActiveCards() : array_merge($this->state->getPlayer($playerId)->playArea->getAll(), [
                $this->state->getPlayer($playerId)->characterCardId,
            ]);

        if ([] === $pool) {
            throw new \LogicException('No cards available to select');
        }

        return $this->selectRandomCardIn($pool);
    }

    /**
     * @param string[] $pool
     */
    public function selectRandomCardIn(array $pool): string
    {
        if ([] === $pool) {
            throw new \LogicException('No cards available to select');
        }

        return $this->getRandomFromArray($pool);
    }

    public function getRandomFromArray(array $array): mixed
    {
        if ([] === $array) {
            throw new \LogicException('No values available to select');
        }

        $values = array_values($array);
        $index = $this->state->randomizer->randomBetweenInt(0, count($values) - 1);

        return $values[$index];
    }

    public function getOtherPlayerId(string $playerId): string
    {
        return $this->state->player1->player->id === $playerId ? $this->state->player2->player->id : $this->state->player1->player->id;
    }

    public function getOpponentState(): PlayerState
    {
        return $this->state->currentPlayer === $this->state->player1->player->id ? $this->state->player2 : $this->state->player1;
    }

    public function lastActionHasBeenPrevented(): bool
    {
        return array_last($this->events)?->type === GameEventTypeEnum::CARD_ACTION_PREVENTED;
    }

    public function preventLastAction(): void
    {
        $this->pushGameEvent(GameEventTypeEnum::CARD_ACTION_PREVENTED);
    }

    /**
     * @return string[]
     */
    public function getMonsters(): array
    {
        return array_merge($this->state->player1->playArea->monsterCards, $this->state->player2->playArea->monsterCards);
    }

    public function discardCard(string $cardId): void
    {
        $this->pushGameEvent(GameEventTypeEnum::CARD_DISCARDED, [
            'cardId' => $cardId,
            'playerId' => $this->state->getCardState($cardId)?->ownerId,
        ]);
    }

    public function addCoins(int $amount, ?string $playerId = null): void
    {
        $playerId ??= $this->playerId;

        $this->pushGameEvent(GameEventTypeEnum::COINS_GAINED, ['playerId' => $playerId, 'amount' => $amount]);
    }

    public function setCoins(int $amount, ?string $playerId = null): void
    {
        $playerId ??= $this->playerId;
        $currentCoins = $this->state->getPlayer($playerId)->coins;

        $eventType = $amount > $currentCoins ? GameEventTypeEnum::COINS_GAINED : GameEventTypeEnum::COINS_LOST;

        $this->pushGameEvent($eventType, ['playerId' => $playerId, 'amount' => abs($amount - $currentCoins)]);
    }

    public function giveCard(string $cardId, ?string $playerId = null): void
    {
        $playerId ??= $this->playerId;

        $id = $this->state->randomizer->roll(0xFFFF_FFFF);

        $this->pushGameEvent(GameEventTypeEnum::CARD_GENERATED, ['playerId' => $playerId, 'cardTemplateId' => $cardId, 'cardInstanceId' => $id]);
    }

    public function damageCard(string $targetId, int $damage): void
    {
        $this->pushGameEvent(GameEventTypeEnum::DAMAGE_DEALT, ['targetId' => $targetId, 'damage' => $damage]);
    }

    public function getPlayerStateById(string $playerId): PlayerState
    {
        return $this->state->getPlayer($playerId);
    }

    public function stealCard(string $cardId, string $fromPlayerId, string $toPlayerId): void
    {
        $this->pushGameEvent(GameEventTypeEnum::CARD_STOLEN, [
            'cardId' => $cardId,
            'fromPlayerId' => $fromPlayerId,
            'toPlayerId' => $toPlayerId,
        ]);
    }
}
