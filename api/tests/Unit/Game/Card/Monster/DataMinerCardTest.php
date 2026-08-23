<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\DataMinerCard;
use App\Tests\Unit\Game\Card\CardTestCase;

final class DataMinerCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return DataMinerCard::class;
    }

    public function testBaseAttackAndHealPoints(): void
    {
        $card = $this->getCard();

        self::assertSame(5, $card->getBaseAttack());
        self::assertSame(10, $card->getHealPoints());
    }

    public function testOnTurnStartAddsCoinsEqualToCurrentCoinsOnOwnerTurn(): void
    {
        $card = new DataMinerCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', [], ['currentCoins' => 3]));

        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::COINS_GAINED, $events[0]->type);
        self::assertSame('1', $events[0]->data['playerId']);
        self::assertSame(3, $events[0]->data['amount']);
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn(): void
    {
        $card = new DataMinerCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', [], ['currentCoins' => 3]));

        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);

        self::assertCount(0, $ctx->flushEvents());
    }

    public function testOnTurnEndIncrementsCurrentCoinsAndPushesCardStateUpdated(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onTurnEnd($this->createTurnEndedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame($card->getInstanceId(), $events[0]->data['cardId']);
        self::assertSame('1', $events[0]->data['playerId']);
        self::assertSame(['currentCoins' => 1], $events[0]->data['stateToUpdate']);
    }

    public function testOnTurnEndDoesNothingWhenNotOwnerTurn(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onTurnEnd($this->createTurnEndedEvent('2'), $ctx);

        self::assertCount(0, $ctx->flushEvents());
    }

    public function testOnTurnEndIncrementsCurrentCoinsOnEachCall(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onTurnEnd($this->createTurnEndedEvent('1'), $ctx);
        $ctx->flushEvents();
        $card->onTurnEnd($this->createTurnEndedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertSame(['currentCoins' => 2], $events[0]->data['stateToUpdate']);
    }

    public function testSetStateRestoresCurrentCoins(): void
    {
        $card = new DataMinerCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', [], ['currentCoins' => 7]));

        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertSame(7, $events[0]->data['amount']);
    }

    public function testSetStateDefaultsCurrentCoinsToZeroWhenMissing(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertSame(0, $events[0]->data['amount']);
    }

    public function testGetDescriptionContainsCurrentCoinsValue(): void
    {
        $card = new DataMinerCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', [], ['currentCoins' => 4]));

        self::assertStringContainsString('4', $card->getDescription());
    }
}
