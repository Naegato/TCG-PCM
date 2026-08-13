<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Exception\InvalidDeckException;
use App\Entity\Deck;
use App\Enum\CardRarityEnum;
use App\Enum\CardTypeEnum;
use App\Service\Game\CardRegistryInterface;

final class DeckValidator
{
    public const DECK_SIZE = 50;
    public const MAX_CARD_COPIES = 5;

    private const array RARITY_LIMITS = [
        CardRarityEnum::UNCOMMON->value => 15,
        CardRarityEnum::RARE->value => 7,
        CardRarityEnum::EPIC->value => 5,
        CardRarityEnum::LEGENDARY->value => 3,
    ];

    public function getDeckSize(): int
    {
        return self::DECK_SIZE;
    }

    public function getMaxCardCopies(): int
    {
        return self::MAX_CARD_COPIES;
    }

    /**
     * @return array<string, int>
     */
    public function getRarityLimitsForApi(): array
    {
        return [
            CardRarityEnum::COMMON->name => self::DECK_SIZE,
            CardRarityEnum::UNCOMMON->name => self::RARITY_LIMITS[CardRarityEnum::UNCOMMON->value],
            CardRarityEnum::RARE->name => self::RARITY_LIMITS[CardRarityEnum::RARE->value],
            CardRarityEnum::EPIC->name => self::RARITY_LIMITS[CardRarityEnum::EPIC->value],
            CardRarityEnum::LEGENDARY->name => self::RARITY_LIMITS[CardRarityEnum::LEGENDARY->value],
        ];
    }

    public function __construct(
        private CardRegistryInterface $cardRegistry,
    ) {}

    public function validateDeck(Deck $deck): bool
    {
        $inventory = $deck->getUser()->getInventory();
        $allCards = $deck->getCards();
        $cardsMap = [];
        $rarityCount = [
            CardRarityEnum::COMMON->value => 0,
            CardRarityEnum::UNCOMMON->value => 0,
            CardRarityEnum::RARE->value => 0,
            CardRarityEnum::EPIC->value => 0,
            CardRarityEnum::LEGENDARY->value => 0,
        ];

        if (50 !== count($allCards)) {
            throw new InvalidDeckException(\sprintf('Deck should have %d cards (currently %d)', self::DECK_SIZE, count($allCards)));
        }

        if (!$this->cardRegistry->has($deck->getCharacterCard())) {
            throw new InvalidDeckException(\sprintf('Character card "%s" does not exist', $deck->getCharacterCard()));
        }

        $characterTemplate = $this->cardRegistry->getCardTemplateById($deck->getCharacterCard());
        if (CardTypeEnum::CHARACTER !== $characterTemplate->getType()) {
            throw new InvalidDeckException(\sprintf('Card "%s" is not a valid character card', $deck->getCharacterCard()));
        }

        foreach ($allCards as $card) {
            if (!$this->cardRegistry->has($card)) {
                throw new InvalidDeckException(\sprintf('Card "%s" does not exist', $card));
            }
            $template = $this->cardRegistry->getCardTemplateById($card);

            if (CardTypeEnum::CHARACTER === $template->getType()) {
                throw new InvalidDeckException(\sprintf('Card "%s" cannot be added to deck cards', $card));
            }

            if (!($cardsMap[$card] ?? null)) {
                $cardsMap[$card] = 0;
            }

            ++$cardsMap[$card];

            if ($cardsMap[$card] > self::MAX_CARD_COPIES) {
                throw new InvalidDeckException(\sprintf(
                    'Deck cannot have more than %d copies of card "%s" (currently %d)',
                    self::MAX_CARD_COPIES,
                    $card,
                    $cardsMap[$card],
                ));
            }

            $rarity = $template->getRarity()->value;
            $rarityCount[$rarity]++;
        }

        foreach (self::RARITY_LIMITS as $rarity => $limit) {
            if ($rarityCount[$rarity] > $limit) {
                throw new InvalidDeckException(\sprintf('Deck cannot have more than %d %s cards (currently %d)', $limit, $rarity, $rarityCount[$rarity]));
            }
        }

        foreach ($cardsMap as $cardId => $count) {
            if (!($cardInventory = $inventory->findCardByCardId($cardId))) {
                throw new InvalidDeckException(\sprintf('User does not have card "%s"', $cardId));
            }

            if ($cardInventory->getQuantity() < $count) {
                throw new InvalidDeckException(\sprintf(
                    'User does not have enough copies of card "%s" (has %d, needs %d)',
                    $cardId,
                    $cardInventory->getQuantity(),
                    $count,
                ));
            }
        }

        return true;
    }
}
