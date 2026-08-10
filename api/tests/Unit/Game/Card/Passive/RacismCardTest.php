<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\ClottyCard;
use App\Game\Card\Monster\HoneyBeeCard;
use App\Game\Card\Passive\RacismCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class RacismCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return RacismCard::class;
    }

    public function testOnCardPlayedAttacksOriginalMonster(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();
        $playedCard = new HoneyBeeCard();
        $playedCard->setState(new CardState('honey_bee_1', $playedCard->getId(), '2', []));

        $card->onCardPlayed($playedCard, $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE, $events[0]->type);
        self::assertSame('honey_bee_1', $events[0]->data['targetId']);
        self::assertSame(5, $events[0]->data['damage']);
    }

    public function testOnCardPlayedDoesNothingForNonMonsterCard(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();
        $playedCard = $this->createStubCard();

        $card->onCardPlayed($playedCard, $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnCardPlayedDoesNothingForNonOriginalMonster(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();
        $playedCard = new ClottyCard();
        $playedCard->setState(new CardState('clotty_1', $playedCard->getId(), '2', []));

        $card->onCardPlayed($playedCard, $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }
}
