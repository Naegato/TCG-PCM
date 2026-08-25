<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardHelper;
use App\Game\GameContext;
use App\Game\GameUtils;

final class TrollBombCard extends AbstractConsumableCard
{
    private const MINOR_DAMAGE = 1;

    private const JACKPOT_ROLL = 10;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::RARE;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'TrollBomb';
    }

    public function getImage(): string
    {
        return 'trollbomb.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::MINOR_DAMAGE, true),
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $roll = $context->rollDice(self::JACKPOT_ROLL);

        if ($roll < self::JACKPOT_ROLL) {
            $context->attack($this->getValue(self::MINOR_DAMAGE, true));

            return;
        }

        foreach (CardHelper::getAllCardInGroups($context, 'bomb') as $bomb) {
            $context->pushGameEvent(GameEventTypeEnum::CARD_CONSUMED, [
                'cardId' => $bomb->instanceId,
                'playerId' => $bomb->ownerId,
            ]);
        }
    }
}
