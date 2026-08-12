<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Monster\BoomerangMonkeyCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class BoomerangMonkeyCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return BoomerangMonkeyCard::class;
    }

    public function testStats()
    {
        $card = $this->getCard();

        self::assertSame(10, $card->getHealPoints());
        self::assertSame(20, $card->getBaseAttack());
        self::assertSame(20, $card->getAttack());
    }

    public function testOnAttackDamagesItself()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onAttack($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame($card->getInstanceId(), $events[0]->data['targetId']);
        self::assertSame(3, $events[0]->data['damage']);
    }
}
