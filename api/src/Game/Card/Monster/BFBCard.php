<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;

final class BFBCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::EPIC;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 100;
    private const ATTACK = 20;

    public function getId(): string
    {
        return 'BFB';
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
