<?php

declare(strict_types=1);

namespace App\Debug\Card;

use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\Interface\ComputedCardInterface;
use App\Game\GameContext;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableConsumableCard extends AbstractConsumableCard implements ComputedCardInterface
{
    /** @use TraceableCardTrait<AbstractConsumableCard> */
    use TraceableCardTrait;

    public static function create(parent $card, Stopwatch $stopwatch): static
    {
        $traceableCard = new static();
        $traceableCard->card = $card;
        $traceableCard->stopwatch = $stopwatch;

        return $traceableCard;
    }

    public function play(GameContext $context, array $data = []): void
    {
        $this->stopwatch->start($id = $this->getEventName('play'), self::STOPWATCH_CATEGORY);

        $this->methodCalled[] = __METHOD__;
        $this->card->play($context, $data);

        $this->stopwatch->stop($id);
    }
}
