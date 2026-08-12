<?php

declare(strict_types=1);

namespace App\Game\Card\Trait;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\GameContext;
use App\Game\State\GameEvent;

trait BaseOnTurnTrait
{
    private int $turnRemainingBeforeAction;

    use TurnAwareTrait;

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $this->turnRemainingBeforeAction--;

        if ($this->turnRemainingBeforeAction <= 0) {
            $this->beforeAction($gameContext);
            // @note If *someday* effects have
            // critical interactions on gamestate
            // we should change this X_X
            if (!$gameContext->lastActionHasBeenPrevented()) {
                $this->onTurnAction($gameContext);
            }

            $this->turnRemainingBeforeAction = $this->getTurnDelay();
        }

        $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $this->getInstanceId(),
            'stateToUpdate' => [
                'turnRemainingBeforeAction' => $this->turnRemainingBeforeAction,
            ],
        ]);
    }

    abstract public function getTurnDelay(): int;

    public function setState(CardState $state): void
    {
        parent::setState($state);

        $this->initFromState($state);
    }

    abstract private function onTurnAction(GameContext $gameContext): void;

    private function initFromState(CardState $state): void
    {
        $this->turnRemainingBeforeAction = $state->values['turnRemainingBeforeAction'] ?? $this->getTurnDelay();
    }
}
