<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\MonsterCardState;
use App\Game\GameContext;

final class TheLostCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::UNCOMMON;
    public static CardSetEnum $serie = CardSetEnum::TBOI;

    private const HEALTH_POINTS = 1;
    private const ATTACK = 20;

    private bool $hasDodged = false;

    public function getId(): string
    {
        return 'TheLost';
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS;
    }

    public function setState(CardState $state): void
    {
        parent::setState($state);

        if ($state instanceof MonsterCardState) {
            $hasDodgedValue = $state->values['hasDodged'] ?? false;
            $this->hasDodged = \is_bool($hasDodgedValue) ? $hasDodgedValue : \filter_var($hasDodgedValue, \FILTER_VALIDATE_BOOL);
        }
    }

    public function reduceDamage(GameContext $context, int $damage): int
    {
        if ($this->hasDodged) {
            return $damage;
        }

        $this->hasDodged = true;
        $instanceId = $this->getInstanceId();

        if (null !== $instanceId) {
            $context->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
                'cardId' => $instanceId,
                'stateToUpdate' => [
                    'hasDodged' => true,
                ],
            ]);
        }

        return 0;
    }
}
