<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card;

use App\Enum\GameEventTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\CardActions;
use App\Game\Card\CardState;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\GameContext;
use App\Game\Player;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\Helper\CardHelper;
use App\Tests\Unit\Fixtures\DummyCard;
use Psr\Container\ContainerInterface;

final class CardActionsTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function tearDown(): void
    {
        $this->restoreGameUtilsContainer();
        parent::tearDown();
    }

    public function getCardFQCN(): string
    {
        return DummyCard::class;
    }

    private function setCardsService(CardHelper $cardHelper): void
    {
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

    public function testGetAllMonsterMergesCurrentAndOpponentMonsterCards(): void
    {
        $player1State = new PlayerState(new Player('1', 'Player 1'), 30, 30, 'char1', [], [], 0, new PlayArea([], ['m1', 'm2']));
        $player2State = new PlayerState(new Player('2', 'Player 2'), 30, 30, 'char2', [], [], 0, new PlayArea([], ['m3']));

        $state = new GameState($player1State, $player2State, 1, 0);
        $ctx = new GameContext($state, '1');

        self::assertSame(['m1', 'm2', 'm3'], CardActions::getAllMonster($ctx));
    }

    public function testGeneratedAndPlayPushesCardGeneratedThenPlacedInPlayAreaWhenNotMonster(): void
    {
        $ctx = $this->createGameContext();

        CardActions::generatedAndPlay($ctx, '1', 'SomeTemplate', false);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('1', $events[0]->data['playerId']);
        self::assertSame('SomeTemplate', $events[0]->data['cardTemplateId']);

        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, $events[1]->type);
        self::assertSame('1', $events[1]->data['playerId']);
        self::assertSame($events[0]->data['cardInstanceId'], $events[1]->data['cardId']);
    }

    public function testGeneratedAndPlayPushesCardGeneratedThenPlacedInMonsterAreaWhenMonster(): void
    {
        $spawnedMonster = $this->createStub(AbstractMonsterCard::class);
        $spawnedMonster->method('getHealPoints')->willReturn(42);

        $cardFactory = $this->createStub(CardFactoryInterface::class);
        $cardFactory->method('create')->willReturn($spawnedMonster);

        $this->setCardsService(
            new CardHelper($this->createStub(CardRegistryInterface::class), $this->createStub(CardIdGeneratorInterface::class), $cardFactory),
        );

        $ctx = $this->createGameContext();

        CardActions::generatedAndPlay($ctx, '1', 'SomeMonsterTemplate', true);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('SomeMonsterTemplate', $events[0]->data['cardTemplateId']);

        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA, $events[1]->type);
        self::assertSame('1', $events[1]->data['playerId']);
        self::assertSame($events[0]->data['cardInstanceId'], $events[1]->data['cardId']);
        self::assertSame(42, $events[1]->data['cardHealthPoints']);
    }

    public function testGeneratedAndPlayThrowsWhenMonsterFlagButTemplateIsNotAMonster(): void
    {
        $cardFactory = $this->createStub(CardFactoryInterface::class);
        $cardFactory->method('create')->willReturn($this->createStub(AbstractCard::class));

        $this->setCardsService(
            new CardHelper($this->createStub(CardRegistryInterface::class), $this->createStub(CardIdGeneratorInterface::class), $cardFactory),
        );

        $ctx = $this->createGameContext();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Chaos picked a non-monster template for a monster slot');

        CardActions::generatedAndPlay($ctx, '1', 'NotAMonster', true);
    }

    public function testGenerateAndDrawPushesCardGeneratedThenCardDrawn(): void
    {
        $ctx = $this->createGameContext();

        CardActions::generateAndDraw($ctx, '1', 'SomeTemplate');
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);

        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('1', $events[0]->data['playerId']);
        self::assertSame('SomeTemplate', $events[0]->data['cardTemplateId']);

        self::assertSame(GameEventTypeEnum::CARD_DRAWN, $events[1]->type);
        self::assertSame('1', $events[1]->data['playerId']);
        self::assertSame($events[0]->data['cardInstanceId'], $events[1]->data['cardId']);
    }

    public function testGetAllCardInGroupsFiltersCardsByTemplatesInGroup(): void
    {
        $cardRegistry = $this->createStub(CardRegistryInterface::class);
        $cardRegistry->method('getAllBy')->willReturn(['BombTemplate']);

        $this->setCardsService(
            new CardHelper($cardRegistry, $this->createStub(CardIdGeneratorInterface::class), $this->createStub(CardFactoryInterface::class)),
        );

        $player1State = $this->createPlayerState('1');
        $player2State = $this->createPlayerState('2');

        $state = new GameState($player1State, $player2State, 1, 0, null, [
            'bomb1' => new CardState('bomb1', 'BombTemplate', '1', []),
            'other1' => new CardState('other1', 'OtherTemplate', '1', []),
        ]);

        $ctx = new GameContext($state, '1');

        $result = CardActions::getAllCardInGroups($ctx, 'bomb');

        self::assertArrayHasKey('bomb1', $result);
        self::assertArrayNotHasKey('other1', $result);
        self::assertCount(1, $result);
    }
}
