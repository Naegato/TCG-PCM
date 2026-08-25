<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Passive\DeadCatCard;
use App\Game\GameContext;
use App\Tests\Unit\Game\Card\CardTestCase;

final class DeadCatCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return DeadCatCard::class;
    }

    public function testOnPlayerDeathDoesNothingWhenOtherPlayerDies(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onPlayerDeath($ctx, '2');

        self::assertCount(0, $ctx->flushEvents());
    }

    public function testOnPlayerDeathHealsAndDecreasesCounter(): void
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $ctx = new GameContext($state->withUpdatedPlayer($state->player1->withUpdatedHealth(0)), '1');

        $card->onPlayerDeath($ctx, '1');
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::HEAL_APPLIED, $events[0]->type);
        self::assertSame('1', $events[0]->data['targetId']);
        self::assertSame(1, $events[0]->data['amount']);

        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
        self::assertSame('test_card', $events[1]->data['cardId']);
        self::assertSame(8, $events[1]->data['stateToUpdate']['counter']);
    }

    public function testOnPlayerDeathHealsMissingHealthPointsWhenNegative(): void
    {
        $card = $this->getCard();
        $state = $this->createGameContext()->state;
        $ctx = new GameContext($state->withUpdatedPlayer($state->player1->withUpdatedHealth(-3)), '1');

        $card->onPlayerDeath($ctx, '1');
        $events = $ctx->flushEvents();

        self::assertSame(4, $events[0]->data['amount']);
    }

    public function testOnPlayerDeathDiscardsCardWhenCounterReachesZero(): void
    {
        $card = $this->getCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', values: ['counter' => 1]));

        $state = $this->createGameContext()->state;
        $ctx = new GameContext($state->withUpdatedPlayer($state->player1->withUpdatedHealth(0)), '1');

        $card->onPlayerDeath($ctx, '1');
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);
        self::assertSame(0, $events[1]->data['stateToUpdate']['counter']);
        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[2]->type);
        self::assertSame('test_card', $events[2]->data['cardId']);
    }

    public function testOnPlayerDeathDoesNotDiscardCardWhenCounterAboveZero(): void
    {
        $card = $this->getCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', values: ['counter' => 2]));

        $state = $this->createGameContext()->state;
        $ctx = new GameContext($state->withUpdatedPlayer($state->player1->withUpdatedHealth(0)), '1');

        $card->onPlayerDeath($ctx, '1');
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
    }
}
