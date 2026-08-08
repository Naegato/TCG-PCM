<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class CamoBloonCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::COMMON;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 5;
    private const ATTACK = 5;
    private const DODGE_CHANCE = 50;

    public function getId(): string
    {
        return 'CamoBloon';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DODGE_CHANCE, true),
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
        if ($context->randomIntBetween(0, 100) < $this->getValue(self::DODGE_CHANCE, true)) {
            return $damage;
        }

        return max(0, $damage - 999);
    }
}
