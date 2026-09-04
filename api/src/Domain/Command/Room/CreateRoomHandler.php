<?php

declare(strict_types=1);

namespace App\Domain\Command\Room;

use App\Entity\Room;
use App\Repository\DeckRepository;
use App\Repository\RoomRepository;
use App\Service\Auth\CurrentUserProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateRoomHandler
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private DeckRepository $deckRepository,
        private RoomRepository $roomRepository,
        private HubInterface $hub,
    ) {}

    public function __invoke(CreateRoomCommand $command): array
    {
        $user = $this->currentUserProvider->getCurrentUser();

        if (!($deck = $this->deckRepository->findFirstActiveByUser($user))) {
            throw HttpException::fromStatusCode(Response::HTTP_BAD_REQUEST, 'User has no deck to create a room.');
        }

        $room = new Room($user);
        $room->setOwnerDeck($deck);

        $this->roomRepository->save($room);

        $topic = \sprintf('game/%s', $room->getId());
        $token = $this->hub->getFactory()?->create([new Grant([Grant::ACTION_SUBSCRIBE], [$topic])], []);
        $url = \sprintf('%s?topic=%s', $this->hub->getPublicUrl(), $topic);

        return [
            'id' => $room->getId(),
            'mercure_url' => $url,
            'mercure_token' => $token,
        ];
    }
}
