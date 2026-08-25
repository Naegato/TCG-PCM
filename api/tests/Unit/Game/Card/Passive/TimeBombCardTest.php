<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Passive\TimeBombCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class TimeBombCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return TimeBombCard::class;
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnTurnStartIncrementsTurnsActiveBeforeThreshold(): void
    {
        $card = $this->buildCard(0);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(1, $events[0]->data['stateToUpdate']['turnsActive']);
    }

    public function testOnTurnStartExplodesWhenThresholdReached(): void
    {
        $card = $this->buildCard(3);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('2', $events[0]->data['targetId']);
        self::assertSame(15, $events[0]->data['damage']);

        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[1]->type);
        self::assertSame('test_card', $events[1]->data['cardId']);
    }

    private function buildCard(int $turnsActive): TimeBombCard
    {
        $card = new TimeBombCard();
        $card->setState(
            new CardState(
                'test_card',
                $card->getId(),
                '1',
                [],
                [
                    'turnsActive' => $turnsActive,
                ],
            ),
        );

        return $card;
    }
}
