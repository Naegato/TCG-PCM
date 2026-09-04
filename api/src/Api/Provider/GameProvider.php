<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Game\State\GameState;
use App\Service\Auth\CurrentUserProviderInterface;
use App\Service\Game\GameStateConverter;
use App\Service\Game\State\GameStateProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;

/**
 * @implements ProviderInterface<GameState>
 */
final class GameProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private GameStateConverter $gameStateConverter,
        private GameStateProvider $gameStateProvider,
        private HubInterface $hub,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $id = (string) $uriVariables['id'];
        if (!($gameState = $this->gameStateProvider->get($id))) {
            throw new NotFoundHttpException();
        }

        $user = $this->currentUserProvider->getCurrentUser();
        $topic = \sprintf('game/%s', $id);
        $privateTopic = $topic.'-'.($user->getId() == $gameState->player1->player->id ? '1' : '2'); // @mago-ignore lint:identity-comparison
        $token = $this->hub->getFactory()?->create([new Grant([Grant::ACTION_SUBSCRIBE], [$topic, $privateTopic])], []);
        $url = \sprintf('%s?topic=%s&topic=%s', $this->hub->getPublicUrl(), $topic, $privateTopic);

        return [
            'state' => $this->gameStateConverter->convertGameState($gameState, (string) $user->getId()),
            'mercure_url' => $url,
            'mercure_token' => $token,
        ];
    }
}
