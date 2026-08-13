<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CardSetEnum;
use App\Service\Auth\CurrentUserProviderInterface;
use App\Service\Game\CardRegistryInterface;

/**
 * @implements ProviderInterface<object>
 */
final class InventorySetStatsProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private CardRegistryInterface $cardRegistry,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $inventory = $this->currentUserProvider->getCurrentUser()->getInventory();

        $ownedCardsBySet = [];
        $seenCardsBySet = [];
        foreach (CardSetEnum::cases() as $cardSet) {
            $ownedCardsBySet[$cardSet->name] = 0;
            $seenCardsBySet[$cardSet->name] = [];
        }

        foreach ($inventory->getCards() as $inventoryCard) {
            try {
                $template = $this->cardRegistry->getCardTemplateById($inventoryCard->getCard());
            } catch (\Throwable) {
                continue;
            }

            $setName = $template->getSerie()->name;
            $cardId = $inventoryCard->getCard();
            if (isset($seenCardsBySet[$setName][$cardId])) {
                continue;
            }

            $seenCardsBySet[$setName][$cardId] = true;
            ++$ownedCardsBySet[$setName];
        }

        $sets = [];
        foreach (CardSetEnum::cases() as $cardSet) {
            $sets[] = [
                'set' => $cardSet->name,
                'ownedCards' => $ownedCardsBySet[$cardSet->name],
                'totalCards' => count($this->cardRegistry->getAllBy(['serie' => $cardSet])),
            ];
        }

        return $sets;
    }
}
