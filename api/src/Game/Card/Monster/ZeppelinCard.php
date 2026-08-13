<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardHelper;
use App\Game\GameContext;
use App\Game\GameUtils;

final class ZeppelinCard extends AbstractMonsterCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::LEGENDARY;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const HEALTH_POINTS = 120;
    private const ATTACK = 12;
    private const BUFF_AMOUNT = 10;

    public function getId(): string
    {
        return 'Zeppelin';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::BUFF_AMOUNT, true),
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

    public function onMonsterPlayed(GameContext $context): void
    {
        $bloonCards = CardHelper::getAllCardInGroups($context, 'bloon');
        foreach ($context->getMonsters() as $cardId) {
            $cardState = $context->state->getCardState($cardId);

            if (null === $cardState || !isset($bloonCards[$cardId])) {
                continue;
            }

            $currentBonusAttack = (int) ($cardState->values['bonusAttack'] ?? 0);

            $context->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
                'cardId' => $cardId,
                'stateToUpdate' => [
                    'bonusAttack' => $currentBonusAttack + $this->getValue(self::BUFF_AMOUNT, true),
                ],
            ]);

            $context->heal($this->getValue(self::BUFF_AMOUNT, true), $cardId);
        }
    }
}
