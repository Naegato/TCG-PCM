<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\StickyBombCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class StickyBombCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return StickyBombCard::class;
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
        self::assertSame(12, $events[0]->data['damage']);
    }

    public function testPlayThrowsWhenTargetMissing(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $this->expectException(\InvalidArgumentException::class);

        $card->play($ctx, []);
    }
}
