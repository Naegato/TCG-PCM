<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Game;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\CardState;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Monster\RedBloonsMonsterCard;
use App\Game\Card\MonsterCardState;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\Exception\NotEnoughCoinsException;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\CardFactory;
use App\Service\Game\CardRuntimeMap;
use App\Service\Game\Factory\GameContextFactory;
use App\Service\Game\GameEventApplier;
use App\Service\Game\GameEventApplierInterface;
use App\Service\Game\GameEventResolver;
use App\Tests\Resources\MockCardRegistry;
use App\Tests\Unit\Fixtures\DummyCard;
use App\Tests\Unit\Fixtures\SpyAwareCard;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class GameEventResolverTest extends TestCase
{
    #[After]
    public function clean(): void
    {
        SpyAwareCard::reset();
    }

    public function testCardPlayWithNoMoney()
    {
        self::expectException(NotEnoughCoinsException::class);
        self::expectExceptionMessage('Action cost 1 coins, got 0');

        $gm = $this->getSut();

        $gameState = $this->createGameState();
        $gameState = new GameState(
            $gameState->player1->withUpdatedCoins(0),
            $gameState->player2,
            $gameState->lastEventId,
            $gameState->seed,
            $gameState->currentPlayer,
            $gameState->cards,
        );
        $event = new GameEvent(0, GameEventTypeEnum::CARD_PLAYED, GameEvent::PLAYER_EVENT, [
            'playerId' => $gameState->player1->player->id,
            'cardId' => 'card1',
        ]);

        $gm->resolve($event, $gameState)->events;
    }

    public function testHandlePlayActionCallCard(): void
    {
        $gm = $this->getSut();

        $gameState = $this->createGameState();
        $event = new GameEvent(0, GameEventTypeEnum::CARD_PLAYED, GameEvent::PLAYER_EVENT, [
            'playerId' => $gameState->player1->player->id,
            'cardId' => 'card2',
        ]);

        $events = $gm->resolve($event, $gameState)->events;

        self::assertNotNull(SpyCard::$receivedContext);
        self::assertCount(3, $events);
        self::assertEquals(
            [
                GameEventTypeEnum::CARD_PLAYED,
                GameEventTypeEnum::COINS_LOST,
                GameEventTypeEnum::CARD_DISCARDED,
            ],
            array_map(static fn(GameEvent $event) => $event->type, $events),
        );
    }

    public function testEndTurn()
    {
        $gm = $this->getSut();

        $gameState = $this->createGameState();
        $event = new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player1->player->id]);

        $events = $gm->resolve($event, $gameState)->events;
        $expected = [
            new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player1->player->id]),
            new GameEvent(0, GameEventTypeEnum::TURN_STARTED, GameEvent::GAME_EVENT, ['playerId' => $gameState->player2->player->id]),
            new GameEvent(0, GameEventTypeEnum::COINS_GAINED, GameEvent::GAME_EVENT, [
                'playerId' => $gameState->player2->player->id,
                'amount' => 3,
            ]),
            new GameEvent(0, GameEventTypeEnum::CARD_DRAWN, GameEvent::GAME_EVENT, ['playerId' => $gameState->player2->player->id]),
        ];

        self::assertEquals($expected, $events);
    }

    public function testEndTurnWithNewRound()
    {
        $gm = $this->getSut();

        $gameState = $this->createGameState();
        $gameState = $gameState->withCurrentPlayer($gameState->player2->player->id);
        $event = new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player2->player->id]);

        $events = $gm->resolve($event, $gameState)->events;
        $expected = [
            new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player2->player->id]),
            new GameEvent(0, GameEventTypeEnum::TURN_STARTED, GameEvent::GAME_EVENT, ['playerId' => $gameState->player1->player->id]),
            new GameEvent(0, GameEventTypeEnum::COINS_GAINED, GameEvent::GAME_EVENT, [
                'playerId' => $gameState->player1->player->id,
                'amount' => 3,
            ]),
            new GameEvent(0, GameEventTypeEnum::CARD_DRAWN, GameEvent::GAME_EVENT, ['playerId' => $gameState->player1->player->id]),
        ];

        self::assertEquals($expected, $events);
    }

    public function testCardPlay()
    {
        $gm = $this->getSut();
        $gameState = $this->createGameState();

        $event = new GameEvent(0, GameEventTypeEnum::CARD_PLAYED, GameEvent::PLAYER_EVENT, [
            'playerId' => $gameState->player1->player->id,
            'cardId' => 'card2',
        ]);

        $resolution = $gm->resolve($event, $gameState);

        self::assertCount(3, $resolution->events);
    }

    public function testEndTurnCallTurnAwareCard()
    {
        $gm = $this->getSut();

        $gameState = $this->createGameState();
        $player = $gameState->player1->withPlayArea($gameState->player1->playArea->addPassiveCard('card1O'));
        $gameState = new GameState($player, $gameState->player2, $gameState->lastEventId, $gameState->seed, $gameState->currentPlayer, [
            'card1O' => new CardState('card1O', SpyCard::class, '1', []),
        ]);
        $event = new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player1->player->id]);

        $gm->resolve($event, $gameState)->events;

        self::assertTrue(SpyCard::$turnStartCalled);
    }

    public function testPropagate()
    {
        $gm = $this->getSut();

        $gameState = $this->createGameState();
        $event = new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player1->player->id]);

        $events = $gm->resolve($event, $gameState)->events;

        self::assertCount(4, $events);
    }

    public function testPropagateWithCardDeath()
    {
        $ger = $this->getSut();
        $gameState = new GameState(
            new PlayerState(
                new Player('1', 'Player 1'),
                10,
                10,
                'player1',
                [],
                [],
                0,
                new PlayArea([], [
                    'attacker',
                ]),
            ),
            new PlayerState(
                new Player('2', 'Player 2'),
                10,
                10,
                'player1',
                [],
                [],
                0,
                new PlayArea([], [
                    'bloons',
                ]),
            ),
            0,
            0,
            null,
            [
                'bloons' => new MonsterCardState('bloons', 'Redbloons', '2', 1),
                'attacker' => new MonsterCardState('attacker', 'Redbloons', '1', 1),
            ],
        );

        $event = new GameEvent(0, GameEventTypeEnum::ATTACK, GameEvent::PLAYER_EVENT, [
            'targetId' => 'bloons',
            'attackerId' => 'attacker',
        ]);

        $events = $ger->resolve($event, $gameState)->events;

        self::assertCount(4, $events);
        self::assertCount(1, array_filter($events, static fn(GameEvent $event): bool => GameEventTypeEnum::MONSTER_DIED === $event->type));
    }

    public function testPropagateWithCardDeathAndDeathCardAware()
    {
        $ger = $this->getSut();
        $gameState = new GameState(
            new PlayerState(new Player('1', 'Player 1'), 10, 10, 'player1', [], [], 0, new PlayArea()),
            new PlayerState(
                new Player('2', 'Player 2'),
                10,
                10,
                'player1',
                [],
                [],
                0,
                new PlayArea([
                    'spy-aware',
                ], [
                    'bloons',
                ]),
            ),
            0,
            0,
            null,
            [
                'bloons' => new MonsterCardState('bloons', 'Redbloons', '2', 1),
                'attacker' => new MonsterCardState('attacker', 'Redbloons', '1', 1),
                'spy-aware' => new CardState('spy-aware', SpyAwareCard::class, '2'),
            ],
        );

        $event = new GameEvent(0, GameEventTypeEnum::ATTACK, GameEvent::PLAYER_EVENT, [
            'targetId' => 'bloons',
            'attackerId' => 'attacker',
        ]);

        $ger->resolve($event, $gameState)->events;
        self::assertCount(1, SpyAwareCard::$calls);
    }

    public function testPropagateWithPlayerDeath()
    {
        $ger = $this->getSut();
        $gameState = new GameState(
            new PlayerState(new Player('1', 'Player 1'), 10, 10, 'player1', [], [], 0, new PlayArea()),
            new PlayerState(
                new Player('2', 'Player 2'),
                1,
                10,
                'player1',
                [],
                [],
                0,
                new PlayArea([
                    'spy-aware',
                ], [
                    'bloons',
                ]),
            ),
            0,
            0,
            null,
            [
                'bloons' => new MonsterCardState('bloons', 'Redbloons', '1', 1),
                'attacker' => new MonsterCardState('attacker', 'Redbloons', '1', 1),
                'spy-aware' => new CardState('spy-aware', SpyAwareCard::class, '2'),
            ],
        );

        $event = new GameEvent(0, GameEventTypeEnum::ATTACK, GameEvent::PLAYER_EVENT, [
            'targetId' => '2',
            'attackerId' => 'attacker',
        ]);

        $ger->resolve($event, $gameState)->events;

        // add UPDATE_CARD_STATE
        self::assertCount(1, SpyAwareCard::$calls);
    }

    public function testSimultaneousMonsterDeathsDoNotProduceDuplicateDeathEvents(): void
    {
        $ger = $this->getSut();
        $gameState = new GameState(
            new PlayerState(new Player('1', 'Player 1'), 10, 10, 'player1', [], [], 0, new PlayArea()),
            new PlayerState(
                new Player('2', 'Player 2'),
                10,
                10,
                'player1',
                [],
                [
                    'draw2' => DummyCard::class,
                ],
                0,
                new PlayArea([], [
                    'bloons1',
                    'bloons2',
                ]),
            ),
            0,
            0,
            null,
            [
                'bloons1' => new MonsterCardState('bloons1', 'Redbloons', '2', 0),
                'bloons2' => new MonsterCardState('bloons2', 'Redbloons', '2', 0),
            ],
        );

        // Both monsters are already at 0 HP (e.g. following an AOE damage event),
        // so the very first event resolved should detect and queue both deaths at once.
        $event = new GameEvent(0, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, ['playerId' => $gameState->player1->player->id]);

        $events = $ger->resolve($event, $gameState)->events;

        $deathEvents = array_values(array_filter($events, static fn(GameEvent $e): bool => GameEventTypeEnum::MONSTER_DIED === $e->type));

        self::assertCount(2, $deathEvents);
        self::assertEqualsCanonicalizing(
            ['bloons1', 'bloons2'],
            array_map(static fn(GameEvent $e) => $e->data['cardId'], $deathEvents),
        );
    }

    public function testAttackPlayerCreatesDamageEvent(): void
    {
        $ger = $this->getSut();
        $gameState = new GameState(
            new PlayerState(
                new Player('1', 'Player 1'),
                10,
                10,
                'player1',
                [],
                [],
                0,
                new PlayArea([], [
                    'attacker',
                ]),
            ),
            new PlayerState(new Player('2', 'Player 2'), 20, 20, 'player2', [], [], 0, new PlayArea()),
            0,
            0,
            null,
            [
                'attacker' => new MonsterCardState('attacker', 'Redbloons', '1', 1),
            ],
        );

        $event = new GameEvent(0, GameEventTypeEnum::ATTACK, GameEvent::PLAYER_EVENT, [
            'targetId' => '2',
            'attackerId' => 'attacker',
        ]);

        $events = $ger->resolve($event, $gameState)->events;

        $damageEvents = array_filter($events, static fn(GameEvent $e): bool => GameEventTypeEnum::DAMAGE === $e->type);
        self::assertNotEmpty($damageEvents);
        $de = array_values($damageEvents)[0];
        self::assertEquals('2', $de->data['targetId']);
        self::assertEquals(5, $de->data['damage']);

        $updateEvents = array_filter($events, static fn(GameEvent $e): bool => GameEventTypeEnum::UPDATE_CARD_STATE === $e->type);
        self::assertNotEmpty($updateEvents);
    }

    private function createGameState(): GameState
    {
        $player1State = new PlayerState(
            new Player('1', 'Player 1', 67),
            30,
            30,
            '',
            [
                'card1',
                'card2',
            ],
            [
                'drawPile1' => DummyCard::class,
            ],
            10,
            new PlayArea(),
        );
        $player2State = new PlayerState(
            new Player('2', 'Player 2', 69),
            30,
            30,
            '',
            [],
            [
                'drawPile2' => DummyCard::class,
            ],
            10,
            new PlayArea(),
        );

        return new GameState($player1State, $player2State, 1, 0, null, [
            'card1' => new CardState('card1', DummyCard::class, '1', []),
            'card2' => new CardState('card2', SpyCard::class, '2', []),
        ]);
    }

    private function getSut(bool $fakeGEA = false): GameEventResolver
    {
        $cardRuntimeMap = new CardRuntimeMap(new CardFactory(new MockCardRegistry([
            DummyCard::class => DummyCard::class,
            'other_card' => DummyCard::class,
            SpyCard::class => SpyCard::class,
            'Redbloons' => RedBloonsMonsterCard::class,
            SpyAwareCard::class => SpyAwareCard::class,
        ])));

        $gea = $fakeGEA
            ? new class implements GameEventApplierInterface {
                public function apply(GameEvent $event, GameState $gameState): GameState
                {
                    return $gameState;
                }

                public function applyMultiple(array $events, GameState $gameState): GameState
                {
                    return $gameState;
                }
            } : new GameEventApplier($cardRuntimeMap);

        return new GameEventResolver($cardRuntimeMap, new GameContextFactory(), $gea);
    }
}

class SpyCard extends AbstractConsumableCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public static ?GameContext $receivedContext = null;
    public static bool $turnStartCalled = false;

    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'Spy';
    }

    public function getDescription(): string
    {
        return $this->getName();
    }

    public function play(GameContext $ctx, array $data = []): void
    {
        self::$receivedContext = $ctx;

        if ($data['other'] ?? false) {
            $ctx->pushGameEvent(GameEventTypeEnum::CARD_PLAYED, ['playerId' => $ctx->playerId, 'cardId' => 'other-spy']);
        }
    }

    public function onTurnStart(GameEvent $event, GameContext $ctx): void
    {
        self::$turnStartCalled = true;
    }
}
