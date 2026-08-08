<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;

final class CeramicBloonCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::RARE;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 40;
    private const ATTACK = 8;

    public function getId(): string
    {
        return 'CeramicBloon';
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
