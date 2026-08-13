<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\GameContext;

abstract class AbstractConsumableCard extends AbstractCard
{
    public const TARGET_TYPE_NONE = 0;

    public const TARGET_TYPE_MONSTER = 1;

    public const TARGET_TYPE_PASSIVE = 2;

    public const TARGET_TYPE_CHARACTER = 4;

    public const TARGET_SELF_CARDS = 8;

    public const TARGET_OPPONENT_CARDS = 16;

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::CONSUMABLE;
    }

    public function requiresTarget(): bool
    {
        return self::TARGET_TYPE_NONE !== $this->getTargetType();
    }

    public function getTargetType(): int
    {
        return self::TARGET_TYPE_NONE;
    }

    abstract public function play(GameContext $context, array $data = []): void;
}
