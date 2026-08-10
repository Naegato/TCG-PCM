<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class MonkeyVillageCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public static CardRarityEnum $rarity = CardRarityEnum::EPIC;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

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

        foreach ($ownerState->playArea->monsterCards as $cardId) {
            $cardState = $gameContext->state->getCardState($cardId);

            $monkeyIds = GameUtils::getService('cards')->getCardsInGroup('monkey');
            if (null === $cardState || !\in_array($cardState->templateId, $monkeyIds, true)) {
                continue;
            }

            $currentBonusAttack = (int) ($cardState->values['bonusAttack'] ?? 0);

            $gameContext->pushGameEvent(GameEventTypeEnum::UPDATE_CARD_STATE, [
                'cardId' => $cardId,
                'stateToUpdate' => [
                    'bonusAttack' => $currentBonusAttack + $this->getValue(self::ATTACK_BUFF, true),
                ],
            ]);
        }
    }
}
