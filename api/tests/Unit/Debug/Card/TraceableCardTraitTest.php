<?php

declare(strict_types=1);

namespace App\Tests\Unit\Debug\Card;

use App\Debug\Card\TraceableCardFactory;
use App\Game\Card\CardState;
use App\Service\Game\CardFactory;
use App\Tests\Resources\MockCardRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableCardTraitTest extends TestCase
{
    public function testGetOwnerIdDelegatesToWrappedCard(): void
    {
        $factory = new TraceableCardFactory(new CardFactory(new MockCardRegistry()), new Stopwatch());

        $card = $factory->createWithState('consolation_prices', new CardState('consolation', 'consolation_prices', 'player-1'));

        self::assertSame('player-1', $card->getOwnerId());
    }
}
