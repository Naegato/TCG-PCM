<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class HoneyBeeCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    private const HEALTH_POINTS = 15;
    private const ATTACK = 5;
    private const CHARACTER_REGEN_ON_ATTACK = 5;

    public function getId(): string
    {
        return 'HoneyBee';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::CHARACTER_REGEN_ON_ATTACK, true),
        ]);
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS;
    }

    public function onAttack(GameContext $context): void
    {
        $context->heal($this->getValue(self::CHARACTER_REGEN_ON_ATTACK, true), $this->getOwnerId());
    }
}
