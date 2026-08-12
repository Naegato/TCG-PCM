<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\SpicyD6Card;
use App\Tests\Unit\Game\Card\CardTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SpicyD6CardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return SpicyD6Card::class;
    }

    #[DataProvider('d6RollsProvider')]
    public function testD6Damage(int $roll): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls($roll);
        $ctx = $this->createGameContext();

        $card->play($ctx);

        $event = $ctx->flushEvents()[0];

        self::assertSame($event->type, GameEventTypeEnum::DAMAGE_DEALT);
        self::assertSame($event->data['damage'], $roll * 10);
    }

    public static function d6RollsProvider(): \Generator
    {
        yield from self::allRollFromGenerator(6);
    }
}
