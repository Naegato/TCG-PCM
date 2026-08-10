<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\GameContext;

abstract class AbstractConsumableCard extends AbstractCard
{
    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::CONSUMABLE;
    }

    abstract public function play(GameContext $context, array $data = []): void;
}
