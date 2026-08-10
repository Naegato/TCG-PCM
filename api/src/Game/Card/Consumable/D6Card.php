<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardSetEnum;
use App\Game\GameContext;

final class D6Card extends AbstractConsumableCard
{
    public static CardSetEnum $serie = CardSetEnum::TBOI;

    public function getId(): string
    {
        return 'D6';
    }

    public function getImage(): string
    {
        return 'https://www.shutterstock.com/image-photo/red-die-on-white-six-260nw-27724336.jpg';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $context->drawCards($context->rollDice(6));
    }
}
