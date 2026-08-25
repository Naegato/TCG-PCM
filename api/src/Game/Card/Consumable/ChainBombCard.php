<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardActions;
use App\Game\Card\CardState;
use App\Game\GameContext;
use App\Game\GameUtils;

final class ChainBombCard extends AbstractConsumableCard
{
    private const DAMAGE = 8;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'ChainBomb';
    }

    public function getImage(): string
    {
        return 'chainbomb.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $otherBombs = array_filter(
            CardActions::getAllCardInGroups($context, 'bomb'),
            fn(CardState $card): bool => $card->instanceId !== $this->getInstanceId(),
        );

        if ([] !== $otherBombs) {
            $target = array_values($otherBombs)[0];

            $context->pushGameEvent(GameEventTypeEnum::CARD_CONSUMED, [
                'cardId' => $target->instanceId,
                'playerId' => $target->ownerId,
            ]);

            $context->addCoins(1);
        }

        $context->attack($this->getValue(self::DAMAGE, true));
    }
}
