<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Character;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Character\IsaacCard;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class IsaacCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return IsaacCard::class;
    }

    public function testGetId()
    {
        self::assertSame('Isaac', $this->getCard()->getId());
    }

    public function testGetHealthPoints()
    {
        /** @var IsaacCard $card */
        $card = $this->getCard();
        self::assertSame(180, $card->getHealthPoints());
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn()
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        // card owner is '1', but it's player2's turn
        $state = $state->withCurrentPlayer('2');
        $ctx = $this->createGameContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnTurnStartDamagesTargetInOpponentPool()
    {
        $card = $this->getCard();
        $player1State = $this->createPlayerState('1');
        // Opponent has an empty play area and a set character id, so the
        // random selection pool only contains the opponent's character card.
        $player2State = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, 'char2', [], [], 0, new PlayArea());
        $state = new GameState($player1State, $player2State, 1, 0, '1');
        $ctx = $this->createGameContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('char2', $events[0]->data['targetId']);
        self::assertSame(5, $events[0]->data['damage']);
    }
}
