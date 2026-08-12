<?php

declare(strict_types=1);

namespace App\Domain\Command\Game\Debug;

use App\Service\Auth\CurrentUserProviderInterface;
use App\Service\Debug\DebugGameAction;
use App\Service\Debug\DebugGameActionConverter;
use App\Service\Game\EndGameHandlerInterface;
use App\Service\Game\GameEventResolver;
use App\Service\Game\State\GameEventRepositoryInterface;
use App\Service\Game\State\GameStateProvider;
use App\Service\Game\State\GameStateRepositoryInterface;
use App\Service\GameEventPublisher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DebugGameActionHandler
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private GameStateProvider $gameStateProvider,
        private DebugGameActionConverter $converter,
        private GameEventResolver $gameEventResolver,
        private GameEventRepositoryInterface $gameEventRepository,
        private GameStateRepositoryInterface $gameStateRepository,
        private EndGameHandlerInterface $endGameHandler,
        private GameEventPublisher $gameEventPublisher,
    ) {}

    public function __invoke(DebugGameActionCommand $command): void
    {
        $user = $this->currentUserProvider->getCurrentUser();

        if (!\in_array($command->actionId, DebugGameAction::ACTIONS, true)) {
            throw HttpException::fromStatusCode(Response::HTTP_BAD_REQUEST, 'Invalid action id');
        }

        $room = $command->getCurrentResource();
        $gameId = (string) $room->getId();

        if (!($state = $this->gameStateProvider->get($gameId))) {
            throw new NotFoundHttpException();
        }

        $action = new DebugGameAction((string) $user->getId(), $command->actionId, $gameId, $command->payload);

        $resolvedEvents = [];

        foreach ($this->converter->convert($action, $state) as $event) {
            $resolution = $this->gameEventResolver->resolve($event, $state);
            $state = $resolution->state;
            $resolvedEvents = [...$resolvedEvents, ...$resolution->events];
        }

        $lastId = null;

        foreach ($resolvedEvents as $event) {
            if (!$event->shouldBePersisted()) {
                continue;
            }

            $event = $this->gameEventRepository->save($event, $gameId);
            $lastId = $event->id ? $event->id : $lastId;
        }

        if ($lastId) {
            $state = $state->withLastEventId($lastId);
        }

        $this->gameStateRepository->save($state, $gameId);

        if ($state->player1->healthPoints <= 0 || $state->player2->healthPoints <= 0) {
            $this->endGameHandler->endGame($gameId, $state->player1->healthPoints <= 0 ? $state->player2->player->id : $state->player1->player->id);
        }

        $this->gameEventPublisher->publish($resolvedEvents, $state, $gameId);
    }
}
