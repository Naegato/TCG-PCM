<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\MonsterCardState;
use App\Game\GameContext;

final class NinjaMonkeyCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const HEALTH_POINTS = 8;
    private const ATTACK = 15;

    private bool $hasDodged = false;

    public function getId(): string
    {
        return 'NinjaMonkey';
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

    public function setState(CardState $state): void
    {
        parent::setState($state);

        if ($state instanceof MonsterCardState) {
            $this->hasDodged = true === ($state->values['hasDodged'] ?? false);
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
