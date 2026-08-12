<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\HorsepillCard;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\TestableGameContext;
use PHPUnit\Framework\Attributes\DataProvider;

final class HorsepillCardTest extends CardTestCase
{
    protected function getCardFQCN(): string
    {
        return HorsepillCard::class;
    }

    private function buildState(): GameState
    {
        $player1 = $this
            ->createPlayerState('1')
            ->withPlayArea(new PlayArea(['passive1'], ['monster1']))
            ->withNewHandAndDeck(['test_card', 'ownerHandCard'], []);
        $player2 = $this->createPlayerState('2')->withNewHandAndDeck(['oppHandCard'], []);

        return new GameState($player1, $player2, 1, 0, '1', [
            'test_card' => new CardState('test_card', 'Horsepill', '1', []),
            'ownerHandCard' => new CardState('ownerHandCard', 'SomeCard', '1', []),
            'oppHandCard' => new CardState('oppHandCard', 'SomeCard', '2', []),
        ]);
    }

    #[DataProvider('effectsProvider')]
    public function testPlayUsesExpectedEffect(int $roll, GameEventTypeEnum $expectedType, array $expectedData): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls($roll);
        $state = $this->buildState();
        $ctx = $this->createDeterministicContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame($expectedType, $events[0]->type);
        self::assertSame($expectedData, $events[0]->data);
    }

    public static function effectsProvider(): \Generator
    {
        yield 'self damage' => [1, GameEventTypeEnum::DAMAGE_DEALT, ['targetId' => '1', 'damage' => 50]];
        yield 'self heal' => [2, GameEventTypeEnum::HEAL_APPLIED, ['targetId' => '1', 'amount' => 50]];
        yield 'discard random board card' => [3, GameEventTypeEnum::CARD_DISCARDED, ['cardId' => 'monster1', 'playerId' => null]];
        yield 'set monster attack 99' => [4, GameEventTypeEnum::CARD_STATE_UPDATED, ['cardId' => 'monster1', 'stateToUpdate' => ['forcedAttack' => 99]]];
        yield 'set monster attack 0' => [5, GameEventTypeEnum::CARD_STATE_UPDATED, ['cardId' => 'monster1', 'stateToUpdate' => ['forcedAttack' => 0]]];
        yield 'discard owner hand' => [6, GameEventTypeEnum::CARD_DISCARDED, ['cardId' => 'ownerHandCard', 'playerId' => '1']];
        yield 'discard opponent hand' => [7, GameEventTypeEnum::CARD_DISCARDED, ['cardId' => 'oppHandCard', 'playerId' => '2']];
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
