<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Game\AbstractCard;
use App\Game\GameContext;

final class DummyCard extends AbstractCard
{
    public function getName(): string
    {
        return 'DummyCard';
    }

    public function getDescription(): string
    {
        return 'This is a dummy card for testing purposes.';
    }

    public function play(GameContext $context): void
    {
        // No operation for dummy card
    }
}
