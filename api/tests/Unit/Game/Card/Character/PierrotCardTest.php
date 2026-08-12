<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Character;

use App\Enum\CardEffectEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Character\PierrotCard;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\TestableGameContext;

final class PierrotCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return PierrotCard::class;
    }

    public function testGetId()
    {
        self::assertSame('Pierrot', $this->getCard()->getId());
    }

    public function testGetHealthPoints()
    {
        /** @var PierrotCard $card */
        $card = $this->getCard();
        self::assertSame(175, $card->getHealthPoints());
    }

    public function testGetTurnDelay()
    {
        /** @var PierrotCard $card */
        $card = $this->getCard();
        self::assertSame(2, $card->getTurnDelay());
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
        // Starting at 2 remaining turns, one turn start should only decrement to 1.
        $card = $this->buildCard(2);
        $state = $this->buildState();
        $ctx = $this->buildContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(1, $events[0]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    public function testOnTurnStartAppliesTornedEffectToRandomOpponentCard()
    {
        $card = $this->buildCard(1);
        // Opponent's play area is empty, so the (deterministic) selection
        // pool only contains the opponent's character card.
        $state = $this->buildState(opponentCharacterId: 'char2');
        $ctx = $this->buildContext($state);

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::EFFECT_ADDED, $events[0]->type);
        self::assertSame(CardEffectEnum::TORNED->value, $events[0]->data['effect']);
        self::assertSame('char2', $events[0]->data['cardId']);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[1]->type);
        self::assertSame(2, $events[1]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    private function buildCard(int $turnRemainingBeforeAction): PierrotCard
    {
        $card = new PierrotCard();
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

    private function buildState(string $opponentCharacterId = ''): GameState
    {
        $player1State = $this->createPlayerState('1');
        $player2State = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, $opponentCharacterId, [], [], 0, new PlayArea());

        return new GameState($player1State, $player2State, 1, 0, '1');
    }

    private function buildContext(GameState $state): GameContext
    {
        return new TestableGameContext($state, '1', 0);
    }
}
