<?php

declare(strict_types=1);

namespace App\Service\Game\Factory;

use App\Game\GameContext;
use App\Game\State\GameState;

final class ReplayableGameContextFactory implements GameContextFactoryInterface
{
    /**
     * @param array<int|float|string> $runtimesValues
     */
    public function __construct(
        private array $runtimesValues = [],
    ) {}

    public function createGameContext(GameState $gameState, string $playerId, ?int $parentEventId = null): GameContext
    {
        $context = new ReplayableGameContext($gameState, $playerId, $parentEventId);
        $context->setRuntimeValueProvider($this->getNextRolls(...));

        return $context;
    }

    private function getNextRolls(): int|float|string
    {
        return array_shift($this->runtimesValues) ?? throw new \BadMethodCallException('No more values available for replay.');
    }
}

class ReplayableGameContext extends GameContext
{
    /**
     * @var \Closure(): mixed
     */
    private \Closure $runtimeValuesProvider;

    /**
     * @param \Closure(): mixed $runtimeValueProvider
     */
    public function setRuntimeValueProvider(\Closure $runtimeValueProvider): void
    {
        $this->runtimeValuesProvider = $runtimeValueProvider;
    }

    public function runtimeValueEffect(mixed $value): mixed
    {
        $value = ($this->runtimeValuesProvider)();

        return parent::runtimeValueEffect($value);
    }
}
