<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardSetEnum;

final class DartMonkeyCard extends AbstractMonsterCard
{
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 5;
    private const ATTACK = 10;

    public function getId(): string
    {
        return 'DartMonkey';
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
