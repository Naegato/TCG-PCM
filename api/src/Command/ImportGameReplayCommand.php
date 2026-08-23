<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Room;
use App\Enum\RoomStatusEnum;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Repository\DeckRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Service\Game\State\GameEventRepositoryInterface;
use App\Service\Game\State\GameStateRepositoryInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import:game-replay', description: 'Import a game replay from a file.')]
final class ImportGameReplayCommand
{
    public function __construct(
        private GameStateRepositoryInterface $gameStateRepository,
        private GameEventRepositoryInterface $gameEventRepository,
        private RoomRepository $roomRepository,
        private UserRepository $userRepository,
        private DeckRepository $deckRepository,
    ) {}

    public function __invoke(#[Argument('filePath')] string $filePath, OutputInterface $output, #[Option('room')] bool $room = true)
    {
        if (!file_exists($filePath)) {
            $output->writeln(sprintf('<error>File "%s" not found.</error>', $filePath));

            return 1;
        }

        /** @var array{initialGameState: GameState, events: GameEvent[]} $data */
        $data = require $filePath;
        $gameState = $data['initialGameState'];

        $user = $this->userRepository->find($gameState->player1->player->id);
        $opponent = $this->userRepository->find($gameState->player2->player->id);

        $roomEntity = null;
        if ($room) {
            $roomEntity = new Room($user);
            $roomEntity->setStatus(RoomStatusEnum::PLAYING);
            $roomEntity->setOpponent($opponent);
            $roomEntity->setOwnerDeck($this->deckRepository->findOneBy(['user' => $user]));
            $roomEntity->setOpponentDeck($this->deckRepository->findOneBy(['user' => $opponent]));

            $this->roomRepository->save($roomEntity);
        }

        $id = null !== $roomEntity ? (string) $roomEntity->getId() : $this->formatFileName($filePath);
        $this->gameStateRepository->save($gameState, $id);

        foreach ($data['events'] as $gameEvent) {
            $this->gameEventRepository->save($gameEvent, $id);
        }

        $output->writeln(sprintf('<info>File "%s" imported successfully.</info>', $filePath));
        $output->writeln($id);

        return 0;
    }

    private function formatFileName(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }
}
