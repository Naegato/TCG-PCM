<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardEffectEnum;
use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\CardState;
use App\Game\Card\Effect\PowerBoostEffect;
use App\Game\Card\MonsterCardState;
use App\Game\GameContext;

abstract class AbstractMonsterCard extends AbstractCard
{
    protected int $currentHealthPoints;

    protected bool $canAttack = true;

    protected int $bonusAttack = 0;
    protected ?int $forcedAttack = null;

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }

    public function getAttack(): int
    {
        if (null !== $this->forcedAttack) {
            return max(0, $this->forcedAttack);
        }

        $value = $this->getValue($this->getBaseAttack() + $this->bonusAttack, true);

        /** @var PowerBoostEffect|null $boost */
        if ($boost = $this->getEffect(CardEffectEnum::POWER_BOOST)) {
            $value *= $boost->getValue();
        }

        return max(0, (int) $value);
    }

    abstract public function getBaseAttack(): int;

    abstract public function getHealPoints(): int;

    public function setState(CardState $state): void
    {
        parent::setState($state);

        if (!$state instanceof MonsterCardState) {
            return;
        }

        $this->currentHealthPoints = $state->currentHealthPoints;
        $this->canAttack = $state->canAttack;
        $this->bonusAttack = (int) ($state->values['bonusAttack'] ?? 0);
        $this->forcedAttack = isset($state->values['forcedAttack']) ? (int) $state->values['forcedAttack'] : null;
    }

    public function onMonsterPlayed(GameContext $context): void
    {
        // no-op
    }

    public function onMonsterDeath(GameContext $context): void
    {
        // no-op
    }

    public function onAttack(GameContext $context): void
    {
        // no-op
    }

    public function canAttack(): bool
    {
        return $this->canAttack;
    }

    public function reduceDamage(GameContext $context, int $damage): int
    {
        return $damage;
    }

    public function getCurrentHealthPoints(): int
    {
        return $this->currentHealthPoints;
    }
}
