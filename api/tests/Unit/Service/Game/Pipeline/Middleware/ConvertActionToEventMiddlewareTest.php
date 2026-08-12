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
use App\Service\Game\Pipeline\Middleware\ConvertActionToEventMiddleware;
use PHPUnit\Framework\TestCase;

final class ConvertActionToEventMiddlewareTest extends TestCase
{
    public function testConvertPlayCard()
    {
        $sut = $this->getSut();
        $gamePipelineContext = new GamePipelineContext(new PlayerAction('userId', PlayerAction::PLAY_CARD, 'gameId', ['cardId' => 'cardId']));
        $state = new GameState(
            new PlayerState(new Player('userId', 'username'), 0, 0, '', ['cardId'], [], 0, new PlayArea()),
            $this->createStub(PlayerState::class),
            null,
            0,
            null,
            [
                'cardId' => '',
            ],
        );
        $gamePipelineContext->setGameState($state);

        $sut->handle($gamePipelineContext, new GamePipelineMiddlewareStack([]));

        $expectedEvent = GameEvent::player(GameEventTypeEnum::CARD_PLAYED, [
            'playerId' => 'userId',
            'cardId' => 'cardId',
            'data' => [],
        ]);

        self::assertEquals($expectedEvent, $gamePipelineContext->getMainEvent());
    }

    public function testConvertEndTurn()
    {
        $sut = $this->getSut();
        $gamePipelineContext = new GamePipelineContext(new PlayerAction('userId', PlayerAction::END_TURN, 'gameId', []));
        $state = new GameState(
            new PlayerState(new Player('userId', 'username'), 0, 0, '', [], [], 0, new PlayArea()),
            $this->createStub(PlayerState::class),
            null,
            0,
            null,
        );
        $gamePipelineContext->setGameState($state);

        $sut->handle($gamePipelineContext, new GamePipelineMiddlewareStack([]));

        $expectedEvent = GameEvent::player(GameEventTypeEnum::TURN_ENDED, [
            'playerId' => 'userId',
        ]);

        self::assertEquals($expectedEvent, $gamePipelineContext->getMainEvent());
    }

    public function testConvertAttack()
    {
        $sut = $this->getSut();
        $gamePipelineContext = new GamePipelineContext(new PlayerAction('userId', PlayerAction::ATTACK, 'gameId', [
            'cardId' => 'cardId',
            'targetId' => 'targetId',
        ]));
        $state = new GameState(
            new PlayerState(new Player('userId', 'username'), 0, 0, '', [], [], 0, new PlayArea([], ['cardId'])),
            $this->createStub(PlayerState::class),
            null,
            0,
            null,
        );
        $gamePipelineContext->setGameState($state);

        $sut->handle($gamePipelineContext, new GamePipelineMiddlewareStack([]));

        $expectedEvent = GameEvent::player(GameEventTypeEnum::ATTACKED, [
            'attackerId' => 'cardId',
            'targetId' => 'targetId',
        ]);

        self::assertEquals($expectedEvent, $gamePipelineContext->getMainEvent());
    }

    private function getSut(): ConvertActionToEventMiddleware
    {
        return new ConvertActionToEventMiddleware();
    }
}
