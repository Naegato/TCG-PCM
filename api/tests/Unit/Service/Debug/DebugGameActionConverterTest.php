<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Debug;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Debug\DebugGameAction;
use App\Service\Debug\DebugGameActionConverter;
use App\Service\Game\CardFactory;
use App\Service\Game\CardIdGeneratorInterface;
use App\Tests\Resources\MockCardRegistry;
use PHPUnit\Framework\TestCase;

final class DebugGameActionConverterTest extends TestCase
{
    public function testGiveCardToHand(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::GIVE_CARD, 'game1', [
            'playerId' => 'player1',
            'cardTemplateId' => 'D6',
        ]), $state);

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('player1', $events[0]->data['playerId']);
        self::assertSame('D6', $events[0]->data['cardTemplateId']);
        self::assertSame('generated-id', $events[0]->data['cardInstanceId']);
        self::assertSame(GameEventTypeEnum::CARD_DRAWN, $events[1]->type);
        self::assertSame('generated-id', $events[1]->data['cardId']);
    }

    public function testGiveMonsterCardToBoard(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::GIVE_CARD, 'game1', [
            'playerId' => 'player1',
            'cardTemplateId' => 'Redbloons',
            'zone' => 'board',
        ]), $state);

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, $events[1]->type);
        self::assertGreaterThan(0, $events[1]->data['cardHealthPoints']);
    }

    public function testGiveNonMonsterCardToBoard(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::GIVE_CARD, 'game1', [
            'playerId' => 'player1',
            'cardTemplateId' => 'D6',
            'zone' => 'board',
        ]), $state);

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, $events[1]->type);
    }

    public function testGiveCardThrowsForUnknownPlayer(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $this->expectException(\InvalidArgumentException::class);

        $converter->convert(new DebugGameAction('admin', DebugGameAction::GIVE_CARD, 'game1', [
            'playerId' => 'does-not-exist',
            'cardTemplateId' => 'D6',
        ]), $state);
    }

    public function testSetStatsEmitsDamageWhenHealthDecreases(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::SET_STATS, 'game1', [
            'playerId' => 'player1',
            'healthPoints' => 10,
        ]), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame(15, $events[0]->data['damage']);
    }

    public function testSetStatsEmitsHealWhenHealthIncreases(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::SET_STATS, 'game1', [
            'playerId' => 'player1',
            'healthPoints' => 30,
        ]), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::HEAL_APPLIED, $events[0]->type);
        self::assertSame(5, $events[0]->data['amount']);
    }

    public function testSetStatsEmitsCoinsGainedAndLost(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::SET_STATS, 'game1', [
            'playerId' => 'player1',
            'coins' => 10,
        ]), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::COINS_GAINED, $events[0]->type);
        self::assertSame(10, $events[0]->data['amount']);

        $state = $state->withUpdatedPlayer($state->player1->withUpdatedCoins(10));

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::SET_STATS, 'game1', [
            'playerId' => 'player1',
            'coins' => 4,
        ]), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::COINS_LOST, $events[0]->type);
        self::assertSame(6, $events[0]->data['amount']);
    }

    public function testSetStatsEmitsNothingWhenValuesUnchanged(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::SET_STATS, 'game1', [
            'playerId' => 'player1',
            'healthPoints' => 25,
            'coins' => 0,
        ]), $state);

        self::assertSame([], $events);
    }

    public function testForceEndTurn(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::FORCE_END_TURN, 'game1', []), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::TURN_ENDED, $events[0]->type);
        self::assertSame('player1', $events[0]->data['playerId']);
    }

    public function testForceSetCurrentPlayer(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::FORCE_SET_CURRENT_PLAYER, 'game1', [
            'playerId' => 'player2',
        ]), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CURRENT_PLAYER_SET, $events[0]->type);
        self::assertSame('player2', $events[0]->data['playerId']);
    }

    public function testForceSetCurrentPlayerThrowsForUnknownPlayer(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $this->expectException(\InvalidArgumentException::class);

        $converter->convert(new DebugGameAction('admin', DebugGameAction::FORCE_SET_CURRENT_PLAYER, 'game1', [
            'playerId' => 'does-not-exist',
        ]), $state);
    }

    public function testRemoveCard(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState(cards: [
            'card1' => new CardState('card1', 'D6', 'player1', []),
        ]);

        $events = $converter->convert(new DebugGameAction('admin', DebugGameAction::REMOVE_CARD, 'game1', [
            'cardId' => 'card1',
        ]), $state);

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[0]->type);
        self::assertSame('card1', $events[0]->data['cardId']);
    }

    public function testRemoveCardThrowsForUnknownCard(): void
    {
        $converter = $this->getSut();
        $state = $this->getInitialGameState();

        $this->expectException(\InvalidArgumentException::class);

        $converter->convert(new DebugGameAction('admin', DebugGameAction::REMOVE_CARD, 'game1', [
            'cardId' => 'does-not-exist',
        ]), $state);
    }

    private function getSut(): DebugGameActionConverter
    {
        $cardIdGenerator = new class implements CardIdGeneratorInterface {
            public function generateCardId(string $templateId): string
            {
                return 'generated-id';
            }
        };

        return new DebugGameActionConverter($cardIdGenerator, new CardFactory(new MockCardRegistry()));
    }

    private function getInitialGameState(?array $cards = null): GameState
    {
        return new GameState(
            new PlayerState(new Player('player1', 'Alice'), 25, 30, '', [], [], 0, new PlayArea()),
            new PlayerState(new Player('player2', 'Bob'), 30, 30, '', [], [], 0, new PlayArea()),
            1,
            0,
            null,
            $cards ?? [],
        );
    }
}
