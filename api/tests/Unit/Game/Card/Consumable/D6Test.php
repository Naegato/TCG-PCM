<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\D6Card;
use App\Game\State\GameEvent;
use App\Tests\Unit\Game\Card\CardTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class D6Test extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return D6Card::class;
    }

    #[DataProvider('d6RollsProvider')]
    public function test6DrawCards(int $roll): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls($roll);
        $ctx = $this->createGameContext();

        $card->play($ctx);

        $events = $ctx->flushEvents();

        self::assertCount($roll, $events);
    }

    public function testD6Event(): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls(1);
        $ctx = $this->createGameContext();

        $card->play($ctx);

        [$drawEvent] = $ctx->flushEvents();

        self::assertEquals(GameEvent::game(GameEventTypeEnum::CARD_DRAWN, ['playerId' => '1']), $drawEvent);
    }

    public static function d6RollsProvider(): \Generator
    {
        yield from self::allRollFromGenerator(6);
    }
}
