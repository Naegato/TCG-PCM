<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game;

use App\Enum\GameEventTypeEnum;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use PHPUnit\Framework\TestCase;

final class GameContextTest extends TestCase
{
    public function testFlushEvents()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->drawCards(2);
        $ctx->attack(2);

        $events = $ctx->flushEvents();

        self::assertCount(3, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::CARD_DRAWN, GameEvent::GAME_EVENT, [
            'playerId' => '1',
        ]), $events[0]);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::CARD_DRAWN, GameEvent::GAME_EVENT, [
            'playerId' => '1',
        ]), $events[1]);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::DAMAGE_DEALT, GameEvent::GAME_EVENT, [
            'targetId' => '2',
            'damage' => 2,
        ]), $events[2]);
        self::assertEmpty($ctx->flushEvents());
    }

    public function testDrawCards()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->drawCards(2);

        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::CARD_DRAWN, GameEvent::GAME_EVENT, [
            'playerId' => '1',
        ]), $events[0]);
    }

    public function testDrawCardsOtherPlayer()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->drawCards(2, '2');

        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::CARD_DRAWN, GameEvent::GAME_EVENT, [
            'playerId' => '2',
        ]), $events[0]);
    }

    public function testAttack()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->attack(2);

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::DAMAGE_DEALT, GameEvent::GAME_EVENT, [
            'targetId' => '2',
            'damage' => 2,
        ]), $events[0]);
    }

    public function testAttackSamePlayer()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->attack(2, '1');

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::DAMAGE_DEALT, GameEvent::GAME_EVENT, [
            'targetId' => '1',
            'damage' => 2,
        ]), $events[0]);
    }

    public function testPushGameEvent()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->pushGameEvent(GameEventTypeEnum::TURN_STARTED, ['turn' => 1]);

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::TURN_STARTED, GameEvent::GAME_EVENT, [
            'turn' => 1,
        ]), $events[0]);
    }

    public function testGetOpponent()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $opponent = $ctx->getOpponent();

        self::assertEquals(new Player('2', 'Player 2'), $opponent);
    }

    public function testGetOpponentWithOtherPlayer()
    {
        $ctx = new GameContext($this->getGameState(), '2');

        $opponent = $ctx->getOpponent();

        self::assertEquals(new Player('1', 'Player 1'), $opponent);
    }

    public function testRollDice()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $result = $ctx->rollDice(6);

        self::assertGreaterThanOrEqual(1, $result);
        self::assertLessThanOrEqual(6, $result);
    }

    public function testGetOneRandomCard()
    {
        $gameState = new GameState(
            new PlayerState(
                player: new Player('1', 'Player 1'),
                healthPoints: 30,
                maxHealthPoints: 30,
                characterCardId: 'characterCardId',
                hand: [],
                drawPile: [],
                coins: 1,
                playArea: new PlayArea(),
            ),
            new PlayerState(
                player: new Player('2', 'Player 2'),
                healthPoints: 30,
                maxHealthPoints: 30,
                characterCardId: 'characterCardId',
                hand: [],
                drawPile: [],
                coins: 1,
                playArea: new PlayArea(),
            ),
            null,
            0,
            null,
            [],
        );
        $ctx = new GameContext($gameState, '1');

        $card = $ctx->getOneRandomCard(null);

        self::assertSame('characterCardId', $card);
    }

    public function testGetOneRandomCardWithPlayerId()
    {
        $gameState = new GameState(
            new PlayerState(
                player: new Player('1', 'Player 1'),
                healthPoints: 30,
                maxHealthPoints: 30,
                characterCardId: 'characterCardId1',
                hand: [],
                drawPile: [],
                coins: 1,
                playArea: new PlayArea(),
            ),
            new PlayerState(
                player: new Player('2', 'Player 2'),
                healthPoints: 30,
                maxHealthPoints: 30,
                characterCardId: 'characterCardId2',
                hand: [],
                drawPile: [],
                coins: 1,
                playArea: new PlayArea(),
            ),
            null,
            0,
            null,
            [],
        );
        $ctx = new GameContext($gameState, '1');

        $card = $ctx->getOneRandomCard('1');

        self::assertSame('characterCardId1', $card);
    }

    public function testSetCoinsGained()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->setCoins(4);

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::COINS_GAINED, GameEvent::GAME_EVENT, [
            'playerId' => '1',
            'amount' => 3,
        ]), $events[0]);
    }

    public function testSetCoinsLost()
    {
        $ctx = new GameContext($this->getGameState(), '1');

        $ctx->setCoins(0);

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::COINS_LOST, GameEvent::GAME_EVENT, [
            'playerId' => '1',
            'amount' => 1,
        ]), $events[0]);
    }

    private function getGameState(): GameState
    {
        return new GameState(
            new PlayerState(
                player: new Player('1', 'Player 1'),
                healthPoints: 30,
                maxHealthPoints: 30,
                characterCardId: '',
                hand: [],
                drawPile: [],
                coins: 1,
                playArea: new PlayArea(),
            ),
            new PlayerState(
                player: new Player('2', 'Player 2'),
                healthPoints: 30,
                maxHealthPoints: 30,
                characterCardId: '',
                hand: [],
                drawPile: [],
                coins: 1,
                playArea: new PlayArea(),
            ),
            null,
            0,
        );
    }
}
