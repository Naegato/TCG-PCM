<?php

declare(strict_types=1);

namespace App\Game;

class GameContext
{
    private int $currentIndex = 0;

    /** @var Player[] */
    private array $players;

    /**
     * @param Player[] $players
     */
    public function __construct(array $players)
    {
        $this->players = $players;
    }

    public function makePlayerDrawCards(int $count): void
    {
        $player = $this->getCurrentPlayer();
        $player->drawCard($count);
    }

    public function getCurrentPlayer(): Player
    {
        return $this->players[$this->currentIndex];
    }

    public function nextPlayer(): void
    {
        $this->currentIndex = ($this->currentIndex + 1) % count($this->players);
    }
}
