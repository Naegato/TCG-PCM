<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\CommunismCard;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Tests\Unit\Fixtures\DummyCard;
use App\Tests\Unit\Game\Card\CardTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CommunismCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return CommunismCard::class;
    }

    /**
     * @param GameEvent[] $expectedEvents
     */
    #[DataProvider('provideCoinDistributionScenarios')]
    public function testCommunismCardRedistributesCoinsEqually(int $player1Coins, int $player2Coins, array $expectedEvents): void
    {
        /** @var CommunismCard $card */
        $card = $this->getCard();
        $ctx = $this->createGameContextWithCoins($player1Coins, $player2Coins);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertEquals($expectedEvents, $events);
    }

    public static function provideCoinDistributionScenarios(): \Generator
    {
        yield 'even split with 10 total coins' => [
            5,
            5,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'even split with 100 total coins' => [
            50,
            50,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'even split with 20 total coins' => [
            10,
            10,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'odd total with 11 coins: player 1 gets extra' => [
            6,
            5,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'odd total with 21 coins: player 1 gets extra' => [
            11,
            10,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'odd total with 3 coins: player 1 gets extra' => [
            2,
            1,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'both players no coins' => [
            0,
            0,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'only player 1 has 1 coin' => [
            1,
            0,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 0),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 0),
            ],
        ];

        yield 'only player 2 has 1 coin' => [
            0,
            1,
            [
                self::coinEvent(GameEventTypeEnum::COINS_GAINED, '1', 1),
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '2', 1),
            ],
        ];

        yield 'player 1 has 2 coins, player 2 has 0' => [
            2,
            0,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 1),
                self::coinEvent(GameEventTypeEnum::COINS_GAINED, '2', 1),
            ],
        ];

        yield 'player 1 has 3 coins, player 2 has 0' => [
            3,
            0,
            [
                self::coinEvent(GameEventTypeEnum::COINS_LOST, '1', 1),
                self::coinEvent(GameEventTypeEnum::COINS_GAINED, '2', 1),
            ],
        ];
    }

    private static function coinEvent(GameEventTypeEnum $type, string $playerId, int $amount): GameEvent
    {
        return new GameEvent(0, $type, GameEvent::GAME_EVENT, [
            'playerId' => $playerId,
            'amount' => $amount,
        ]);
    }

    private function createGameContextWithCoins(int $player1Coins, int $player2Coins): GameContext
    {
        $player1State = new PlayerState(new Player('1', 'Player 1', 67), 30, 30, '', [], [], $player1Coins, new PlayArea());

        $player2State = new PlayerState(new Player('2', 'Player 2', 67), 30, 30, '', [], [], $player2Coins, new PlayArea());

        $state = new GameState($player1State, $player2State, 1, 0, null, [
            'test_card' => new CardState('test_card', DummyCard::class, '1', []),
        ]);

        return new GameContext($state, '1');
    }
}
