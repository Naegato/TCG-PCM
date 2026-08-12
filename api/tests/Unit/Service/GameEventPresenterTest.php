<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Player;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use App\Game\State\PlayerState;
use App\Service\Game\CardFactory;
use App\Service\Game\GameStateConverter;
use App\Service\GameEventPresenter;
use App\Tests\Resources\MockCardRegistry;
use App\Tests\Unit\Game\Card\GameUtilsContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GameEventPresenterTest extends TestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->setGameUtilsContainer(new class($translator) implements ContainerInterface {
            public function __construct(
                private TranslatorInterface $translator,
            ) {}

            public function get(string $id): mixed
            {
                return 'translator' === $id ? $this->translator : throw new \RuntimeException("Unexpected service \"{$id}\"");
            }

            public function has(string $id): bool
            {
                return 'translator' === $id;
            }
        });
    }

    protected function tearDown(): void
    {
        $this->restoreGameUtilsContainer();
        parent::tearDown();
    }

    public function testCardDrawnViewUsesExplicitCardIdWhenPresent(): void
    {
        $presenter = $this->getSut();
        // player1's hand already contains an older card; the newly drawn card is appended last
        $state = $this->getInitialGameState(player1Hand: ['older-card', 'newly-drawn-card']);

        $event = new GameEvent(1, GameEventTypeEnum::CARD_DRAWN, GameEvent::PLAYER_EVENT, [
            'playerId' => 'player1',
            'cardId' => 'newly-drawn-card',
        ]);

        $view = $presenter->present($event, $state, false, null)['view'];

        self::assertSame('newly-drawn-card', $view['cardId']);
    }

    public function testCardDrawnViewIncludesCardForThePrivateViewer(): void
    {
        $presenter = $this->getSut();
        $state = $this->getInitialGameState(player1Hand: ['older-card', 'newly-drawn-card']);

        $event = new GameEvent(1, GameEventTypeEnum::CARD_DRAWN, GameEvent::PLAYER_EVENT, [
            'playerId' => 'player1',
            'cardId' => 'newly-drawn-card',
        ]);

        $view = $presenter->present($event, $state, true, 'player1')['view'];

        self::assertSame('newly-drawn-card', $view['cardId']);
        self::assertArrayHasKey('card', $view);
        self::assertSame('newly-drawn-card', $view['card']['instanceId']);
    }

    public function testCardDrawnViewInfersLastCardWhenNoExplicitCardId(): void
    {
        $presenter = $this->getSut();
        $state = $this->getInitialGameState(player1Hand: ['older-card', 'freshly-drawn']);

        $event = new GameEvent(1, GameEventTypeEnum::CARD_DRAWN, GameEvent::PLAYER_EVENT, [
            'playerId' => 'player1',
        ]);

        $view = $presenter->present($event, $state, false, null)['view'];

        self::assertSame('freshly-drawn', $view['cardId']);
    }

    public function testCardDrawnViewIsStableAcrossPublicAndPrivatePresentationOfTheSameEvent(): void
    {
        // Mirrors GameEventPublisher::publish(), which presents the very same GameEvent
        // object once for the public topic and once more for the drawing player's
        // private topic. Both presentations of one real draw must resolve to the same
        // card, not shift due to the offset being consumed twice.
        $presenter = $this->getSut();
        $state = $this->getInitialGameState(player1Hand: ['given-earlier', 'freshly-drawn']);

        $event = new GameEvent(1, GameEventTypeEnum::CARD_DRAWN, GameEvent::PLAYER_EVENT, [
            'playerId' => 'player1',
        ]);

        $publicView = $presenter->present($event, $state, false, null)['view'];
        $privateView = $presenter->present($event, $state, true, 'player1')['view'];

        self::assertSame('freshly-drawn', $publicView['cardId']);
        self::assertSame('freshly-drawn', $privateView['cardId']);
        self::assertSame('freshly-drawn', $privateView['card']['instanceId']);
    }

    private function getSut(): GameEventPresenter
    {
        return new GameEventPresenter(new GameStateConverter(new CardFactory(new MockCardRegistry())));
    }

    private function getInitialGameState(array $player1Hand): GameState
    {
        return new GameState(
            new PlayerState(new Player('player1', 'Alice'), 30, 30, '', $player1Hand, [], 0, new PlayArea()),
            new PlayerState(new Player('player2', 'Bob'), 30, 30, '', [], [], 0, new PlayArea()),
            1,
            0,
            null,
            [
                'older-card' => new CardState('older-card', 'D6', 'player1'),
                'newly-drawn-card' => new CardState('newly-drawn-card', 'D6', 'player1'),
                'freshly-drawn' => new CardState('freshly-drawn', 'D6', 'player1'),
                'given-earlier' => new CardState('given-earlier', 'D6', 'player1'),
            ],
        );
    }
}
