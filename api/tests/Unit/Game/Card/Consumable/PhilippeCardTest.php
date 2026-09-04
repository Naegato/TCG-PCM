<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\CardEffectEnum;
use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\PhilippeCard;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Game\Card\CardTestCase;

final class PhilippeCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return PhilippeCard::class;
    }

    public function testGetId(): void
    {
        self::assertSame('Philippe', $this->getCard()->getId());
    }

    public function testCostsFourCoins(): void
    {
        $card = $this->getCard();

        self::assertSame(CardRarityEnum::EPIC, $card->getRarity());
        self::assertSame(4, $card->getCost());
    }

    public function testRequiresNoTarget(): void
    {
        /** @var PhilippeCard $card */
        $card = $this->getCard();

        self::assertFalse($card->requiresTarget());
    }

    public function testPlayAppliesTornedToEveryCardInTheOpponentPlayArea(): void
    {
        /** @var PhilippeCard $card */
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState(
            opponentCharacterId: 'char2',
            opponentPlayArea: new PlayArea(['passive1'], ['monster1', 'monster2']),
        ));

        $card->play($ctx);

        $events = $ctx->flushEvents();
        $screamed = [];

        self::assertCount(3, $events);

        foreach ($events as $event) {
            self::assertSame(GameEventTypeEnum::EFFECT_ADDED, $event->type);
            self::assertSame(CardEffectEnum::TORNED->value, $event->data['effect']);
            $screamed[] = $event->data['cardId'];
        }

        self::assertEqualsCanonicalizing(['passive1', 'monster1', 'monster2'], $screamed);
    }

    public function testPlaySparesOwnCardsAndBothCharacters(): void
    {
        /** @var PhilippeCard $card */
        $card = $this->getCard();
        $ctx = $this->createGameContext($this->buildState(opponentCharacterId: 'char2', opponentPlayArea: new PlayArea([], ['monster1'])));

        $card->play($ctx);

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame('monster1', $events[0]->data['cardId']);
    }

    private function buildState(string $opponentCharacterId = '', ?PlayArea $opponentPlayArea = null): GameState
    {
        $player1State = new PlayerState(new Player('1', 'Player 1', 67), 30, 30, 'char1', [], [], 0, new PlayArea(['ownPassive'], ['ownMonster']));
        $player2State = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, $opponentCharacterId, [], [], 0, $opponentPlayArea ?? new PlayArea());

        return new GameState($player1State, $player2State, 1, 0, '1', [
            'test_card' => new CardState('test_card', 'Philippe', '1', []),
        ]);
    }
}
