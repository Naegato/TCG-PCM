<?php

declare(strict_types=1);

namespace App\Game\Card\Character;

use App\Game\Card\CardHelper;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class DataCenterCard extends AbstractCharacterCard implements TurnAwareInterface
{
    use BaseOnTurnTrait;

    private const TURN_DELAY = 1;
    private const SPAWN_QUANTITY = 1;

    private const SPAWNED_CARD_ID = 'DataMiner';

    public function getId(): string
    {
        return 'DataCenter';
    }

    public function getHealthPoints(): int
    {
        return 150;
    }

    public function getTurnDelay(): int
    {
        return $this->getValue(self::TURN_DELAY, true);
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'card' => self::SPAWNED_CARD_ID,
            'value' => $this->getValue(self::SPAWN_QUANTITY, true),
            'value2' => $this->getTurnDelay(),
        ]);
    }

    private function onTurnAction(GameContext $gameContext): void
    {
        $ownerId = $this->getOwnerId();
        if (null === $ownerId) {
            return;
        }

        for ($i = 1; $i <= $this->getValue(self::SPAWN_QUANTITY, true); $i++) {
            CardHelper::generatedAndPlay($gameContext, $ownerId, self::SPAWNED_CARD_ID, true);
        }
    }
}
