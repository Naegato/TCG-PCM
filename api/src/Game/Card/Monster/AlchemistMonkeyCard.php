<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class AlchemistMonkeyCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public static CardRarityEnum $rarity = CardRarityEnum::EPIC;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const HEALTH_POINTS = 20;
    private const ATTACK = 6;
    private const ATTACK_BUFF = 6;

    public function getId(): string
    {
        return 'AlchemistMonkey';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::ATTACK_BUFF, true),
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

    public static function getGroups(): array
    {
        return ['monkey'];
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        $instanceId = $this->getInstanceId();
        $ownerId = $this->getOwnerId();

        if (null === $instanceId || null === $ownerId) {
            return;
        }

        if ($gameContext->isCurrentPlayer($ownerId)) {
            return;
        }

        $ownerState = $gameContext->getPlayerStateById($ownerId);
        $pool = array_values(array_filter($ownerState->playArea->monsterCards, static fn(string $cardId): bool => $cardId !== $instanceId));

        if ([] === $pool) {
            return;
        }

        $targetId = $gameContext->selectRandomCardIn($pool);
        $targetState = $gameContext->state->getCardState($targetId);

        if (null === $targetState) {
            return;
        }

        $currentBonus = (int) ($targetState->values['bonusAttack'] ?? 0);

        $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $targetId,
            'stateToUpdate' => [
                'bonusAttack' => $currentBonus + $this->getValue(self::ATTACK_BUFF, true),
            ],
        ]);
    }
}
