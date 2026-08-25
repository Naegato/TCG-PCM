<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Passive\BombFactoryCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class BombFactoryCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return BombFactoryCard::class;
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn(): void
    {
        $card = $this->buildCard(1);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnTurnStartGeneratesBombEachTurn(): void
    {
        $card = $this->buildCard(1);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('1', $events[0]->data['playerId']);
        self::assertSame('TimeBomb', $events[0]->data['cardTemplateId']);

        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, $events[1]->type);
        self::assertSame('1', $events[1]->data['playerId']);

        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[2]->type);
        self::assertSame(1, $events[2]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    private function buildCard(int $turnRemainingBeforeAction): BombFactoryCard
    {
        $card = new BombFactoryCard();
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
