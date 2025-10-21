<?php

declare(strict_types=1);

namespace App\Game;

final class Player
{
    /**
     * @param AbstractCard[] $hand
     * @param AbstractCard[] $deck
     */
    public function __construct(
        private readonly string $name,
        private readonly int $score,
        private array $hand = [],
        private array $deck = [],
    ) {}

    public function drawCard(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            if (empty($this->deck)) {
                break;
            }

            $card = array_shift($this->deck);
            $this->hand[] = $card;
        }
    }

    public function getHandSize(): int
    {
        return count($this->hand);
    }
}
