<?php

declare(strict_types=1);

namespace App\Domain\Command\Trade;

use App\Entity\Trade;
use App\Repository\FriendshipRepository;
use App\Repository\TradeRepository;
use App\Repository\UserRepository;
use App\Service\Auth\CurrentUserProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateTradeHandler
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private UserRepository $userRepository,
        private FriendshipRepository $friendshipRepository,
        private TradeRepository $tradeRepository,
        private HubInterface $hub,
    ) {}

    public function __invoke(CreateTradeCommand $command): array
    {
        $user = $this->currentUserProvider->getCurrentUser();
        $friend = $this->userRepository->find($command->friendId);

        if (null === $friend) {
            throw HttpException::fromStatusCode(Response::HTTP_NOT_FOUND, 'User not found.');
        }

        if (!$this->friendshipRepository->areFriends($user, $friend)) {
            throw HttpException::fromStatusCode(Response::HTTP_FORBIDDEN, 'You can only trade with friends.');
        }

        if (null !== $this->tradeRepository->findActiveForUser($user)) {
            throw HttpException::fromStatusCode(Response::HTTP_BAD_REQUEST, 'You already have an active trade.');
        }

        if (null !== $this->tradeRepository->findActiveForUser($friend)) {
            throw HttpException::fromStatusCode(Response::HTTP_BAD_REQUEST, 'This friend already has an active trade.');
        }

        $trade = new Trade($user, $friend);
        $this->tradeRepository->save($trade);

        $this->hub->publish(
            new Update(
                \sprintf('trades/%s', $friend->getUsername()),
                json_encode([
                    'type' => 'trade_invite_received',
                    'data' => [
                        'id' => (string) $trade->getId(),
                        'initiator' => ['username' => $user->getUsername()],
                    ],
                ], JSON_THROW_ON_ERROR),
                true,
            ),
        );

        $topic = \sprintf('trade/%s', $trade->getId());
        $token = $this->hub->getFactory()?->create([new Grant([Grant::ACTION_SUBSCRIBE], [$topic])], []);
        $url = \sprintf('%s?topic=%s', $this->hub->getPublicUrl(), $topic);

        return [
            'id' => $trade->getId(),
            'mercure_url' => $url,
            'mercure_token' => $token,
        ];
    }
}
