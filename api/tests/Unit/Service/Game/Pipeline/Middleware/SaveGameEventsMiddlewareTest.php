<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Game\Pipeline\Middleware;

use App\Enum\GameEventTypeEnum;
use App\Game\Player;
use App\Game\PlayerAction;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\Pipeline\GamePipelineContext;
use App\Service\Game\Pipeline\GamePipelineMiddlewareStack;
use App\Service\Game\Pipeline\Middleware\SaveGameEventsMiddleware;
use App\Service\Game\ResolutionResult;
use App\Service\Game\State\GameEventRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveGameEventsMiddlewareTest extends TestCase
{
    public function testHandle()
    {
        $repo = $this->createMock(GameEventRepositoryInterface::class);
        $repo
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturn(new GameEvent(1, GameEventTypeEnum::CARD_PLAYED, '', []));
        $middleware = new SaveGameEventsMiddleware($repo);
        $gpc = new GamePipelineContext(new PlayerAction('', '', 'gameId', []));
        $result = new ResolutionResult(
            [
                GameEvent::player(GameEventTypeEnum::CARD_PLAYED, []),
                GameEvent::game(GameEventTypeEnum::CARD_RUNTIME_VALUE, []),
                GameEvent::game(GameEventTypeEnum::DAMAGE_DEALT, []),
            ],
            new GameState(
                new PlayerState(new Player('1', 'otherUsername'), 0, 0, '', [], [], 0, new PlayArea()),
                new PlayerState(new Player('2', 'otherUsername'), 0, 0, '', [], [], 0, new PlayArea()),
                null,
                0,
            ),
        );
        $gpc->setResolutionResult($result);

        $middleware->handle($gpc, new GamePipelineMiddlewareStack([]));
    }
}
