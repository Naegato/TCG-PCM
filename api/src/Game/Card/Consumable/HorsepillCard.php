<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\GameContext;

final class HorsepillCard extends AbstractConsumableCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::EPIC;
    public static CardSetEnum $serie = CardSetEnum::TBOI;

    private const SELF_DAMAGE = 50;
    private const SELF_HEAL = 50;
    private const MONSTER_ATTACK_MAX = 99;
    private const MONSTER_ATTACK_ZERO = 0;

    public function getId(): string
    {
        return 'Horsepill';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $ownerId = $this->getOwnerId();
        if (null === $ownerId) {
            return;
        }

        $ownerState = $context->getPlayerStateById($ownerId);
        $ownerBoardCards = $ownerState->playArea->getAll();
        $ownerMonsters = $ownerState->playArea->monsterCards;
        $opponentId = $context->getOtherPlayerId($ownerId);
        $opponentHand = $context->getPlayerStateById($opponentId)->hand;

        $effects = [
            'self_damage',
            'self_heal',
        ];

        if ([] !== $ownerBoardCards) {
            $effects[] = 'discard_random_card';
        }

        if ([] !== $ownerMonsters) {
            $effects[] = 'monster_attack_99';
            $effects[] = 'monster_attack_0';
        }

        if ([] !== $ownerState->hand) {
            $effects[] = 'discard_owner_hand';
        }

        if ([] !== $opponentHand) {
            $effects[] = 'discard_opponent_hand';
        }

        $selectedEffect = $context->getRandomFromArray($effects);

        switch ($selectedEffect) {
            case 'self_damage':
                $context->attack(self::SELF_DAMAGE, $ownerId);

                return;

            case 'self_heal':
                $context->heal(self::SELF_HEAL, $ownerId);

                return;

            case 'discard_random_card':
                $targetId = $context->selectRandomCardIn($ownerBoardCards);
                $context->discardCard($targetId);

                return;

            case 'monster_attack_99':
                $targetId = $context->selectRandomCardIn($ownerMonsters);
                $this->setMonsterAttack($context, $targetId, self::MONSTER_ATTACK_MAX);

                return;

            case 'monster_attack_0':
                $targetId = $context->selectRandomCardIn($ownerMonsters);
                $this->setMonsterAttack($context, $targetId, self::MONSTER_ATTACK_ZERO);

                return;

            case 'discard_owner_hand':
                $this->discardHand($context, $ownerState->hand, $this->getInstanceId());

                return;

            case 'discard_opponent_hand':
                $this->discardHand($context, $opponentHand);

                return;

            default:
                throw new \LogicException('Unknown Horsepill effect');
        }
    }

    /**
     * @param string[] $hand
     */
    private function discardHand(GameContext $context, array $hand, ?string $excludedCardId = null): void
    {
        foreach ($hand as $cardId) {
            if ($excludedCardId === $cardId) {
                continue;
            }

            if (null === $context->state->getCardState($cardId)) {
                continue;
            }

            $context->discardCard($cardId);
        }
    }

    private function setMonsterAttack(GameContext $context, string $targetId, int $attack): void
    {
        $context->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $targetId,
            'stateToUpdate' => [
                'forcedAttack' => $attack,
            ],
        ]);
    }
}
