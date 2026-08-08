<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardSetEnum;

final class ClottyCard extends AbstractMonsterCard
{
    public static CardSetEnum $serie = CardSetEnum::TBOI;

    private const HEALTH_POINTS = 7;
    private const ATTACK = 7;

    public function getId(): string
    {
        return 'Clotty';
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
        return ['clotty'];
    }
}
