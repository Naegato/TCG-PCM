<?php

declare(strict_types=1);

namespace App\Tests\Game\Card;

use App\Game\Card\D6Card;
use App\Game\GameContext;
use App\Game\Player;
use App\Tests\Fixtures\DummyCard;
use PHPUnit\Framework\Attributes\DataProvider;

final class D6Test extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return D6Card::class;
    }

    #[DataProvider('d6RollsProvider')]
    public function test6DrawCards(int $roll): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls($roll);
        $player = new Player(
            '1',
            0,
            [],
            [
                new DummyCard(),
                new DummyCard(),
                new DummyCard(),
                new DummyCard(),
                new DummyCard(),
                new DummyCard(),
                new DummyCard(),
            ],
        );
        $ctx = new GameContext([
            $player,
            new Player(
                '2',
                0,
                [],
                [],
            ),
        ]);

        $card->play($ctx);

        $this->assertSame($roll, $player->getHandSize());
    }

    public static function d6RollsProvider(): \Generator
    {
        yield from self::allRollFromGenerator(6);
    }
}
