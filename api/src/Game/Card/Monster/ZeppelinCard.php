<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class ZeppelinCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::LEGENDARY;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

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
        foreach ($context->getMonsters() as $cardId) {
            $cardState = $context->state->getCardState($cardId);

            $bloonIds = GameUtils::getService('cards')->getCardsInGroup('bloon');
            if (null === $cardState || !\in_array($cardState->templateId, $bloonIds, true)) {
                continue;
            }

            $currentBonusAttack = (int) ($cardState->values['bonusAttack'] ?? 0);

            $context->pushGameEvent(GameEventTypeEnum::UPDATE_CARD_STATE, [
                'cardId' => $cardId,
                'stateToUpdate' => [
                    'bonusAttack' => $currentBonusAttack + $this->getValue(self::BUFF_AMOUNT, true),
                ],
            ]);

            $context->heal($this->getValue(self::BUFF_AMOUNT, true), $cardId);
        }
    }
}
