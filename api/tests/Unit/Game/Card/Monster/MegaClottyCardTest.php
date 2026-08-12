<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\MegaClottyCard;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class MegaClottyCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return MegaClottyCard::class;
    }

    public function testOnMonsterPlayedDiscardsCLottiesAndGrantsBonus()
    {
        $card = $this->getCard();

        $player1State = new PlayerState(new Player('1', 'Player 1', 67), 30, 30, '', [], [], 0, new PlayArea(monsterCards: ['clotty_a', 'other_x']));
        $player2State = $this->createPlayerState('2');

        $gameState = new GameState($player1State, $player2State, 1, 0, null, [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'clotty_a' => new CardState('clotty_a', 'Clotty', '1', []),
            'other_x' => new CardState('other_x', 'DummyCard', '1', []),
        ]);

        $gameContext = new GameContext($gameState, '1');

        $card->onMonsterPlayed($gameContext);
        $events = $gameContext->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[0]->type);
        self::assertSame('clotty_a', $events[0]->data['cardId']);

        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
        self::assertSame('test_card', $events[1]->data['cardId']);
        self::assertSame(7, $events[1]->data['stateToUpdate']['bonusAttack']);
        self::assertSame(7, $events[1]->data['stateToUpdate']['bonusHealth']);

        self::assertSame(15 + 7, $card->getBaseAttack());
        self::assertSame(15 + 7, $card->getHealPoints());
    }

    public function testOnMonsterPlayedWithNoClottiesGrantsNoBonus()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onMonsterPlayed($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(0, $events[0]->data['stateToUpdate']['bonusAttack']);
        self::assertSame(0, $events[0]->data['stateToUpdate']['bonusHealth']);
    }

    public function testOnMonsterDeathSpawnsTwoClotties()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onMonsterDeath($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(4, $events);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('Clotty', $events[0]->data['cardTemplateId']);
        self::assertSame($card->getOwnerId(), $events[0]->data['playerId']);
        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, $events[1]->type);
        self::assertSame($card->getOwnerId(), $events[1]->data['playerId']);
        self::assertSame($events[0]->data['cardInstanceId'], $events[1]->data['cardId']);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[2]->type);
        self::assertSame('Clotty', $events[2]->data['cardTemplateId']);
        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, $events[3]->type);
        self::assertSame($events[2]->data['cardInstanceId'], $events[3]->data['cardId']);
    }

    public function testBaseAttackAndHealPoints()
    {
        $card = $this->getCard();

        self::assertSame(15, $card->getBaseAttack());
        self::assertSame(15, $card->getHealPoints());
    }
}
