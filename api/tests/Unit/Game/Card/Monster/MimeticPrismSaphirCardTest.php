<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Monster\MimeticPrismSaphirCard;
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

final class MimeticPrismSaphirCardTest extends CardTestCase
{
    use GameUtilsContainerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $cardRegistry = $this->createStub(CardRegistryInterface::class);
        $cardRegistry->method('getCardTemplateById')->willReturn(new ViciousBeeCard());

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
        return MimeticPrismSaphirCard::class;
    }

    public function testId()
    {
        $card = $this->getCard();

        self::assertSame('MimeticPrismSaphir', $card->getId());
    }

    public function testBaseAttackAndHealPointsAreZeroBeforeMimickingACard()
    {
        // Without having mimicked a monster (via onMonsterPlayed or a persisted
        // 'templateId' state value), damage/heal are never computed and default to 0.
        $card = $this->getCard();

        self::assertSame(1, $card->getBaseAttack());
        self::assertSame(1, $card->getHealPoints());
    }

    public function testOnMonsterPlayedCopiesReducedAttackAndBoostedHeal()
    {
        $card = $this->getCard();
        $player1State = $this->createPlayerState('1')->withPlayArea(new PlayArea([], ['m1']));
        $player2State = $this->createPlayerState('2');
        $state = new GameState($player1State, $player2State, 1, 0, null, [
            'test_card' => new CardState('test_card', $card->getId(), '1', []),
            'm1' => new CardState('m1', 'ViciousBee', '1', []),
        ]);
        $ctx = $this->createGameContext($state);

        $card->onMonsterPlayed($ctx);
        $events = $ctx->flushEvents();

        // ViciousBee base attack = 10 * 0.5 (ATTACK_MULTIPLIER) = 5.
        // ViciousBee heal points = 10 * 2 (HEALTH_POINTS_MULTIPLIER) = 20.
        self::assertSame(5, $card->getBaseAttack());
        self::assertSame(20, $card->getHealPoints());

        $updateEvent = array_values(array_filter($events, static fn($event) => GameEventTypeEnum::CARD_STATE_UPDATED === $event->type));
        self::assertCount(1, $updateEvent);
        self::assertSame('ViciousBee', $updateEvent[0]->data['stateToUpdate']['templateId']);
    }
}
