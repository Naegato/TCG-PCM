<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class DDTCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const HEALTH_POINTS = 20;
    private const ATTACK = 15;
    private const DODGE_CHANCE = 50;
    private const DAMAGE_REDUCTION = 5;

    public function getId(): string
    {
        return 'DDT';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DODGE_CHANCE, true),
            'value2' => $this->getValue(self::DAMAGE_REDUCTION, true),
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

    public static function getGroups(): array
    {
        return ['bloon'];
    }

    public function reduceDamage(GameContext $context, int $damage): int
    {
        if (0 === $context->randomIntBetween(0, 1)) {
            return $damage;
        }

        return max(0, $damage - self::DAMAGE_REDUCTION);
    }
}
