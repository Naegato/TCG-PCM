<?php

declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\Enum\CardTypeEnum;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\GameContext;

class DummyCard extends AbstractConsumableCard
{
    public function getName(): string
    {
        return 'DummyCard';
    }

    public function getId(): string
    {
        return self::class;
    }

    public function getDescription(): string
    {
        return 'This is a dummy card for testing purposes.';
    }

    public function play(GameContext $context, array $data = []): void
    {
        // No operation for dummy card
    }

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }
}
