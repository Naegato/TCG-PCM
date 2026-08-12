<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Character;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Character\CharlieCard;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\Helper\CardHelper;
use App\Tests\Unit\Game\Card\CardTestCase;
use App\Tests\Unit\Game\Card\GameUtilsContainerTrait;
use Psr\Container\ContainerInterface;

final class CharlieCardTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $cardRegistry = $this->createStub(CardRegistryInterface::class);
        $cardRegistry->method('getAllBy')->willReturn(['SomePassive']);

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
        return CharlieCard::class;
    }

    public function testTurnActionGeneratesRandomPassiveCardForCurrentPlayer()
    {
        $card = $this->getCard();
        $ctx = $this->createGameContext();

        $card->onTurnAction($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(2, $events);
        self::assertSame(GameEventTypeEnum::CARD_GENERATED, $events[0]->type);
        self::assertSame('SomePassive', $events[0]->data['cardTemplateId']);
        self::assertSame(GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA, $events[1]->type);
    }

    public function testTurnActionDoesNothingWhenNotCurrentPlayer()
    {
        $card = $this->getCard();
        $gameState = $this->createGameContext()->state;
        $gameState = $gameState->withCurrentPlayer($gameState->player2->player->id);
        $ctx = $this->createGameContext($gameState);

        $card->onTurnAction($ctx);
        $events = $ctx->flushEvents();

        self::assertCount(0, $events);
    }
}
