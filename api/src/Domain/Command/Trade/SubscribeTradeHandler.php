<?php

declare(strict_types=1);

namespace App\Domain\Command\Trade;

use App\Service\Auth\CurrentUserProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Mints a Mercure subscriber token for the `trade/{id}` topic. Unlike Room (where both parties
 * get a token through create/join), a Trade has no "join" step for the recipient, so each
 * participant calls this once when opening the trade session page.
 */
#[AsMessageHandler]
final class SubscribeTradeHandler
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private HubInterface $hub,
    ) {}

    public function __invoke(SubscribeTradeCommand $command): array
    {
        $trade = $command->getCurrentResource();
        $user = $this->currentUserProvider->getCurrentUser();

        if (!$trade->involves($user)) {
            throw HttpException::fromStatusCode(Response::HTTP_FORBIDDEN, 'You are not part of this trade.');
        }

        $topic = \sprintf('trade/%s', $trade->getId());
        $token = $this->hub->getFactory()?->create([new Grant([Grant::ACTION_SUBSCRIBE], [$topic])], []);
        $url = \sprintf('%s?topic=%s', $this->hub->getPublicUrl(), $topic);

        return [
            'mercure_url' => $url,
            'mercure_token' => $token,
        ];
    }
}
