<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Game\State;

use App\Enum\GameEventTypeEnum;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\GameStateRebuilder;
use App\Service\Game\State\GameEventRepositoryInterface;
use App\Service\Game\State\GameStateProvider;
use App\Service\Game\State\GameStateRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GameStateProviderTest extends TestCase
{
    public function testProvide()
    {
        $sut = new GameStateProvider(
            $this->createStub(GameStateRepositoryInterface::class),
            $this->createStub(GameEventRepositoryInterface::class),
            $this->createStub(GameStateRebuilder::class),
        );
        $gameState = $sut->get('gameId');

        self::assertNull($gameState);
    }

    public function testProvideWithExistingGameState()
    {
        $gameState = new GameState(
            new PlayerState(new Player('', ''), 0, 0, '', [], [], 0, new PlayArea()),
            new PlayerState(new Player('', ''), 0, 0, '', [], [], 0, new PlayArea()),
            null,
            0,
            '',
        );
        $repo = $this->createStub(GameStateRepositoryInterface::class);
        $repo->method('get')->willReturn($gameState);
        $sut = new GameStateProvider($repo, $this->createStub(GameEventRepositoryInterface::class), $this->createStub(GameStateRebuilder::class));
        $result = $sut->get('gameId');

        self::assertSame($gameState, $result);
    }

    public function testProvideUpToDate()
    {
        $gameState = new GameState(
            new PlayerState(new Player('', ''), 0, 0, '', [], [], 0, new PlayArea()),
            new PlayerState(new Player('', ''), 0, 0, '', [], [], 0, new PlayArea()),
            null,
            0,
            '',
        );
        $event = new GameEvent(2, GameEventTypeEnum::ATTACKED, 'playerEvent', []);
        $repo = $this->createStub(GameStateRepositoryInterface::class);
        $repo->method('get')->willReturn($gameState);
        $eventRepo = $this->createStub(GameEventRepositoryInterface::class);
        $eventRepo->method('getEventsSince')->willReturn([$event]);
        $rebuilder = $this->createMock(GameStateRebuilder::class);
        $rebuilder->expects(self::once())->method('rebuild')->with($gameState, [$event])->willReturn($gameState);
        $sut = new GameStateProvider($repo, $eventRepo, $rebuilder);
        $result = $sut->get('gameId');
    }
}
