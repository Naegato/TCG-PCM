<?php

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Passive\StackyStackitoCard;
use App\Game\GameContext;
use App\Game\State\GameEvent;
use App\Tests\Unit\Game\Card\CardTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class StackyStackitoCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return StackyStackitoCard::class;
    }

    public function testCard()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();
        $event = new GameEvent(0, GameEventTypeEnum::TURN_STARTED, 'game', ['playerId' => '1']);

        for ($i = 0; $i < 9; $i++) {
            $card->onTurnStart($event, $ctx);
        }
        $card->onTurnStart($event, $ctx = $this->createGameContext());
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
    }

    #[DataProvider('provideCoins')]
    public function testCardDamage(int $coins)
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $state = $state->withUpdatedPlayer($state->getPlayer('1')->withUpdatedCoins($coins));
        $ctx = new GameContext($state, '1');

        for ($i = 0; $i < 9; $i++) {
            $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        }
        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx = new GameContext($state, '1'));
        $events = $ctx->flushEvents();

        self::assertSame($coins, $events[0]->data['damage']);
    }

    public static function provideCoins(): \Generator
    {
        yield [0];
        yield [10];
        yield [100];
    }
}
