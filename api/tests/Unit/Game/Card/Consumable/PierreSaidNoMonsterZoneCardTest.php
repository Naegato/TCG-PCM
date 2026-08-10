<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Game\Card\CardState;
use App\Game\Card\Consumable\PierreSaidNoMonsterZone;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class PierreSaidNoMonsterZoneCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return PierreSaidNoMonsterZone::class;
    }

    public function testOnCardPlace()
    {
        $gameState = new GameState(
            new PlayerState(
                new Player('player1', 'Player 1'),
                30,
                30,
                '',
                [],
                [],
                1,
                new PlayArea([], [
                    'monster1',
                    'monster2',
                ]),
            ),
            new PlayerState(
                new Player('player2', 'Player 2'),
                30,
                30,
                '',
                [],
                [],
                1,
                new PlayArea([], [
                    'monster3',
                ]),
            ),
            1,
            0,
            null,
            [
                'monster1' => new CardState('monster1', DummyCard::class, '1', []),
                'monster2' => new CardState('monster2', DummyCard::class, '1', []),
                'monster3' => new CardState('monster3', DummyCard::class, '1', []),
            ],
        );
        $ctx = new GameContext($gameState, 'player1');
        $card = $this->getCard();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);
    }
}
