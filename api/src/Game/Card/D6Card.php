<?php

declare(strict_types=1);

namespace App\Game\Card;

use App\Game\AbstractCard;
use App\Game\Dice;
use App\Game\GameContext;

final class D6Card extends AbstractCard
{
    public function getImage(): string
    {
        return 'https://www.shutterstock.com/image-photo/red-die-on-white-six-260nw-27724336.jpg';
    }

    public function getName(): string
    {
        return 'D6';
    }

    public function getDescription(): string
    {
        return 'Roll a six-sided dice and draw that many cards.';
    }

    public function play(GameContext $context): void
    {
        $context->makePlayerDrawCards(Dice::d6());
    }
}
