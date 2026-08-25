<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Game\Card\CardActions;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\GameContext;
use App\Game\GameUtils;

final class ChaosCard extends AbstractConsumableCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::RARE;
    }

    /**
     * @var string[]|null
     */
    private static ?array $monsterTemplatePool = null;

    /**
     * @var string[]|null
     */
    private static ?array $passiveTemplatePool = null;

    public function getId(): string
    {
        return 'Chaos';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $cardsInPlay = array_merge($context->state->player1->playArea->getAll(), $context->state->player2->playArea->getAll());

        if ([] === $cardsInPlay) {
            return;
        }

        foreach ($cardsInPlay as $cardId) {
            $cardState = $context->state->getCardState($cardId);
            if (null === $cardState) {
                continue;
            }

            $ownerState = $context->state->getPlayer($cardState->ownerId);
            $isMonster = \in_array($cardId, $ownerState->playArea->monsterCards, true);
            $replacementTemplateId = $this->pickRandomTemplateId($context, $isMonster);

            $context->discardCard($cardId);

            CardActions::generatedAndPlay($context, $cardState->ownerId, $replacementTemplateId, $isMonster);
        }
    }

    private function pickRandomTemplateId(GameContext $context, bool $monster): string
    {
        $pool = $monster ? $this->getMonsterTemplatePool() : $this->getPassiveTemplatePool();

        if ([] === $pool) {
            throw new \LogicException('Chaos template pool cannot be empty');
        }

        return $pool[$context->state->randomizer->randomBetweenInt(0, count($pool) - 1)];
    }

    /**
     * @return string[]
     */
    private function getMonsterTemplatePool(): array
    {
        return self::$monsterTemplatePool ??= GameUtils::getService('cards')->getCardsBy([
            'type' => AbstractMonsterCard::class,
        ]);
    }

    /**
     * @return string[]
     */
    private function getPassiveTemplatePool(): array
    {
        return self::$passiveTemplatePool ??= GameUtils::getService('cards')->getCardsBy([
            'type' => AbstractPassiveCard::class,
        ]);
    }
}
