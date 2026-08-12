<?php

declare(strict_types=1);

namespace App\Service\Game\Pipeline\Middleware;

use App\Enum\GameEventTypeEnum;
use App\Game\PlayerAction;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Service\Game\Pipeline\GamePipelineContext;
use App\Service\Game\Pipeline\GamePipelineStackInterface;

final class ConvertActionToEventMiddleware implements GameMiddlewareInterface
{
    public function handle(GamePipelineContext $gamePipelineContext, GamePipelineStackInterface $stack): GamePipelineContext
    {
        $action = $gamePipelineContext->getAction();
        $state = $gamePipelineContext->getGameState();

        $event = $this->handleAction($action, $state);

        $gamePipelineContext->setMainEvent($event);

        return $stack->next()->handle($gamePipelineContext, $stack);
    }

    private function handleAction(PlayerAction $action, GameState $state): GameEvent
    {
        return match ($action->actionId) {
            PlayerAction::PLAY_CARD => $this->playCardAction($action, $state),
            PlayerAction::END_TURN => $this->endTurnAction($action, $state),
            PlayerAction::ATTACK => $this->attackAction($action, $state),
            default => throw new \LogicException('Unknown action ID'),
        };
    }

    private function playCardAction(PlayerAction $action, GameState $state): GameEvent
    {
        $card = $action->payload['cardId'] ?? null;
        $data = $action->payload['data'] ?? [];

        if (!\is_string($card)) {
            throw new \InvalidArgumentException('cardId is required in payload and must be a string');
        }

        if (!\is_array($data)) {
            throw new \InvalidArgumentException('data must be an object payload');
        }

        return GameEvent::player(GameEventTypeEnum::CARD_PLAYED, [
            'playerId' => $action->authorId,
            'cardId' => $card,
            'data' => $data,
        ]);
    }

    private function endTurnAction(PlayerAction $action, GameState $state): GameEvent
    {
        return GameEvent::player(GameEventTypeEnum::TURN_ENDED, [
            'playerId' => $action->authorId,
        ]);
    }

    private function attackAction(PlayerAction $action, GameState $state): GameEvent
    {
        /** @var string $cardId */
        $cardId = $action->payload['cardId'] ?? null;

        if (!($targetId = $action->payload['targetId'] ?? null)) {
            throw new \InvalidArgumentException('targetId is required in payload');
        }

        return GameEvent::player(GameEventTypeEnum::ATTACKED, [
            'attackerId' => $cardId,
            'targetId' => $targetId,
        ]);
    }
}
