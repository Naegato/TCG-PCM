<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\AlchemistMonkeyCard;
use App\Game\GameContext;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class AlchemistMonkeyCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return AlchemistMonkeyCard::class;
    }

    public function testTurnEndBuffsRandomOtherMonster()
    {
        $card = $this->getCard();

        $player1 = $this->createPlayerState('1');
        $player1 = new PlayerState($player1->player, 30, 30, '', [], [], 0, new PlayArea([], ['test_card', 'target_card']));
        $player2 = $this->createPlayerState('2');

        $state = new GameState($player1, $player2, 1, 0, '2', [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'target_card' => new CardState('target_card', 'DummyCard', '1', [], ['bonusAttack' => 3]),
        ]);

        $gameContext = new GameContext($state, '1');

        $card->onTurnEnd(new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $card->getOwnerId()]), $gameContext);
        $events = $gameContext->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame('target_card', $events[0]->data['cardId']);
        self::assertSame(9, $events[0]->data['stateToUpdate']['bonusAttack']);
    }

    public function testTurnEndDoesNothingWhenOwnerIsCurrentPlayer()
    {
        $card = $this->getCard();

        $player1 = $this->createPlayerState('1');
        $player1 = new PlayerState($player1->player, 30, 30, '', [], [], 0, new PlayArea([], ['test_card', 'target_card']));
        $player2 = $this->createPlayerState('2');

        $state = new GameState($player1, $player2, 1, 0, '1', [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'target_card' => new CardState('target_card', 'DummyCard', '1', []),
        ]);

        $gameContext = new GameContext($state, '1');

        $card->onTurnEnd(new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $card->getOwnerId()]), $gameContext);
        $events = $gameContext->flushEvents();

        self::assertCount(0, $events);
    }

    public function testTurnEndDoesNothingWhenNoOtherMonster()
    {
        $card = $this->getCard();

        $player1 = $this->createPlayerState('1');
        $player1 = new PlayerState($player1->player, 30, 30, '', [], [], 0, new PlayArea([], ['test_card']));
        $player2 = $this->createPlayerState('2');

        $state = new GameState($player1, $player2, 1, 0, '2', [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
        ]);

        $gameContext = new GameContext($state, '1');

        $card->onTurnEnd(new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $card->getOwnerId()]), $gameContext);
        $events = $gameContext->flushEvents();

        self::assertCount(0, $events);
    }
}
