<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Game;

use App\Enum\CardEffectEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\RedBloonsMonsterCard;
use App\Game\Card\MonsterCardState;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\CardFactory;
use App\Service\Game\CardRuntimeMap;
use App\Service\Game\GameEventApplier;
use App\Tests\Resources\MockCardRegistry;
use PHPUnit\Framework\TestCase;

final class GameEventApplierTest extends TestCase
{
    public function testApplyCardDrawn()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState(cards: []);
        $event = new GameEvent(1, GameEventTypeEnum::CARD_DRAWN, GameEvent::PLAYER_EVENT, ['playerId' => 'player1']);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(1, $newState->cards);
        self::assertEquals(new CardState('id', 'D6', 'player1', []), $newState->cards['id']);
    }

    public function testApplyDamage(): void
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $event = new GameEvent(1, GameEventTypeEnum::DAMAGE, GameEvent::PLAYER_EVENT, [
            'targetId' => 'player2',
            'damage' => 15,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertSame(15, $newState->player2->healthPoints);
    }

    public function testApplyDamageCharacterCard(): void
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $event = new GameEvent(1, GameEventTypeEnum::DAMAGE, GameEvent::PLAYER_EVENT, [
            'targetId' => 'character_2',
            'damage' => 15,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertSame(15, $newState->player2->healthPoints);
    }

    public function testApplyDamageToMonsterCard(): void
    {
        $eventApplier = $this->getSut(['Redbloons' => RedBloonsMonsterCard::class]);
        $state = $this->getInitialGameState();
        $event = new GameEvent(1, GameEventTypeEnum::DAMAGE, GameEvent::PLAYER_EVENT, [
            'targetId' => 'monster',
            'damage' => 1,
        ]);

        $newState = $eventApplier->apply($event, $state);
        $state = $newState->getCardState('monster');

        self::assertSame(9, $state->currentHealthPoints);
    }

    public function testApplyDamageToMonsterCardDoesNotGoBelowZero(): void
    {
        $eventApplier = $this->getSut(['Redbloons' => RedBloonsMonsterCard::class]);
        $state = $this->getInitialGameState();
        $event = new GameEvent(1, GameEventTypeEnum::DAMAGE, GameEvent::PLAYER_EVENT, [
            'targetId' => 'monster',
            'damage' => 999,
        ]);

        $newState = $eventApplier->apply($event, $state);
        $state = $newState->getCardState('monster');

        self::assertSame(0, $state->currentHealthPoints);
    }

    public function testApplyTurnEnded()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();

        $event = new GameEvent(1, GameEventTypeEnum::TURN_ENDED, GameEvent::PLAYER_EVENT, [
            'playerId' => 'player1',
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertSame('player2', $newState->currentPlayer);
    }

    public function testEffectAdded()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $state = $state->addCard(new CardState('D6', 'D6', '1', []));

        $event = new GameEvent(1, GameEventTypeEnum::EFFECT_ADDED, GameEvent::GAME_EVENT, [
            'cardId' => 'D6',
            'effect' => CardEffectEnum::HACKED->value,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(1, $newState->cards['D6']->effects);
    }

    public function testCardDiscarded()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState(cards: [
            'D6' => new CardState('D6', 'D6', 'player2', []),
        ]);

        $event = new GameEvent(1, GameEventTypeEnum::CARD_DISCARDED, GameEvent::GAME_EVENT, [
            'cardId' => 'D6',
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(0, $newState->getPlayer('player2')->hand);
        self::assertCount(2, $newState->getPlayer('player2')->discardPile);
    }

    public function testCardPlaceInPlayArea()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();

        $event = new GameEvent(1, GameEventTypeEnum::CARD_PLACE_IN_PLAY_AREA, GameEvent::GAME_EVENT, [
            'playerId' => 'player2',
            'cardId' => 'D6',
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(1, $newState->getPlayer('player2')->playArea->passiveCards);
        self::assertCount(0, $newState->getPlayer('player2')->hand);
    }

    public function testCardStateUpdate()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $cardState = new CardState('test', '', '', [], ['turnRemainingBeforeAction' => 2]);
        $state = $state->addCard($cardState);

        $event = new GameEvent(1, GameEventTypeEnum::UPDATE_CARD_STATE, GameEvent::GAME_EVENT, [
            'cardId' => 'test',
            'stateToUpdate' => [
                'turnRemainingBeforeAction' => 1,
            ],
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertSame(1, $newState->cards['test']->values['turnRemainingBeforeAction']);
    }

    public function testCardStateUpdateCanAttack()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $cardState = new MonsterCardState('test', '', '', 1, [], [], true);
        $state = $state->addCard($cardState);

        $event = new GameEvent(1, GameEventTypeEnum::UPDATE_CARD_STATE, GameEvent::GAME_EVENT, [
            'cardId' => 'test',
            'canAttack' => false,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertFalse($newState->cards['test']->canAttack);
    }

    public function testCardPlaceInMonsterArea()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $state = $state->addCard(new CardState('test', '', '', []));

        $event = new GameEvent(1, GameEventTypeEnum::CARD_PLACE_IN_MONSTER_AREA, GameEvent::GAME_EVENT, [
            'cardId' => 'test',
            'playerId' => '1',
            'cardHealthPoints' => 5,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertInstanceOf(MonsterCardState::class, $newState->cards['test']);
        self::assertSame(5, $newState->cards['test']->currentHealthPoints);
    }

    public function testMultipleCardPlaceInMonsterArea()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $state = $state->addCard(new CardState('test', '', '', []));
        $state = $state->withUpdatedPlayer($state->player1->withPlayArea(new PlayArea()));

        $events = [
            new GameEvent(1, GameEventTypeEnum::CARD_PLACE_IN_MONSTER_AREA, GameEvent::GAME_EVENT, [
                'cardId' => 'test',
                'playerId' => 'player1',
                'cardHealthPoints' => 5,
            ]),
            new GameEvent(2, GameEventTypeEnum::CARD_PLACE_IN_MONSTER_AREA, GameEvent::GAME_EVENT, [
                'cardId' => 'test',
                'playerId' => 'player1',
                'cardHealthPoints' => 5,
            ]),
        ];

        $newState = $eventApplier->applyMultiple($events, $state);

        self::assertCount(2, $newState->player1->playArea->getAll());
    }

    public function testCoinsGained()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();

        $event = new GameEvent(1, GameEventTypeEnum::COINS_GAINED, GameEvent::GAME_EVENT, [
            'playerId' => '1',
            'amount' => 5,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertSame(5, $newState->getPlayer('1')->coins);
    }

    public function testCoinsLost()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();
        $state = $state->withUpdatedPlayer($state->player1->withUpdatedCoins(10));

        $event = new GameEvent(1, GameEventTypeEnum::COINS_LOST, GameEvent::GAME_EVENT, [
            'playerId' => 'player1',
            'amount' => 5,
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertSame(5, $newState->getPlayer('player1')->coins);
    }

    public function testMonsterDied()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();

        $event = new GameEvent(1, GameEventTypeEnum::MONSTER_DIED, GameEvent::GAME_EVENT, [
            'playerId' => 'player1',
            'cardId' => 'monster',
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(0, $newState->getPlayer('player1')->playArea->monsterCards);
        self::assertCount(1, $newState->getPlayer('player1')->discardPile);
    }

    public function testCardGenerated()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();

        $event = new GameEvent(1, GameEventTypeEnum::CARD_GENERATED, GameEvent::GAME_EVENT, [
            'playerId' => 'player1',
            'cardTemplateId' => 'monster',
            'cardInstanceId' => 'monster2',
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(2, $newState->cards);
    }

    public function testApplyCardStolenMovesMonsterCardBetweenPlayers(): void
    {
        $eventApplier = $this->getSut(['Redbloons' => RedBloonsMonsterCard::class]);
        $state = $this->getInitialGameState();

        $event = new GameEvent(1, GameEventTypeEnum::CARD_STOLEN, GameEvent::GAME_EVENT, [
            'cardId' => 'monster',
            'fromPlayerId' => 'player1',
            'toPlayerId' => 'player2',
        ]);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(0, $newState->getPlayer('player1')->playArea->monsterCards);
        self::assertContains('monster', $newState->getPlayer('player2')->playArea->monsterCards);
        self::assertSame('player2', $newState->getCardState('monster')->ownerId);
    }

    public function testApplyCardStolenThrowsForInvalidCardIdInsteadOfFatalError(): void
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('CardStolen requires a valid cardId');

        $event = new GameEvent(1, GameEventTypeEnum::CARD_STOLEN, GameEvent::GAME_EVENT, [
            'cardId' => 'does-not-exist',
            'fromPlayerId' => 'player1',
            'toPlayerId' => 'player2',
        ]);

        $eventApplier->apply($event, $state);
    }

    public function testApplyCardRedrawn()
    {
        $eventApplier = $this->getSut();
        $state = $this->getInitialGameState(cards: []);
        $event = new GameEvent(1, GameEventTypeEnum::CARD_REDRAWN, GameEvent::PLAYER_EVENT, ['playerId' => 'player2', 'cardId' => 'card']);

        $newState = $eventApplier->apply($event, $state);

        self::assertCount(1, $newState->cards);
        self::assertEquals(new CardState('card', 'D6', 'player2', []), $newState->cards['card']);
        self::assertContains('card', $newState->player2->hand);
    }

    private function getSut(array $cards = []): GameEventApplier
    {
        return new GameEventApplier(new CardRuntimeMap(new CardFactory(new MockCardRegistry($cards))));
    }

    private function getInitialGameState(int $lastEventId = 1, array $player2Hand = [], ?array $cards = null): GameState
    {
        return new GameState(
            new PlayerState(
                new Player('player1', 'Alice'),
                30,
                30,
                '',
                [],
                [
                    'id' => 'D6',
                ],
                0,
                new PlayArea([], ['monster']),
            ),
            new PlayerState(
                new Player('player2', 'Bob'),
                30,
                30,
                'character_2',
                $player2Hand,
                [
                    'id' => 'D6',
                ],
                0,
                new PlayArea(),
                [
                    'card' => 'D6',
                ],
            ),
            $lastEventId,
            0,
            null,
            null !== $cards
                ? $cards
                : [
                    'monster' => MonsterCardState::fromParent(new CardState('monster', 'Redbloons', 'player1'), 10),
                ],
        );
    }
}
