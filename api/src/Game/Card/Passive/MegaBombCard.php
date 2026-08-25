<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class MegaBombCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use BaseOnTurnTrait;

    private const TURN_DELAY = 3;
    private const DAMAGE = 40;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'MegaBomb';
    }

    public function getImage(): string
    {
        return 'megabomb.svg';
    }

    public function getTurnDelay(): int
    {
        return $this->getValue(self::TURN_DELAY, true);
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
            'value2' => $this->getTurnDelay(),
        ]);
    }

    private function onTurnAction(GameContext $gameContext): void
    {
        $gameContext->attack($this->getValue(self::DAMAGE, true));
    }
}
