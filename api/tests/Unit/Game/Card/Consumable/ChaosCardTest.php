<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\ChaosCard;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\Monster\ViciousBeeCard;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\Helper\CardHelper;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\GameUtilsContainerTrait;
use Psr\Container\ContainerInterface;

final class ChaosCardTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $cardRegistry = $this->createStub(CardRegistryInterface::class);
        $cardRegistry->method('getAllBy')->willReturnCallback(static fn(array $criteria): array => (
            AbstractMonsterCard::class === $criteria['type'] ? ['ViciousBee'] : ['SomePassive']
        ));

        $cardFactory = $this->createStub(CardFactoryInterface::class);
        $cardFactory->method('create')->willReturn(new ViciousBeeCard());

        $cardHelper = new CardHelper($cardRegistry, $this->createStub(CardIdGeneratorInterface::class), $cardFactory);

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
        return ChaosCard::class;
    }

    public function testCardReplacesMonsterInPlayWithRandomMonster()
    {
        $card = $this->getCard();
        $player1State = $this->createPlayerState('1')->withPlayArea(new PlayArea([], ['m1']));
        $player2State = $this->createPlayerState('2');
        $state = new GameState($player1State, $player2State, 1, 0, null, [
            'm1' => new CardState('m1', 'ViciousBee', '1', []),
        ]);
        $ctx = $this->createGameContext($state);

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);
        self::assertSame(GameEventTypeEnum::CARD_DISCARDED, $events[0]->type);
        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[1]->type);
        self::assertSame('ViciousBee', $events[1]->data['cardTemplateId']);
        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, $events[2]->type);
        self::assertSame(10, $events[2]->data['cardHealthPoints']);
    }

    public function testCardWithNoCardsInPlayDoesNothing()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->play($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }
}
