<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\FortniteCard;
use App\Game\Card\MonsterCardState;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Tests\Unit\Game\Card\CardTestCase;

final class FortniteCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return FortniteCard::class;
    }

    public function testPlayWithSingleMonsterHealsBuffsAndDiscardsRest(): void
    {
        $card = $this->getCard();

        $player1 = $this->createPlayerState('1')->withPlayArea(new PlayArea(['passive1'], ['monster1']));
        $player2 = $this->createPlayerState('2')->withPlayArea(new PlayArea(['passive2'], []));

        $state = new GameState($player1, $player2, 1, 0, '1', [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'monster1' => new MonsterCardState('monster1', 'SomeMonster', '1', 20, [], ['bonusAttack' => 1]),
        ]);

        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(4, $events);

        $discardEvents = array_values(array_filter($events, static fn($e) => GameEventTypeEnum::CARD_DISCARDED === $e->type));
        self::assertCount(2, $discardEvents);
        $discardedIds = array_map(static fn($e) => $e->data['cardId'], $discardEvents);
        self::assertContains('passive1', $discardedIds);
        self::assertContains('passive2', $discardedIds);

        $healEvents = array_values(array_filter($events, static fn($e) => GameEventTypeEnum::HEAL_APPLIED === $e->type));
        self::assertCount(1, $healEvents);
        self::assertSame('monster1', $healEvents[0]->data['targetId']);
        self::assertSame(6, $healEvents[0]->data['amount']);

        $updateEvents = array_values(array_filter($events, static fn($e) => GameEventTypeEnum::CARD_STATE_UPDATED === $e->type));
        self::assertCount(1, $updateEvents);
        self::assertSame('monster1', $updateEvents[0]->data['cardId']);
        self::assertSame(8, $updateEvents[0]->data['stateToUpdate']['bonusAttack']);
    }

    public function testPlayThrowsWhenNoMonstersInPlay(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $this->expectException(\InvalidArgumentException::class);

        $card->play($ctx);
    }
}
