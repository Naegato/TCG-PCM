<?php

declare(strict_types=1);

namespace App\Debug\GameContext;

use App\Game\GameContext;
use App\Game\State\GameState;
use App\Service\Game\Factory\GameContextFactoryInterface;

final class TraceableGameContextFactory implements GameContextFactoryInterface
{
    /**
     * @var DebugGameContext[]
     */
    private array $gameContexts = [];

    public function createGameContext(GameState $gameState, string $playerId, ?int $parentEventId = null): GameContext
    {
        $gameContext = new DebugGameContext($gameState, $playerId, $parentEventId);
        $this->gameContexts[] = $gameContext;

        return $gameContext;
    }

    /**
     * @return GameContext[]
     */
    public function getGameContexts(): array
    {
        return $this->gameContexts;
    }

    public function hasGameContexts(): bool
    {
        return [] !== $this->gameContexts;
    }
}
