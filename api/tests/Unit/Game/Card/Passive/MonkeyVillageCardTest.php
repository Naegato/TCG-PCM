<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Passive\MonkeyVillageCard;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Tests\Unit\Game\Card\CardTestCase;

final class MonkeyVillageCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return MonkeyVillageCard::class;
    }

    public function testOnTurnStartBuffsOnlyMonkeyMonsters(): void
    {
        $card = $this->getCard();

        $player1 = $this->createPlayerState('1')->withPlayArea(new PlayArea([], ['dartMonkey1', 'other1']));
        $player2 = $this->createPlayerState('2');

        $state = new GameState($player1, $player2, 1, 0, '1', [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'dartMonkey1' => new CardState('dartMonkey1', 'DartMonkey', '1', []),
            'other1' => new CardState('other1', 'SomeOtherMonster', '1', []),
        ]);

        $ctx = $this->createGameContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::UPDATE_CARD_STATE, $events[0]->type);
        self::assertSame('dartMonkey1', $events[0]->data['cardId']);
        self::assertSame(2, $events[0]->data['stateToUpdate']['bonusAttack']);
    }

    public function testOnTurnStartDoesNothingOnOpponentTurn(): void
    {
        $card = $this->getCard();

        $player1 = $this->createPlayerState('1')->withPlayArea(new PlayArea([], ['dartMonkey1']));
        $player2 = $this->createPlayerState('2');

        $state = new GameState($player1, $player2, 1, 0, '2', [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'dartMonkey1' => new CardState('dartMonkey1', 'DartMonkey', '1', []),
        ]);

        $ctx = $this->createGameContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }
}
