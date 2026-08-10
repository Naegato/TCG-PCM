<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\GitmanCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class GitmanCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return GitmanCard::class;
    }

    public function testPlayDealsDamageBasedOnPrecomputedCommitCount()
    {
        $card = $this->getCard();
        $card->setComputedValue(20);
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_RUNTIME_VALUE, $events[0]->type);
        self::assertSame(2, $events[0]->data['value']);
        self::assertSame(GameEventTypeEnum::DAMAGE, $events[1]->type);
        self::assertSame(2, $events[1]->data['damage']);
        self::assertSame($ctx->getOpponent()->id, $events[1]->data['targetId']);
    }

    public function testComputeValueReturnsPrecomputedCommitCountWithoutHttpCall()
    {
        $card = $this->getCard();
        $card->setComputedValue(42);

        self::assertSame(42, $card->computeValue());
    }
}
