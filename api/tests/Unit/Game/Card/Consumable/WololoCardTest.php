<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\WololoCard;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class WololoCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return WololoCard::class;
    }

    private function buildState(): GameState
    {
        $player1 = new PlayerState(new Player('1', 'Player 1', 67), 30, 30, 'char1', [], [], 0, new PlayArea());
        $player2 = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, 'char2', [], [], 0, new PlayArea(['stolenCard'], []));

        return new GameState($player1, $player2, 1, 0, '1', [
            'test_card' => new CardState('test_card', 'Wololo', '1', []),
            'stolenCard' => new CardState('stolenCard', 'SomeCard', '2', []),
        ]);
    }

    public function testPlayStealsTargetCardFromOpponent(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState());

        $card->play($ctx, ['target' => 'stolenCard']);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STOLEN, $events[0]->type);
        self::assertSame('stolenCard', $events[0]->data['cardId']);
        self::assertSame('2', $events[0]->data['fromPlayerId']);
        self::assertSame('1', $events[0]->data['toPlayerId']);
    }

    public function testPlayThrowsWhenTargetMissing(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState());

        $this->expectException(\InvalidArgumentException::class);

        $card->play($ctx, []);
    }

    public function testPlayThrowsWhenTargetingOpponentCharacterCard(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState());

        $this->expectException(\InvalidArgumentException::class);

        $card->play($ctx, ['target' => 'char2']);
    }
}
