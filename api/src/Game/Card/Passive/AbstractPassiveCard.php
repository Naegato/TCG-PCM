<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\GameContext;

abstract class AbstractPassiveCard extends AbstractCard
{
    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::PASSIVE;
    }

    public function onCardPlace(GameContext $gameContext): void
    {
        // no-op by default
    }
}
