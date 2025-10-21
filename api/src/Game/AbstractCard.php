<?php

declare(strict_types=1);

namespace App\Game;

abstract class AbstractCard
{
    abstract public function getName(): string;

    abstract public function getDescription(): string;

    public function getImage(): string
    {
        return \sprintf('%s.png', $this->getName());
    }

    abstract public function play(GameContext $context): void;

    public function onCardPlayed(self $card, GameContext $context): void
    {
        // Default implementation does nothing
    }

    public function onCardDrawn(self $card, GameContext $context): void
    {
        // Default implementation does nothing
    }
}
