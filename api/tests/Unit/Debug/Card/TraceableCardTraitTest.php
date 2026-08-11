<?php

declare(strict_types=1);

namespace App\Tests\Unit\Debug\Card;

use App\Debug\Card\TraceableConsumableCard;
use App\Enum\CardTargetTypeEnum;
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
        self::assertSame(CardTargetTypeEnum::MONSTER, $traceableCard->getTargetType());
    }

    public function testNonTargetingCardStillReportsNoTarget(): void
    {
        $traceableCard = TraceableConsumableCard::create(new BananaCard(), new Stopwatch());

        self::assertFalse($traceableCard->requiresTarget());
        self::assertNull($traceableCard->getTargetType());
    }
}
