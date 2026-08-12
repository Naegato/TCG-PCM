<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\BananaCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class BananaCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return BananaCard::class;
    }

    public function testPlayHealsOwner(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::HEAL_APPLIED, $events[0]->type);
        self::assertSame('1', $events[0]->data['targetId']);
        self::assertSame(10, $events[0]->data['amount']);
    }
}
