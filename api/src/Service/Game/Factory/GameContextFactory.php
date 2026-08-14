<?php

declare(strict_types=1);

namespace App\Service\Game\Factory;

use App\Game\GameContext;
use App\Game\State\GameState;

class GameContextFactory implements GameContextFactoryInterface
{
    public function createGameContext(GameState $gameState, string $playerId, ?int $parentEventId = null): GameContext
    {
        return new GameContext($gameState, $playerId, $parentEventId);
    }
}
