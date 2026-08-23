<?php

declare(strict_types=1);

namespace App\Tests\GameReplay;

use App\Game\AbstractCard;
use App\Game\State\GameState;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardRegistryInterface;
use App\Service\Game\CardRuntimeMap;
use App\Service\Game\Factory\ReplayableGameContextFactory;
use App\Service\Game\GameEventApplier;
use App\Service\Game\GameEventResolver;
use App\Service\Game\GameStateRebuilder;
use App\Tests\Resources\MockCardRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('replay')]
final class GameReplayTest extends KernelTestCase
{
    private const REPLAY_DIR = __DIR__.'/resources';

    #[DataProvider('replayProvider')]
    public function testReplay(string $fileName)
    {
        self::bootKernel();
        if (!file_exists($fileName = \sprintf('%s/%s.php', self::REPLAY_DIR, $fileName))) {
            $this->markTestSkipped(sprintf('Replay file "%s.php" not found.', $fileName));
        }

        $data = require $fileName;

        $gameState = $data['initialGameState'];
        $events = $data['events'];

        $gameReplayer = $this->getGameStateRebuilder();
        $gameState = $gameReplayer->rebuild($gameState, $events);

        // lastAddedCardId is a transient field not persisted in fixtures; rebuild a clean
        // state the same way ExportGameReplayCommand does when generating them.
        $gameState = new GameState(
            $gameState->player1,
            $gameState->player2,
            $gameState->lastEventId,
            $gameState->seed,
            $gameState->currentPlayer,
            $gameState->cards,
        );

        self::assertEquals($data['finalGameState'], $gameState);
    }

    public static function replayProvider(): array
    {
        return [
            'replay1' => ['replay1'],
            'replay2' => ['replay2'],
            'replay3' => ['replay3'],
            'replay4' => ['replay4'],
            'replay5' => ['replay5'],
            'replay6' => ['replay6'],
            'replay7' => ['replay7'],
            'replay8' => ['replay8'],
            'replay9' => ['replay9'],
            'replay10' => ['replay10'],
        ];
    }

    private function getGameStateRebuilder(): GameStateRebuilder
    {
        $cardsListPath = dirname(__DIR__, 2).'/resources/cards_list.php';

        $cardRuntimeMap = new CardRuntimeMap(new TestCardFactory(new MockCardRegistry(array_merge(require $cardsListPath, $this->getDummiesCard()))));

        return new GameStateRebuilder(new GameEventResolver($cardRuntimeMap, new ReplayableGameContextFactory(), new GameEventApplier($cardRuntimeMap)));
    }

    private function getDummiesCard(): array
    {
        return [
            'dummy_character' => DummyCharacterCard::class,
        ];
    }
}

class TestCardFactory implements CardFactoryInterface
{
    public function __construct(
        private CardRegistryInterface $cardRegistry,
    ) {}

    public function create(string $cardId): AbstractCard
    {
        return clone $this->cardRegistry->getCardTemplateById($cardId);
    }

    public function createWithState(string $cardId, \App\Game\Card\CardState $state): AbstractCard
    {
        $card = $this->create($cardId);
        $card->setState($state);

        return $card;
    }
}
