<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Monster\HoneyBeeCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class HoneyBeeCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return HoneyBeeCard::class;
    }

    public function testOnAttackHealsOwner()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onAttack($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::HEAL_APPLIED, $events[0]->type);
        self::assertSame($card->getOwnerId(), $events[0]->data['targetId']);
        self::assertSame(5, $events[0]->data['amount']);
    }

    public function testBaseAttackAndHealPoints()
    {
        $card = $this->getCard();

        self::assertSame(5, $card->getBaseAttack());
        self::assertSame(15, $card->getHealPoints());
    }
}
