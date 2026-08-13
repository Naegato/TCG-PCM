<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class BoomerangMonkeyCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const HEALTH_POINTS = 10;
    private const ATTACK = 20;
    private const SELF_DAMAGE_ON_ATTACK = 3;

    public function getId(): string
    {
        return 'BoomerangMonkey';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::SELF_DAMAGE_ON_ATTACK, true),
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
        return ['monkey'];
    }

    public function onAttack(GameContext $context): void
    {
        $instanceId = $this->getInstanceId();
        if (null === $instanceId) {
            return;
        }

        $context->damageCard($instanceId, $this->getValue(self::SELF_DAMAGE_ON_ATTACK, true));
    }
}
