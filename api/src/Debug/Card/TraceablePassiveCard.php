<?php

declare(strict_types=1);

namespace App\Debug\Card;

use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\ComputedCardInterface;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Passive\AbstractPassiveCard;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceablePassiveCard extends AbstractPassiveCard implements TurnAwareInterface, CardAwareInterface, ComputedCardInterface, DeathAwareInterface
{
    /** @use TraceableCardTrait<AbstractPassiveCard> */
    use TraceableCardTrait;

    public static function create(parent $card, Stopwatch $stopwatch): static
    {
        $traceableCard = new static();
        $traceableCard->card = $card;
        $traceableCard->stopwatch = $stopwatch;

        return $traceableCard;
    }

    private function getEventName(string $method): string
    {
        return \sprintf('%s.%s', $this->getId(), $method);
    }
}
