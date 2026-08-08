<?php

declare(strict_types=1);

namespace App\Game\Card\Character;

use App\Enum\CardRarityEnum;
use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;

abstract class AbstractCharacterCard extends AbstractCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::RARE;

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::CHARACTER;
    }

    abstract public function getHealthPoints(): int;

    public function getCost(): int
    {
        throw new \LogicException('Character cards do not have a cost');
    }
}
