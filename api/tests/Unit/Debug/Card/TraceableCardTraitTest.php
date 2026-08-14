<?php

declare(strict_types=1);

namespace App\Tests\Unit\Debug\Card;

use App\Debug\Card\TraceableConsumableCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\Consumable\BananaCard;
use App\Game\Card\Consumable\DartCard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableCardTraitTest extends TestCase
{
    public function testTargetingCardKeepsRequiresTargetAndTargetType(): void
    {
        $traceableCard = TraceableConsumableCard::create(new DartCard(), new Stopwatch());

        self::assertTrue($traceableCard->requiresTarget());
        self::assertSame(
            AbstractConsumableCard::TARGET_TYPE_MONSTER | AbstractConsumableCard::TARGET_OPPONENT_CARDS | AbstractConsumableCard::TARGET_TYPE_CHARACTER,
            $traceableCard->getTargetType(),
        );
    }

    public function testNonTargetingCardStillReportsNoTarget(): void
    {
        $traceableCard = TraceableConsumableCard::create(new BananaCard(), new Stopwatch());

        self::assertFalse($traceableCard->requiresTarget());
        self::assertSame(AbstractConsumableCard::TARGET_TYPE_NONE, $traceableCard->getTargetType());
    }
}
