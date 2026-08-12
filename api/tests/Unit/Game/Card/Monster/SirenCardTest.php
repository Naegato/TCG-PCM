<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\SirenCard;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class SirenCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return SirenCard::class;
    }

    /**
     * Builds a card whose turn-delay countdown is already at 1, so the very next
     * onTurnStart() triggers the steal action deterministically.
     */
    private function createCardReadyToAct(): SirenCard
    {
        $card = new SirenCard();
        $card->setState(new CardState('test_card', $card->getId(), '1', [], ['turnRemainingBeforeAction' => 1]));

        return $card;
    }

    public function testOnTurnStartStealsTheOnlyOpponentMonster(): void
    {
        $card = $this->createCardReadyToAct();

        $player1State = $this->createPlayerState('1');
        // Opponent has a single monster in play, so the randomizer has only one possible pick.
        $player2State = new PlayerState(new Player('2', 'Player 2'), 30, 30, 'char2', [], [], 0, new PlayArea([], ['m1']));

        $state = new GameState($player1State, $player2State, 1, 0, null, [
            'test_card' => new CardState('test_card', SirenCard::class, '1', [], ['turnRemainingBeforeAction' => 1]),
        ]);

        $ctx = new GameContext($state, '1');

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::CARD_STOLEN, $events[0]->type);
        self::assertSame('m1', $events[0]->data['cardId']);
        self::assertSame('2', $events[0]->data['fromPlayerId']);
        self::assertSame('1', $events[0]->data['toPlayerId']);

        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
        self::assertSame('test_card', $events[1]->data['cardId']);
        self::assertSame(['turnRemainingBeforeAction' => 2], $events[1]->data['stateToUpdate']);
    }

    public function testOnTurnStartWithNoOpponentMonsterOnlyUpdatesCooldown(): void
    {
        $card = $this->createCardReadyToAct();
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(['turnRemainingBeforeAction' => 2], $events[0]->data['stateToUpdate']);
    }

    public function testOnTurnStartDoesNothingWhenNotOwnersTurn(): void
    {
        $card = $this->createCardReadyToAct();

        $gameState = $this->createGameContext()->state;
        $gameState = $gameState->withCurrentPlayer($gameState->player2->player->id);
        $ctx = new GameContext($gameState, $gameState->player1->player->id);

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);

        self::assertCount(0, $ctx->flushEvents());
    }
}
