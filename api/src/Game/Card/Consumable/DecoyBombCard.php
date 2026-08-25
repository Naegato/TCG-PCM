<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class DecoyBombCard extends AbstractConsumableCard
{
    private const STOLEN_COINS = 3;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::COMMON;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'DecoyBomb';
    }

    public function getImage(): string
    {
        return 'decoybomb.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::STOLEN_COINS, true),
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $opponentState = $context->getOpponentState();
        $stolen = min($this->getValue(self::STOLEN_COINS, true), $opponentState->coins);

        $context->setCoins($opponentState->coins - $stolen, $context->getOpponent()->id);
        $context->addCoins($stolen, $this->getOwnerId());
    }
}
