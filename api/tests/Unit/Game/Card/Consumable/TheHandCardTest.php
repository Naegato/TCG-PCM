<?php

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\TheHandCard;
use App\Game\State\PlayArea;
use App\Tests\Unit\Game\Card\CardTestCase;

final class TheHandCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return TheHandCard::class;
    }

    public function testCard()
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $state = $state->withUpdatedPlayer($state->player2->withPlayArea(new PlayArea(['card'])));
        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[0]->type);
        self::assertSame('card', $events[0]->data['cardId']);
    }
}
