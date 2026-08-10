<?php

namespace App\Game\Card\Consumable;

use App\Game\GameContext;

final class TheHandCard extends AbstractConsumableCard
{
    public function getId(): string
    {
        return 'TheHand';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $cardPool = $context->state->getOtherPlayerState()->playArea->passiveCards;
        $cardId = $context->selectRandomCardIn($cardPool);

        $context->discardCard($cardId);
    }
}
