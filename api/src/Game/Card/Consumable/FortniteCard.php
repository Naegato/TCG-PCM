<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\MonsterCardState;
use App\Game\GameContext;
use App\Game\GameUtils;

final class FortniteCard extends AbstractConsumableCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::EPIC;

    private const VICTORY_ROYALE_HEALTH = 6;
    private const VICTORY_ROYALE_ATTACK = 7;

    public function getId(): string
    {
        return 'Fortnite';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'health' => $this->getValue(self::VICTORY_ROYALE_HEALTH, true),
            'attack' => $this->getValue(self::VICTORY_ROYALE_ATTACK, true),
        ]);
    }

    public function requiresTarget(): bool
    {
        return false;
    }

    public function play(GameContext $context, array $data = []): void
    {
        $monsterPool = $context->getMonsters();

        if ([] === $monsterPool) {
            throw new \InvalidArgumentException('Fortnite requires at least one monster in play');
        }

        $targetId = $context->selectRandomCardIn($monsterPool);
        $targetState = $context->state->getCardState($targetId);

        if (!$targetState instanceof MonsterCardState) {
            throw new \LogicException('Fortnite selected target is not a monster card');
        }

        foreach ($this->getAllActivePlayAreaCards($context) as $cardId) {
            if ($cardId === $targetId) {
                continue;
            }

            $context->discardCard($cardId);
        }

        $context->heal($this->getValue(self::VICTORY_ROYALE_HEALTH, true), $targetId);

        $currentBonusAttack = (int) ($targetState->values['bonusAttack'] ?? 0);
        $context->pushGameEvent(GameEventTypeEnum::UPDATE_CARD_STATE, [
            'cardId' => $targetId,
            'stateToUpdate' => [
                'bonusAttack' => $currentBonusAttack + $this->getValue(self::VICTORY_ROYALE_ATTACK, true),
            ],
        ]);
    }

    /**
     * @return string[]
     */
    private function getAllActivePlayAreaCards(GameContext $context): array
    {
        return array_merge($context->state->player1->playArea->getAll(), $context->state->player2->playArea->getAll());
    }
}
