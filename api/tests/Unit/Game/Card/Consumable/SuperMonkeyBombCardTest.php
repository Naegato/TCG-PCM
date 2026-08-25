<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\SuperMonkeyBombCard;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class SuperMonkeyBombCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return SuperMonkeyBombCard::class;
    }

    public function testPlayDamagesAllMonsters(): void
    {
        $card = $this->getCard();

        $player1 = new PlayerState(new Player('1', 'Player 1', 67), 30, 30, '', [], [], 0, new PlayArea([], ['monster1']));
        $player2 = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, '', [], [], 0, new PlayArea([], ['monster2']));

        $state = new GameState($player1, $player2, 1, 0);
        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('monster1', $events[0]->data['targetId']);
        self::assertSame(15, $events[0]->data['damage']);

        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[1]->type);
        self::assertSame('monster2', $events[1]->data['targetId']);
        self::assertSame(15, $events[1]->data['damage']);
    }
}
