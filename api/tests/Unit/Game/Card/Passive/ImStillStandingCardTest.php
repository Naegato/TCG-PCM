<?php

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Passive\ImStillStandingCard;
use App\Game\GameContext;
use App\Tests\Unit\Game\Card\CardTestCase;

final class ImStillStandingCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return ImStillStandingCard::class;
    }

    public function testCard()
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $ctx = new GameContext($state->withUpdatedPlayer($state->player1->withUpdatedHealth(0)), '1');

        $card->onPlayerDeath($ctx, '1');
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame('1', $events[0]->data['targetId']);
        self::assertSame(3, $events[0]->data['amount']);
    }

    public function testCardRemoveAfterEffect()
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $ctx = new GameContext($state->withUpdatedPlayer($state->player1->withUpdatedHealth(0)), '1');

        $card->onPlayerDeath($ctx, '1');
        $event = $ctx->flushEvents()[1];

        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $event->type);
        self::assertSame('test_card', $event->data['cardId']);
    }
}
