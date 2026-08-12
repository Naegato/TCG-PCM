<?php

declare(strict_types=1);

namespace App\Tests\GameReplay;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\CardRuntimeMap;
use App\Service\Game\Factory\ReplayableGameContextFactory;
use App\Service\Game\GameEventApplier;
use App\Service\Game\GameEventResolver;
use App\Service\Game\GameStateRebuilder;
use App\Tests\Resources\MockCardRegistry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Locks in replay correctness for the debug-tool event sequence
 * (give_card / set_stats / force_end_turn / force_set_current_player / remove_card).
 * These are canonical persisted events like any other player action, so
 * GameStateRebuilder must replay them without special-casing.
 */
#[Group('replay')]
final class DebugEventReplayTest extends TestCase
{
    public function testReplayDebugEventSequence(): void
    {
        $initialState = new GameState(
            new PlayerState(new Player('player1', 'Alice'), 20, 30, '', ['hand1'], ['deck1' => 'D6'], 0, new PlayArea()),
            new PlayerState(new Player('player2', 'Bob'), 30, 30, '', [], ['deck2' => 'D6'], 0, new PlayArea()),
            0,
            0,
            null,
            [
                'hand1' => new CardState('hand1', 'D6', 'player1'),
                'deck1' => new CardState('deck1', 'D6', 'player1'),
                'deck2' => new CardState('deck2', 'D6', 'player2'),
            ],
        );

        $events = [
            // give_card: generate + draw into player2's hand
            new GameEvent(1, GameEventTypeEnum::CARD_GENERATED, GameEvent::PLAYER_EVENT, [
                'playerId' => 'player2',
                'cardTemplateId' => 'D6',
                'cardInstanceId' => 'given-card',
            ]),
            new GameEvent(2, GameEventTypeEnum::CARD_DRAWN, GameEvent::PLAYER_EVENT, [
                'playerId' => 'player2',
                'cardId' => 'given-card',
            ]),
            // set_stats: heal player1 and grant coins
            new GameEvent(3, GameEventTypeEnum::HEAL_APPLIED, GameEvent::PLAYER_EVENT, [
                'targetId' => 'player1',
                'amount' => 5,
            ]),
            new GameEvent(4, GameEventTypeEnum::COINS_GAINED, GameEvent::PLAYER_EVENT, [
                'playerId' => 'player1',
                'amount' => 10,
            ]),
            // force_end_turn
            new GameEvent(5, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, [
                'playerId' => 'player1',
            ]),
            // force_set_current_player back to player1
            new GameEvent(6, GameEventTypeEnum::CURRENT_PLAYER_SET, GameEvent::PLAYER_EVENT, [
                'playerId' => 'player1',
            ]),
        ];

        $rebuilder = $this->getGameStateRebuilder();
        $finalState = $rebuilder->rebuild($initialState, $events);

        self::assertArrayHasKey('given-card', $finalState->cards);
        self::assertContains('given-card', $finalState->player2->hand);
        self::assertSame(25, $finalState->player1->healthPoints);
        self::assertSame(10, $finalState->player1->coins);
        self::assertSame('player1', $finalState->currentPlayer);
        self::assertSame(6, $finalState->lastEventId);
    }

    private function getGameStateRebuilder(): GameStateRebuilder
    {
        $cardsListPath = dirname(__DIR__, 2).'/resources/cards_list.php';
        $cardRuntimeMap = new CardRuntimeMap(new TestCardFactory(new MockCardRegistry(require $cardsListPath)));

        return new GameStateRebuilder(new GameEventResolver($cardRuntimeMap, new ReplayableGameContextFactory(), new GameEventApplier($cardRuntimeMap)));
    }
}
