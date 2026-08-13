<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class BananaFarmCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::RARE;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    private const COINS_GAIN = 1;

    public function getId(): string
    {
        return 'BananaFarm';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::COINS_GAIN, true),
        ]);
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $gameContext->addCoins($this->getValue(self::COINS_GAIN, true));
    }
}
