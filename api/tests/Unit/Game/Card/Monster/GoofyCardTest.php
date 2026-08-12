<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Monster\GoofyCard;
use App\Game\Card\MonsterCardState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class GoofyCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return GoofyCard::class;
    }

    public function testStats(): void
    {
        $card = $this->getCard();

        self::assertSame(10, $card->getHealPoints());
        self::assertSame(10, $card->getBaseAttack());
        self::assertSame(10, $card->getAttack());
    }

    public function testOnTurnStartUpdatesAttackAndHealthOnOwnerTurn(): void
    {
        $card = new GoofyCard();
        $card->setState(new MonsterCardState('test_card', $card->getId(), '1', 10, [], ['attack' => 10]));

        $this->ensureNextDiceRolls(5);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertSame(15, $card->getBaseAttack());
        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame('test_card', $events[0]->data['cardId']);
        self::assertSame(15, $events[0]->data['currentHealthPoints']);
        self::assertSame(['attack' => 15], $events[0]->data['stateToUpdate']);
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn(): void
    {
        $card = new GoofyCard();
        $card->setState(new MonsterCardState('test_card', $card->getId(), '1', 10, [], ['attack' => 10]));

        $this->ensureNextDiceRolls(5);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);

        self::assertSame(10, $card->getBaseAttack());
        self::assertCount(0, $ctx->flushEvents());
    }

    public function testOnTurnStartNeverDropsAttackBelowZero(): void
    {
        $card = new GoofyCard();
        $card->setState(new MonsterCardState('test_card', $card->getId(), '1', 10, [], ['attack' => 5]));

        $this->ensureNextDiceRolls(-10);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertSame(0, $card->getBaseAttack());
        self::assertCount(1, $events);
        self::assertSame(['attack' => 0], $events[0]->data['stateToUpdate']);
    }
}
