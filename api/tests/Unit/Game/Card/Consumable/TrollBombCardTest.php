<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\TrollBombCard;
use App\Game\State\GameState;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\Helper\CardHelper;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\GameUtilsContainerTrait;
use Psr\Container\ContainerInterface;

final class TrollBombCardTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $cardRegistry = $this->createStub(CardRegistryInterface::class);
        $cardRegistry->method('getAllBy')->willReturnCallback(static fn(array $criteria): array => (
            ['bomb'] === ($criteria['groups'] ?? null) ? ['TrollBomb'] : []
        ));

        $cardHelper = new CardHelper($cardRegistry, $this->createStub(CardIdGeneratorInterface::class), $this->createStub(CardFactoryInterface::class));

        $this->setGameUtilsContainer(new class($cardHelper) implements ContainerInterface {
            public function __construct(
                private CardHelper $cardHelper,
            ) {}

            public function get(string $id): mixed
            {
                return 'cards' === $id ? $this->cardHelper : throw new \RuntimeException("Unexpected service \"{$id}\"");
            }

            public function has(string $id): bool
            {
                return 'cards' === $id;
            }
        });
    }

    protected function tearDown(): void
    {
        $this->restoreGameUtilsContainer();
        parent::tearDown();
    }

    public function getCardFQCN(): string
    {
        return TrollBombCard::class;
    }

    public function testPlayDealsMinorDamageOnLowRoll(): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls(9);
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::DAMAGE_DEALT, $events[0]->type);
        self::assertSame('2', $events[0]->data['targetId']);
        self::assertSame(1, $events[0]->data['damage']);
    }

    public function testPlayDetonatesAllBombsOnJackpotRoll(): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls(10);

        $state = new GameState($this->createPlayerState('1'), $this->createPlayerState('2'), 1, 0, null, [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'bomb1' => new CardState('bomb1', 'TrollBomb', '1', []),
        ]);
        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_CONSUMED, $events[0]->type);
        self::assertSame('test_card', $events[0]->data['cardId']);
        self::assertSame('1', $events[0]->data['playerId']);

        self::assertSame(GameEventTypeEnum::CARD_CONSUMED, $events[1]->type);
        self::assertSame('bomb1', $events[1]->data['cardId']);
        self::assertSame('1', $events[1]->data['playerId']);
    }
}
