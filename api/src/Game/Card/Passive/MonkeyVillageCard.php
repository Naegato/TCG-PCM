<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardActions;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class MonkeyVillageCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const ATTACK_BUFF = 2;

    public static function getGroups(): array
    {
        return ['monkey'];
    }

    public function getId(): string
    {
        return 'MonkeyVillage';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::ATTACK_BUFF, true),
        ]);
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $ownerId = $this->getOwnerId();

        if (null === $ownerId) {
            return;
        }

        $ownerState = $gameContext->getPlayerStateById($ownerId);
        $monkeyCards = CardActions::getAllCardInGroups($gameContext, 'monkey');

        foreach ($ownerState->playArea->monsterCards as $cardId) {
            $cardState = $gameContext->state->getCardState($cardId);

            if (null === $cardState || !isset($monkeyCards[$cardId])) {
                continue;
            }

            $currentBonusAttack = (int) ($cardState->values['bonusAttack'] ?? 0);

            $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
                'cardId' => $cardId,
                'stateToUpdate' => [
                    'bonusAttack' => $currentBonusAttack + $this->getValue(self::ATTACK_BUFF, true),
                ],
            ]);
        }
    }
}
