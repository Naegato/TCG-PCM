<?php

namespace App\Game\Card\Character;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\State\GameEvent;

final class NecromancianCard extends AbstractCharacterCard implements TurnAwareInterface
{
    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    use TurnAwareTrait;

    public function getId(): string
    {
        return 'Necromancian';
    }

    public function getHealthPoints(): int
    {
        return 150;
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $owner = $gameContext->state->getPlayer($this->getOwnerId());
        $pile = $owner->discardPile;

        if (count($pile) > 0) {
            $index = $gameContext->randomIntBetween(1, count($pile));
            $cardId = array_keys($pile)[$index - 1];

            $gameContext->pushGameEvent(GameEventTypeEnum::CARD_REDRAWN, [
                'playerId' => $this->getOwnerId(),
                'cardId' => $cardId,
            ]);
        }
    }
}
