<?php

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class StackyStackitoCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use BaseOnTurnTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    private const DELAY = 1;

    private int $delay = self::DELAY;

    public function getId(): string
    {
        return 'StackyStackito';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getTurnDelay(),
        ]);
    }

    private function onTurnAction(GameContext $gameContext): void
    {
        $damage = $gameContext->state->getPlayer($this->getOwnerId())->coins;
        $gameContext->attack($damage);
    }

    public function getTurnDelay(): int
    {
        return $this->getValue($this->delay, true);
    }
}
