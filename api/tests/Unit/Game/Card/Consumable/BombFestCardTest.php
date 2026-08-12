<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\BombFestCard;
use App\Game\State\GameState;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\Helper\CardHelper;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\GameUtilsContainerTrait;
use Psr\Container\ContainerInterface;

final class BombFestCardTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $cardRegistry = $this->createStub(CardRegistryInterface::class);
        $cardRegistry->method('getAllBy')->willReturnCallback(static fn(array $criteria): array => ['bomb'] === ($criteria['groups'] ?? null) ? ['Bomb'] : []);

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
        return BombFestCard::class;
    }

    public function testCardPlacesAllBombsInPlayAndGrantsCoinsPerBomb(): void
    {
        $card = $this->getCard();

        $state = new GameState($this->createPlayerState('1'), $this->createPlayerState('2'), 1, 0, null, [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'bomb1' => new CardState('bomb1', 'Bomb', '1', []),
            'bomb2' => new CardState('bomb2', 'Bomb', '2', []),
            'other' => new CardState('other', 'DummyCard', '1', []),
        ]);
        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);

        self::assertSame(GameEventTypeEnum::CARD_CONSUMED, $events[0]->type);
        self::assertSame('bomb1', $events[0]->data['cardId']);
        self::assertSame('1', $events[0]->data['playerId']);

        self::assertSame(GameEventTypeEnum::CARD_CONSUMED, $events[1]->type);
        self::assertSame('bomb2', $events[1]->data['cardId']);
        self::assertSame('2', $events[1]->data['playerId']);

        self::assertSame(GameEventTypeEnum::COINS_GAINED, $events[2]->type);
        self::assertSame('1', $events[2]->data['playerId']);
        self::assertSame(2, $events[2]->data['amount']);
    }

    public function testCardWithNoBombsOnlyGrantsZeroCoins(): void
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::COINS_GAINED, $events[0]->type);
        self::assertSame(0, $events[0]->data['amount']);
    }
}
