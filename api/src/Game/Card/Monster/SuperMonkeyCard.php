<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardHelper;
use App\Game\GameContext;
use App\Game\GameUtils;

final class SuperMonkeyCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::LEGENDARY;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 20;
    private const ATTACK = 20;
    private const BONUS_PER_MONKEY = 5;

    public static function getGroups(): array
    {
        return ['monkey'];
    }

    private int $playBonus = 0;

    public function getId(): string
    {
        return 'SuperMonkey';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::BONUS_PER_MONKEY, true),
        ]);
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK + $this->playBonus;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS + $this->playBonus;
    }

    public function onMonsterPlayed(GameContext $context): void
    {
        $ownerId = $this->getOwnerId();
        $instanceId = $this->getInstanceId();

        if (null === $ownerId || null === $instanceId) {
            return;
        }

        $ownerState = $context->getPlayerStateById($ownerId);
        $monkeysOnBoard = 1;

        $monkeyCards = CardHelper::getAllCardInGroups($context, 'monkey');
        foreach ($ownerState->playArea->monsterCards as $cardId) {
            if (!isset($monkeyCards[$cardId])) {
                continue;
            }

            $monkeysOnBoard++;
        }

        $this->playBonus = $this->getValue($monkeysOnBoard * self::BONUS_PER_MONKEY, true);

        $context->pushGameEvent(GameEventTypeEnum::UPDATE_CARD_STATE, [
            'cardId' => $instanceId,
            'stateToUpdate' => [
                'bonusAttack' => $this->playBonus,
            ],
        ]);
    }
}
