<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\GameContext;

final class PillsCard extends AbstractConsumableCard
{
    public static CardSetEnum $serie = CardSetEnum::TBOI;

    private const SELF_DAMAGE = 5;
    private const SELF_HEAL = 10;
    private const MONSTER_DAMAGE_BUFF = 5;
    private const MONSTER_HEAL = 5;
    private const MONSTER_DAMAGE_DEBUFF = 3;
    private const MONSTER_DAMAGE = 3;

    public function getId(): string
    {
        return 'Pills';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $ownerId = $this->getOwnerId();
        if (null === $ownerId) {
            return;
        }

        $ownerMonsters = $context->getPlayerStateById($ownerId)->playArea->monsterCards;

        $effects = [
            'self_damage',
            'self_heal',
        ];

        if ([] !== $ownerMonsters) {
            $effects = [
                ...$effects,
                'monster_damage_buff',
                'monster_heal',
                'monster_damage_debuff',
                'monster_damage',
            ];
        }

        $selectedEffect = $context->getRandomFromArray($effects);

        switch ($selectedEffect) {
            case 'self_damage':
                $context->attack(self::SELF_DAMAGE, $ownerId);

                return;

            case 'self_heal':
                $context->heal(self::SELF_HEAL, $ownerId);

                return;

            case 'monster_damage_buff':
                $targetId = $context->selectRandomCardIn($ownerMonsters);
                $this->updateMonsterAttackBonus($context, $targetId, self::MONSTER_DAMAGE_BUFF);

                return;

            case 'monster_heal':
                $targetId = $context->selectRandomCardIn($ownerMonsters);
                $context->heal(self::MONSTER_HEAL, $targetId);

                return;

            case 'monster_damage_debuff':
                $targetId = $context->selectRandomCardIn($ownerMonsters);
                $this->updateMonsterAttackBonus($context, $targetId, -self::MONSTER_DAMAGE_DEBUFF);

                return;

            case 'monster_damage':
                $targetId = $context->selectRandomCardIn($ownerMonsters);
                $context->damageCard($targetId, self::MONSTER_DAMAGE);

                return;

            default:
                throw new \LogicException('Unknown Pillules effect');
        }
    }

    private function updateMonsterAttackBonus(GameContext $context, string $targetId, int $delta): void
    {
        $targetState = $context->state->getCardState($targetId);

        if (null === $targetState) {
            return;
        }

        $currentBonus = (int) ($targetState->values['bonusAttack'] ?? 0);

        $context->pushGameEvent(GameEventTypeEnum::UPDATE_CARD_STATE, [
            'cardId' => $targetId,
            'stateToUpdate' => [
                'bonusAttack' => $currentBonus + $delta,
            ],
        ]);
    }
}
