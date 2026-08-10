<?php

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Game\Card\Consumable\JusticeCard;
use App\Game\GameContext;
use App\Tests\Unit\Game\Card\CardTestCase;

final class JusticeCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return JusticeCard::class;
    }

    public function testCard()
    {
        $ctx = $this->createGameContext();
        $card = $this->getCard();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testPlayWithLessCards()
    {
        $ctx = $this->createGameContext();
        $state = $ctx->state->withUpdatedPlayer($ctx->getOpponentState()->withNewHandAndDeck(['a', 'b', 'c'], []));
        $ctx = new GameContext($state, '1');
        $card = $this->getCard();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);
    }

    public function testPlayWithMoreCards()
    {
        $ctx = $this->createGameContext();
        $state = $ctx->state->withUpdatedPlayer($ctx->getCurrentPlayerState()->withNewHandAndDeck(['a', 'b', 'c'], []));
        $ctx = new GameContext($state, '1');
        $card = $this->getCard();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }
}
