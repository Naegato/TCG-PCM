<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\PillsCard;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\TestableGameContext;
use PHPUnit\Framework\Attributes\DataProvider;

final class PillsCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return PillsCard::class;
    }

    #[DataProvider('withoutMonsterEffectsProvider')]
    public function testPlayWithoutMonsterUsesExpectedEffect(int $roll, GameEventTypeEnum $expectedType, array $expectedData): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls($roll);
        $ctx = $this->createDeterministicContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame($expectedType, $events[0]->type);
        self::assertSame($expectedData, $events[0]->data);
    }

    #[DataProvider('withMonsterEffectsProvider')]
    public function testPlayWithMonsterUsesExpectedEffect(int $roll, GameEventTypeEnum $expectedType, array $expectedData): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls($roll);

        $player1 = $this->createPlayerState('1')->withPlayArea(new PlayArea([], ['monster1']));
        $player2 = $this->createPlayerState('2');

        $state = new GameState($player1, $player2, 1, 0, '1', [
            'test_card' => new CardState('test_card', 'Pills', '1', []),
            'monster1' => new CardState('monster1', 'SomeMonster', '1', []),
        ]);

        $ctx = $this->createDeterministicContext($state);
        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame($expectedType, $events[0]->type);
        self::assertSame($expectedData, $events[0]->data);
    }

    public static function withoutMonsterEffectsProvider(): \Generator
    {
        yield 'self damage' => [1, GameEventTypeEnum::DAMAGE, ['targetId' => '1', 'damage' => 5]];
        yield 'self heal' => [2, GameEventTypeEnum::HEAL, ['targetId' => '1', 'amount' => 10]];
    }

    public static function withMonsterEffectsProvider(): \Generator
    {
        yield 'self damage' => [1, GameEventTypeEnum::DAMAGE, ['targetId' => '1', 'damage' => 5]];
        yield 'self heal' => [2, GameEventTypeEnum::HEAL, ['targetId' => '1', 'amount' => 10]];
        yield 'monster damage buff' => [3, GameEventTypeEnum::UPDATE_CARD_STATE, ['cardId' => 'monster1', 'stateToUpdate' => ['bonusAttack' => 5]]];
        yield 'monster heal' => [4, GameEventTypeEnum::HEAL, ['targetId' => 'monster1', 'amount' => 5]];
        yield 'monster damage debuff' => [5, GameEventTypeEnum::UPDATE_CARD_STATE, ['cardId' => 'monster1', 'stateToUpdate' => ['bonusAttack' => -3]]];
        yield 'monster damage' => [6, GameEventTypeEnum::DAMAGE, ['targetId' => 'monster1', 'damage' => 3]];
    }

    private function createDeterministicContext(?GameState $state = null): TestableGameContext
    {
        $context = $this->createGameContext($state);

        return new class($context->state, $context->playerId, $context->nextRoll) extends TestableGameContext {
            public function getRandomFromArray(array $array): mixed
            {
                if ([] === $array) {
                    throw new \LogicException('No values available to select');
                }

                $values = array_values($array);
                $index = max(0, min(\count($values) - 1, (int) $this->nextRoll - 1));

                return $values[$index];
            }
        };
    }
}
