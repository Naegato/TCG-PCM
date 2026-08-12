<?php

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Passive\Play2HurtCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class Play2HurtCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return Play2HurtCard::class;
    }

    public function testCard()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();
        $stub = $this->createStubCard();
        $stub->method('getOwnerId')->willReturn('target');

        $card->onCardPlayed($stub, $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('2', $events[0]->data['targetId']);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[1]->type);
        self::assertSame('target', $events[1]->data['targetId']);
    }
}
