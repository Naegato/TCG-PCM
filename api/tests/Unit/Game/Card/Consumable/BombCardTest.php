<?php

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Game\Card\Consumable\BombCard;
use App\Game\State\PlayArea;
use App\Tests\Unit\Game\Card\CardTestCase;

final class BombCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return BombCard::class;
    }

    public function testCard()
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $state = $state->withUpdatedPlayer($state->player1->withPlayArea(new PlayArea([], ['a', 'b'])));
        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
    }
}
