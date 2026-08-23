<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Character;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Character\DataCenterCard;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\Helper\CardHelper;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\GameUtilsContainerTrait;
use Psr\Container\ContainerInterface;

final class DataCenterCardTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $spawnedMonster = $this->createStub(\App\Game\Card\Monster\AbstractMonsterCard::class);
        $spawnedMonster->method('getHealPoints')->willReturn(42);

        $cardFactory = $this->createStub(CardFactoryInterface::class);
        $cardFactory->method('create')->willReturn($spawnedMonster);

        $cardHelper = new CardHelper($this->createStub(CardRegistryInterface::class), $this->createStub(CardIdGeneratorInterface::class), $cardFactory);

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
        return DataCenterCard::class;
    }

    public function testOnTurnStartDoesNothingWhenNotOwnerTurn(): void
    {
        $card = $this->buildCard(1);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('2'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }

    public function testOnTurnStartOnlyDecrementsWhenDelayNotReached(): void
    {
        $card = $this->buildCard(2);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[0]->type);
        self::assertSame(1, $events[0]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    public function testOnTurnStartSpawnsMonsterWhenDelayIsReached(): void
    {
        $card = $this->buildCard(1);
        $ctx = $this->createGameContext();

        $card->onTurnStart($this->createTurnStartedEvent('1'), $ctx);
        $events = $ctx->flushEvents();

        self::assertCount(3, $events);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('1', $events[0]->data['playerId']);
        self::assertSame('DataMiner', $events[0]->data['cardTemplateId']);

        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, $events[1]->type);
        self::assertSame('1', $events[1]->data['playerId']);
        self::assertSame(42, $events[1]->data['cardHealthPoints']);

        self::assertSame(GameEventTypeEnum::CARD_STATE_UPDATED, $events[2]->type);
        self::assertSame(1, $events[2]->data['stateToUpdate']['turnRemainingBeforeAction']);
    }

    private function buildCard(int $turnRemainingBeforeAction): DataCenterCard
    {
        $card = new DataCenterCard();
        $card->setState(
            new CardState(
                'test_card',
                $card->getId(),
                '1',
                [],
                [
                    'turnRemainingBeforeAction' => $turnRemainingBeforeAction,
                ],
            ),
        );

        return $card;
    }
}
