<?php

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\CardEffectEnum;
use App\Game\Card\Consumable\ViciousStingerCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class ViciousStingerCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return ViciousStingerCard::class;
    }

    public function testCard()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->play($ctx, ['target' => 'a']);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(CardEffectEnum::POWER_BOOST->value, $events[0]->data['effect']);
        self::assertSame('a', $events[0]->data['cardId']);
        self::assertSame(1.5, $events[0]->data['effectValues']['value']);
    }
}
