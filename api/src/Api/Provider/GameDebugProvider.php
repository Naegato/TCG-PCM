<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Game\State\GameState;
use App\Service\Game\GameStateConverter;
use App\Service\Game\State\GameStateProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;

/**
 * @implements ProviderInterface<GameState>
 */
final class GameDebugProvider implements ProviderInterface
{
    public function __construct(
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

        $topic = \sprintf('game/%s', $id);
        $privateTopic1 = $topic.'-1';
        $privateTopic2 = $topic.'-2';
        $token = $this->hub->getFactory()?->create([new Grant([Grant::ACTION_SUBSCRIBE], [$topic, $privateTopic1, $privateTopic2])], []);
        $url = \sprintf('%s?topic=%s&topic=%s&topic=%s', $this->hub->getPublicUrl(), $topic, $privateTopic1, $privateTopic2);

        return [
            'state' => $this->gameStateConverter->convertGameStateForAdmin($gameState),
            'mercure_url' => $url,
            'mercure_token' => $token,
        ];
    }
}
