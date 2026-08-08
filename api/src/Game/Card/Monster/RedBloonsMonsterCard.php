<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardSetEnum;

final class RedBloonsMonsterCard extends AbstractMonsterCard
{
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 10;
    private const ATTACK = 5;

    public function getId(): string
    {
        return 'Redbloons';
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
}
