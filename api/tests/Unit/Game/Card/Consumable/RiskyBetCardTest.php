<?php

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Game\Card\Consumable\RiskyBetCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class RiskyBetCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return RiskyBetCard::class;
    }

    public function testSelfDamage()
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls(1);
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame('1', $events[0]->data['targetId']);
        self::assertSame(50, $events[0]->data['damage']);
    }

    public function testOpponentDamage()
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls(9);
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame('2', $events[0]->data['targetId']);
        self::assertSame(100, $events[0]->data['damage']);
    }
}
