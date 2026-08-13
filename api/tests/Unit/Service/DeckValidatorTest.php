<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Exception\InvalidDeckException;
use App\Entity\Deck;
use App\Entity\Inventory\CardInventory;
use App\Entity\Inventory\Inventory;
use App\Entity\User;
use App\Enum\CardRarityEnum;
use App\Service\DeckValidator;
use App\Service\Game\CardRegistryInterface;
use App\Tests\Unit\Fixtures\DummyCard;
use PHPUnit\Framework\TestCase;

final class DeckValidatorTest extends TestCase
{
    private CardRegistryInterface $cardRegistry;
    private DeckValidator $deckValidator;

    protected function setUp(): void
    {
        $this->cardRegistry = $this->createStub(CardRegistryInterface::class);
        $this->deckValidator = new DeckValidator($this->cardRegistry);
    }

    public function testValidateDeckWithValidDeck(): void
    {
        $cards = $this->createValidCardsList();

        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->setupCardRegistry($cards, 'character-card');

        $this->setupUserInventoryWithCards($deck->getUser(), $cards);

        $result = $this->deckValidator->validateDeck($deck);

        self::assertTrue($result);
    }

    public function testValidateDeckWithInvalidSize(): void
    {
        $this->expectException(InvalidDeckException::class);
        $this->expectExceptionMessage('Deck should have 50 cards (currently 30)');

        $cards = array_fill(0, 30, 'card-1');
        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->setupCardRegistry(['card-1', 'character-card'], 'character-card');

        $this->deckValidator->validateDeck($deck);
    }

    public function testValidateDeckWithMissingCharacterCard(): void
    {
        $this->expectException(InvalidDeckException::class);
        $this->expectExceptionMessage('Character card "nonexistent-character" does not exist');

        $cards = array_fill(0, 50, 'card-1');
        $deck = $this->createDeckWithCards('nonexistent-character', $cards);

        $this->cardRegistry->method('has')->willReturnCallback(static fn($card) => $card !== 'nonexistent-character');

        $this->deckValidator->validateDeck($deck);
    }

    public function testValidateDeckWithNonexistentCard(): void
    {
        $this->expectException(InvalidDeckException::class);
        $this->expectExceptionMessage('Card "nonexistent-card" does not exist');

        $cards = array_merge(['nonexistent-card'], $this->generateUniqueCardIds(49, 'existing-card'));
        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->cardRegistry->method('getCardTemplateById')->willReturnCallback(static fn(string $cardId) => 'character-card' === $cardId
            ? new CharacterDummyCard()
            : new DummyCard());

        $this->cardRegistry
            ->method('has')
            ->willReturnCallback(static fn($card) => $card !== 'nonexistent-card' && $card !== 'character-card' || $card === 'character-card');

        $this->deckValidator->validateDeck($deck);
    }

    public function testValidateDeckExceedsRarityLimit(): void
    {
        $this->expectException(InvalidDeckException::class);
        $this->expectExceptionMessage('Deck cannot have more than 3 legendary cards');

        // Create 50 cards with 4 legendary cards (exceeds limit of 3)
        $cards = array_merge(
            array_fill(0, 5, 'common-card-1'),
            array_fill(0, 5, 'common-card-2'),
            array_fill(0, 5, 'common-card-3'),
            array_fill(0, 5, 'common-card-4'),
            array_fill(0, 5, 'common-card-5'),
            array_fill(0, 5, 'common-card-6'),
            array_fill(0, 5, 'common-card-7'),
            array_fill(0, 5, 'common-card-8'),
            array_fill(0, 5, 'common-card-9'),
            ['common-card-10'],
            ['legendary-card-1', 'legendary-card-2', 'legendary-card-3', 'legendary-card-4'],
        );

        $deck = $this->createDeckWithCards('character-card', $cards);

        // Setup card registry with different rarities
        $this->setupCardRegistryWithRarities([
            'common-card-1' => CardRarityEnum::COMMON,
            'common-card-2' => CardRarityEnum::COMMON,
            'common-card-3' => CardRarityEnum::COMMON,
            'common-card-4' => CardRarityEnum::COMMON,
            'common-card-5' => CardRarityEnum::COMMON,
            'common-card-6' => CardRarityEnum::COMMON,
            'common-card-7' => CardRarityEnum::COMMON,
            'common-card-8' => CardRarityEnum::COMMON,
            'common-card-9' => CardRarityEnum::COMMON,
            'common-card-10' => CardRarityEnum::COMMON,
            'character-card' => CardRarityEnum::COMMON,
            'legendary-card-1' => CardRarityEnum::LEGENDARY,
            'legendary-card-2' => CardRarityEnum::LEGENDARY,
            'legendary-card-3' => CardRarityEnum::LEGENDARY,
            'legendary-card-4' => CardRarityEnum::LEGENDARY,
        ]);

        $this->setupUserInventoryWithCards($deck->getUser(), $cards);

        $this->deckValidator->validateDeck($deck);
    }

    public function testValidateDeckWithExactlyTwoCopiesIsValid(): void
    {
        $cards = array_merge(['card-1', 'card-1'], $this->generateUniqueCardIds(48, 'other-card'));

        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->setupCardRegistry($cards, 'character-card');

        $this->setupUserInventoryWithCards($deck->getUser(), $cards);

        $result = $this->deckValidator->validateDeck($deck);

        self::assertTrue($result);
    }

