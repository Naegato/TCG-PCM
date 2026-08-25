<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Passive\MegaBombCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class MegaBombCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return MegaBombCard::class;
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn(): void
    {
        $card = $this->buildCard(1);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnTurnStartOnlyDecrementsWhenDelayNotReached(): void
    {
        $card = $this->buildCard(2);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(1, $events[0]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    public function testOnTurnStartDealsDamageWhenDelayIsReached(): void
    {
        $card = $this->buildCard(1);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('2', $events[0]->data['targetId']);
        self::assertSame(40, $events[0]->data['damage']);

        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
        self::assertSame(3, $events[1]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    private function buildCard(int $turnRemainingBeforeAction): MegaBombCard
    {
        $card = new MegaBombCard();
        $card->setState(
            new CardState(
                'test_card',
                $card->getId(),
                '1',
                [],
                [
                    'turnRemainingBeforeAction' => $turnRemainingBeforeAction,
                ],
            ),
        );

        return $card;
    }
}
