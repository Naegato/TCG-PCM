<?php

declare(strict_types=1);

namespace App\Domain\Command\Booster;

use App\Api\DTO\CollectionCardDTO;
use App\Domain\Exception\NotEnoughTokenException;
use App\Domain\Model\Booster;
use App\Entity\Inventory\Inventory;
use App\Event\Badge\BoosterOpenedEvent;
use App\Game\AbstractCard;
use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Service\Auth\CurrentUserProviderInterface;
use App\Service\Booster\BoosterGenerator;
use App\Service\InventoryUpdater;
use App\Service\User\UserGenerateBoosterTokens;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class OpenBoosterHandler
{
    public function __construct(
        private BoosterGenerator $boosterGenerator,
        private EventDispatcherInterface $eventDispatcher,
        private InventoryUpdater $inventoryUpdater,
        private CurrentUserProviderInterface $currentUserProvider,
        private UserGenerateBoosterTokens $userGenerateBoosterTokens,
    ) {}

    public function __invoke(OpenBoosterCommand $command): Booster
    {
        $user = $this->currentUserProvider->getCurrentUser();
        $ownedCardIds = $this->getOwnedCardIds($user->getInventory());
        // on regen les tokens ici pour éviter qu'un chenapan contourne la limite max de tokens
        // ex un utilisateur à le nombre max de tokens et n'en a pas recup depuis des jours -> il pourrait direct taper l'endpoint open booster
        // puis une fois qu'il est à 0 tokens utiliser generateBoosterToken pour en regénérer depuis lastBoosterTokensAt
        $this->userGenerateBoosterTokens->generate($user);
        $wallet = $user->getUserWallet();

        if ($wallet->getBoosterTokens() <= 0) {
            throw new NotEnoughTokenException('Not enough booster tokens to open a booster.');
        }
        $wallet->removeBoosterToken(1);

        $this->eventDispatcher->dispatch(new BoosterOpenedEvent($user));

        $booster = $this->boosterGenerator->generateBooster($command->type);

        /** @var AbstractCard[] $cards */
        $cards = $booster->getCards();

        $this->inventoryUpdater->addCards($cards);

        return new Booster(array_map(fn(AbstractCard $card): CollectionCardDTO => $this->createOpenedCardDTO($card, $ownedCardIds), $cards));
    }

    /**
     * @return array<string, true>
     */
    private function getOwnedCardIds(Inventory $inventory): array
    {
        $ownedCardIds = [];
        foreach ($inventory->getCards() as $cardInventory) {
            $ownedCardIds[$cardInventory->getCard()] = true;
        }

        return $ownedCardIds;
    }

    /**
     * @param array<string, true> $ownedCardIds
     */
    private function createOpenedCardDTO(AbstractCard $card, array $ownedCardIds): CollectionCardDTO
    {
        $cost = null;
        $hp = null;
        $attack = null;

        if (!$card instanceof AbstractCharacterCard) {
            $cost = $card->getCost();
        }

        if ($card instanceof AbstractMonsterCard) {
            $hp = $card->getHealPoints();
            $attack = $card->getAttack();
        }

        return new CollectionCardDTO(
            name: $card->getName(),
            description: $card->getDescription(),
            image: $card->getImage(),
            rarity: $card->getRarity(),
            set: $card->getSerie(),
            instanceId: $card->getInstanceId() ?? $card->getId(),
            type: $card->getType(),
            cost: $cost,
            hp: $hp,
            attack: $attack,
            isNewToCollection: !isset($ownedCardIds[$card->getId()]),
        );
    }
}