    public function testValidateDeckWithInsufficientInventory(): void
    {
        $this->expectException(InvalidDeckException::class);

        $cards = array_merge(['card-1', 'card-1'], $this->generateUniqueCardIds(48, 'other-card'));

        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->setupCardRegistry($cards, 'character-card');

        $user = $deck->getUser();
        $inventory = $user->getInventory();

        foreach ($inventory->getCards() as $c) {
            $inventory->removeCard($c);
        }

        $cardInventory = new CardInventory($inventory, 'card-1');
        $cardInventory->setQuantity(1);
        $inventory->addCard($cardInventory);

        foreach (array_unique(['other-card']) as $cardId) {
            $cardInv = new CardInventory($inventory, $cardId);
            $cardInv->setQuantity(10);
            $inventory->addCard($cardInv);
        }

        $this->deckValidator->validateDeck($deck);
    }

    public function testValidateDeckWithComplexRarityDistribution(): void
    {
        // Test witha valid complex distribution:
        // - 35 common cards
        // - 7 uncommon cards (at max limit)
        // - 4 rare cards (below max limit of 7)
        // - 3 epic cards (below max limit of 5)
        // - 1 legendary card (below max limit of 3)

        $cards = array_merge(
            array_fill(0, 5, 'common-1'),
            array_fill(0, 5, 'common-2'),
            array_fill(0, 5, 'common-3'),
            array_fill(0, 5, 'common-4'),
            array_fill(0, 5, 'common-5'),
            array_fill(0, 5, 'common-6'),
            array_fill(0, 5, 'common-7'),
            array_fill(0, 5, 'uncommon-1'),
            array_fill(0, 2, 'uncommon-2'),
            array_fill(0, 4, 'rare-1'),
            array_fill(0, 3, 'epic-1'),
            ['legendary-1'],
        );

        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->setupCardRegistryWithRarities([
            'common-1' => CardRarityEnum::COMMON,
            'common-2' => CardRarityEnum::COMMON,
            'common-3' => CardRarityEnum::COMMON,
            'common-4' => CardRarityEnum::COMMON,
            'common-5' => CardRarityEnum::COMMON,
            'common-6' => CardRarityEnum::COMMON,
            'common-7' => CardRarityEnum::COMMON,
            'uncommon-1' => CardRarityEnum::UNCOMMON,
            'uncommon-2' => CardRarityEnum::UNCOMMON,
            'rare-1' => CardRarityEnum::RARE,
            'epic-1' => CardRarityEnum::EPIC,
            'legendary-1' => CardRarityEnum::LEGENDARY,
            'character-card' => CardRarityEnum::COMMON,
        ]);

        $this->setupUserInventoryWithCards($deck->getUser(), $cards);

        $result = $this->deckValidator->validateDeck($deck);

        self::assertTrue($result);
    }

    public function testValidateDeckExceedsMaxCopiesPerCard(): void
    {
        $this->expectException(InvalidDeckException::class);
        $this->expectExceptionMessage('Deck cannot have more than 5 copies of card "card-1"');

        $cards = array_merge(array_fill(0, 6, 'card-1'), $this->generateUniqueCardIds(44, 'other-card'));

        $deck = $this->createDeckWithCards('character-card', $cards);

        $this->setupCardRegistry($cards, 'character-card');
        $this->setupUserInventoryWithCards($deck->getUser(), $cards);

        $this->deckValidator->validateDeck($deck);
    }

    private function createValidCardsList(): array
    {
        $cards = [];

        for ($i = 1; $i <= 10; ++$i) {
            $cards = array_merge($cards, array_fill(0, 5, \sprintf('common-card-%d', $i)));
        }

        return $cards;
    }

    /**
     * @return string[]
     */
    private function generateUniqueCardIds(int $count, string $prefix): array
    {
        $ids = [];

        for ($i = 1; $i <= $count; ++$i) {
            $ids[] = \sprintf('%s-%d', $prefix, $i);
        }

        return $ids;
    }

    private function createDeckWithCards(string $characterCard, array $cards): Deck
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(spl_object_id($user));
        $this->setupUserInventoryWithCards($user, $cards);

        return new Deck($user, 'Test Deck', $characterCard, $cards);
    }

    private function setupCardRegistry(array $cards, string $characterCard): void
    {
        $allCards = array_unique(array_merge($cards, [$characterCard]));

        $this->cardRegistry->method('has')->willReturnCallback(static fn($card) => \in_array($card, $allCards, true));

        $this->cardRegistry->method('getCardTemplateById')->willReturnCallback(static fn(string $cardId) => $cardId === $characterCard
            ? new CharacterDummyCard()
            : new DummyCard());
    }

    private function setupCardRegistryWithRarities(array $cardRarityMap): void
    {
        $this->cardRegistry->method('has')->willReturnCallback(static fn($card) => null !== $cardRarityMap[$card] ?? null);

        $this->cardRegistry
            ->method('getCardTemplateById')
            ->willReturnCallback(static function ($cardId) use ($cardRarityMap) {
                if ('character-card' === $cardId) {
                    return new CharacterDummyCard();
                }

                $rarity = $cardRarityMap[$cardId] ?? CardRarityEnum::COMMON;

                return CardRarityEnum::LEGENDARY === $rarity ? new LegendaryCard() : new DummyCard();
            });
    }

    private function setupUserInventoryWithCards(User $user, array $cards): void
    {
        $inventory = new Inventory($user);
        $cardCounts = array_count_values($cards);

        foreach ($cardCounts as $cardId => $count) {
            $cardInventory = new CardInventory($inventory, $cardId);
            $cardInventory->setQuantity($count + 1);
            $inventory->addCard($cardInventory);
        }

        $user->method('getInventory')->willReturn($inventory);
    }
}

class LegendaryCard extends DummyCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::LEGENDARY;
    }
}

class CharacterDummyCard extends \App\Game\Card\Character\AbstractCharacterCard
{
    public function getId(): string
    {
        return 'character-card';
    }

    public function getHealthPoints(): int
    {
        return 20;
    }
}
