<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\DecoyBombCard;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class DecoyBombCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return DecoyBombCard::class;
    }

    public function testPlayStealsCoinsFromOpponent(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState(10));

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::COINS_LOST, $events[0]->type);
        self::assertSame('2', $events[0]->data['playerId']);
        self::assertSame(3, $events[0]->data['amount']);

        self::assertSame(GameEventTypeEnum::COINS_GAINED, $events[1]->type);
        self::assertSame('1', $events[1]->data['playerId']);
        self::assertSame(3, $events[1]->data['amount']);
    }

    public function testPlayStealsOnlyWhatOpponentHas(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState(2));

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(2, $events[0]->data['amount']);
        self::assertSame(2, $events[1]->data['amount']);
    }

    private function buildState(int $opponentCoins): GameState
    {
        $player2 = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, '', [], [], $opponentCoins, new PlayArea());

        return new GameState($this->createPlayerState('1'), $player2, 1, 0, null, [
            'test_card' => new CardState('test_card', 'DecoyBomb', '1', []),
        ]);
    }
}
