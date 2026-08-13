<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class BlackBloonCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const HEALTH_POINTS = 15;
    private const ATTACK = 8;
    private const DAMAGE_REDUCTION = 8;

    public function getId(): string
    {
        return 'BlackBloon';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE_REDUCTION, true),
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
        return max(0, $damage - self::DAMAGE_REDUCTION);
    }
}
