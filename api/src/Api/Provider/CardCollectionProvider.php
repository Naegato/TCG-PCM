<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\DTO\CardCollectionDTO;
use App\Api\DTO\CollectionCardDTO;
use App\Enum\CardRarityEnum;
use App\Game\AbstractCard;
use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Service\Auth\CurrentUserProviderInterface;
use App\Service\Game\CardRegistryInterface;

/**
 * @implements ProviderInterface<CardCollectionDTO>
 */
final class CardCollectionProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
        private CardRegistryInterface $cardRegistry,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CardCollectionDTO
    {
        $inventory = $this->currentUserProvider->getCurrentUser()->getInventory();

        $quantityByCardId = [];
        foreach ($inventory->getCards() as $inventoryCard) {
            $quantityByCardId[$inventoryCard->getCard()] = $inventoryCard->getQuantity();
        }

        $cardIds = $this->cardRegistry->getAllBy([]);
        sort($cardIds);

        // Some registry entries alias the same underlying card class (same AbstractCard::getId()),
        // so entries are keyed and merged by the card's own id to avoid listing it twice.
        $quantityByResolvedId = [];
        $cardByResolvedId = [];
        foreach ($cardIds as $cardId) {
            $card = $this->cardRegistry->getCardTemplateById($cardId);
            $resolvedId = $card->getId();

            $cardByResolvedId[$resolvedId] ??= $card;
            $quantityByResolvedId[$resolvedId] = ($quantityByResolvedId[$resolvedId] ?? 0) + ($quantityByCardId[$cardId] ?? 0);
        }

        $entries = array_map(fn(string $resolvedId): array => [
            'card' => $this->buildCollectionCardDTO($cardByResolvedId[$resolvedId]),
            'quantity' => $quantityByResolvedId[$resolvedId],
        ], array_keys($cardByResolvedId));

        return new CardCollectionDTO($this->sortCards($entries));
    }

    private function buildCollectionCardDTO(AbstractCard $card): CollectionCardDTO
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
            instanceId: $card->getId(),
            type: $card->getType(),
            cost: $cost,
            hp: $hp,
            attack: $attack,
        );
    }

    /**
     * First sort by set, then by type, then by rarity and then by name
     *
     * @param array{card: CollectionCardDTO}[] $cards
     *
     * @return array{card: CollectionCardDTO}[]
     */
    private function sortCards(array $cards): array
    {
        $cardsPerSet = [];

        foreach ($cards as $card) {
            $cardsPerSet[$card['card']->set->value][] = $card;
        }

        foreach ($cardsPerSet as &$cardsInSet) {
            usort($cardsInSet, function (array $a, array $b): int {
                $typeOrderA = $this->getTypeOrder($this->cardRegistry->getCardTemplateById($a['card']->instanceId));
                $typeOrderB = $this->getTypeOrder($this->cardRegistry->getCardTemplateById($b['card']->instanceId));

                if ($typeOrderA !== $typeOrderB) {
                    return $typeOrderA <=> $typeOrderB;
                }

                $rarityOrderA = $this->getRarityOrder($a['card']->rarity);
                $rarityOrderB = $this->getRarityOrder($b['card']->rarity);

                if ($rarityOrderA !== $rarityOrderB) {
                    return $rarityOrderA <=> $rarityOrderB;
                }

                return strcasecmp($a['card']->name, $b['card']->name);
            });
        }

        ksort($cardsPerSet);

        return array_merge(...array_values($cardsPerSet));
    }

    private function getTypeOrder(AbstractCard $card): int
    {
        return match (true) {
            $card instanceof AbstractCharacterCard => 0,
            $card instanceof AbstractMonsterCard => 1,
            $card instanceof AbstractPassiveCard => 2,
            $card instanceof AbstractConsumableCard => 3,
            default => 4,
        };
    }

    private function getRarityOrder(CardRarityEnum $rarity): int
    {
        return match ($rarity) {
            CardRarityEnum::COMMON => 0,
            CardRarityEnum::UNCOMMON => 1,
            CardRarityEnum::RARE => 2,
            CardRarityEnum::EPIC => 3,
            CardRarityEnum::LEGENDARY => 4,
        };
    }
}
