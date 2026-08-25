<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Game\Card\CardActions;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class BombFactoryCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use BaseOnTurnTrait;

    private const TURN_DELAY = 1;

    private const SPAWNED_CARD_ID = 'TimeBomb';

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
        return 'BombFactory';
    }

    public function getImage(): string
    {
        return 'bombfactory.svg';
    }

    public function getTurnDelay(): int
    {
        return $this->getValue(self::TURN_DELAY, true);
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'card' => self::SPAWNED_CARD_ID,
            'value' => $this->getTurnDelay(),
        ]);
    }

    private function onTurnAction(GameContext $gameContext): void
    {
        $ownerId = $this->getOwnerId();

        if (null === $ownerId) {
            return;
        }

        CardActions::generatedAndPlay($gameContext, $ownerId, self::SPAWNED_CARD_ID);
    }
}
