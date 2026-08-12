<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Character;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Character\MaximeCard;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\TestableGameContext;

final class MaximeCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return MaximeCard::class;
    }

    public function testGetId()
    {
        self::assertSame('Maxime', $this->getCard()->getId());
    }

    public function testGetImage()
    {
        self::assertSame('maxime.webp', $this->getCard()->getImage());
    }

    public function testGetHealthPoints()
    {
        /** @var MaximeCard $card */
        $card = $this->getCard();
        self::assertSame(250, $card->getHealthPoints());
    }

    public function testGetTurnDelay()
    {
        /** @var MaximeCard $card */
        $card = $this->getCard();
        self::assertSame(3, $card->getTurnDelay());
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn()
    {
        $card = $this->buildCard(1);
        $state = $this->buildState();
        $state = $state->withCurrentPlayer('2');
        $ctx = $this->buildContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnTurnStartOnlyDecrementsWhenDelayNotReached()
    {
        // Starting at 3 remaining turns, one turn start should only decrement to 2.
        $card = $this->buildCard(3);
        $state = $this->buildState();
        $ctx = $this->buildContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(2, $events[0]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    public function testOnTurnStartAttacksOpponentWhenTargetIsCharacter()
    {
        $card = $this->buildCard(1);
        // Opponent's play area is empty, so the (deterministic) selection
        // pool only contains the opponent's character card.
        $state = $this->buildState(opponentCharacterId: 'char2');
        $ctx = $this->buildContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('2', $events[0]->data['targetId']);
        self::assertSame(25, $events[0]->data['damage']);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
        self::assertSame(3, $events[1]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    public function testOnTurnStartDiscardsCardWhenTargetIsNotCharacter()
    {
        $card = $this->buildCard(1);
        $state = $this->buildState(opponentCharacterId: 'char2', opponentPassiveCards: ['opponent_card']);
        $ctx = $this->buildContext($state, forceFirstPoolEntry: true);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[0]->type);
        self::assertSame('opponent_card', $events[0]->data['cardId']);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
    }

    private function buildCard(int $turnRemainingBeforeAction): MaximeCard
    {
        $card = new MaximeCard();
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

    private function buildState(string $opponentCharacterId = '', array $opponentPassiveCards = []): GameState
    {
        $player1State = $this->createPlayerState('1');
        $player2State = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, $opponentCharacterId, [], [], 0, new PlayArea($opponentPassiveCards));

        return new GameState($player1State, $player2State, 1, 0, '1');
    }

    private function buildContext(GameState $state, bool $forceFirstPoolEntry = false): GameContext
    {
        if (!$forceFirstPoolEntry) {
            return new TestableGameContext($state, '1', 0);
        }

        return new class($state, '1', 0) extends TestableGameContext {
            public function selectRandomCardIn(array $pool): string
            {
                return $pool[0];
            }
        };
    }
}
