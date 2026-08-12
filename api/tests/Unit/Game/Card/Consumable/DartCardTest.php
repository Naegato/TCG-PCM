<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\DartCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class DartCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return DartCard::class;
    }

    public function testPlayDamagesTarget(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->play($ctx, ['target' => 'monster1']);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('monster1', $events[0]->data['targetId']);
        self::assertSame(7, $events[0]->data['damage']);
    }

    public function testPlayThrowsWhenTargetMissing(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $this->expectException(\InvalidArgumentException::class);

        $card->play($ctx, []);
    }
}
