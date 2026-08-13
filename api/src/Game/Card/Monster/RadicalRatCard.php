<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class RadicalRatCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const HEALTH_POINTS = 11;
    private const ATTACK = 10;
    private const BOMB_DAMAGE = 10;

    public function getId(): string
    {
        return 'RadicalRat';
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS;
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::BOMB_DAMAGE, true),
        ]);
    }

    public function onMonsterPlayed(GameContext $context): void
    {
        $this->damageAllOpponents($context);
    }

    public function onMonsterDeath(GameContext $context): void
    {
        $this->damageAllOpponents($context);
    }

    private function damageAllOpponents(GameContext $context): void
    {
        if (null === ($ownerId = $this->getOwnerId())) {
            return;
        }

        $opponentState = $context->getPlayerStateById($context->getOtherPlayerId($ownerId));

        foreach ([...$opponentState->playArea->monsterCards, $opponentState->characterCardId] as $targetId) {
            $context->damageCard($targetId, $this->getValue(self::BOMB_DAMAGE, true));
        }
    }
}
