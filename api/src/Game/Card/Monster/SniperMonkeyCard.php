<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;

final class SniperMonkeyCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::RARE;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 8;
    private const ATTACK = 25;

    public function getId(): string
    {
        return 'SniperMonkey';
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
        return ['monkey'];
    }
}
