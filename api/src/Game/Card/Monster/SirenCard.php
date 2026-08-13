<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class SirenCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use BaseOnTurnTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const HEALTH_POINTS = 33;
    private const ATTACK = 7;
    private const TURN_DELAY = 2;

    public function getId(): string
    {
        return 'Siren';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::TURN_DELAY, true),
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

    private function onTurnAction(GameContext $context): void
    {
        $randomMonsterCardId = $this->pickRandomMonsterCardIdFromOpponent($context);

        if ('' === $randomMonsterCardId) {
            return;
        }

        $context->stealCard($randomMonsterCardId, $context->getOtherPlayerId($this->ownerId), $this->ownerId);
    }

    public function getTurnDelay(): int
    {
        return $this->getValue(self::TURN_DELAY, true);
    }

    private function pickRandomMonsterCardIdFromOpponent(GameContext $context): string
    {
        $pool = $context->getOpponentState()->playArea->monsterCards;

        if ([] === $pool) {
            return '';
        }

        return $pool[$context->state->randomizer->randomBetweenInt(0, count($pool) - 1)];
    }
}
