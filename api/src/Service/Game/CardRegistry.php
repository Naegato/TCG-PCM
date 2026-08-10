<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\AbstractCard;

class CardRegistry implements CardRegistryInterface
{
    /**
     * @var array<string, class-string<AbstractCard>>
     */
    private array $cards = [];

    /**
     * @var array<string, AbstractCard>
     */
    private array $instanciedCards = [];

    public function __construct(
        private string $cardsListPath,
    ) {}

    public function getCardTemplateById(string $cardId): AbstractCard
    {
        return clone $this->get($cardId);
    }

    public function getAllBy(array $criteria): array
    {
        $rarity = $criteria['rarity'] ?? null;
        $serie = $criteria['serie'] ?? null;
        $type = $criteria['type'] ?? null;
        $excludeType = $criteria['excludeType'] ?? null;
        $groups = $criteria['groups'] ?? null;

        if (null !== $rarity && !$rarity instanceof CardRarityEnum) {
            throw new \InvalidArgumentException(sprintf('Rarity must be an instance of %s', CardRarityEnum::class));
        }

        if (null !== $serie && !$serie instanceof CardSetEnum) {
            throw new \InvalidArgumentException(sprintf('Set must be an instance of %s', CardSetEnum::class));
        }

        if (\is_string($type) && !is_subclass_of($type, AbstractCard::class, true)) {
            throw new \InvalidArgumentException(\sprintf('Type must be a class string of %s', AbstractCard::class));
        }

        if (\is_string($excludeType) && !is_subclass_of($excludeType, AbstractCard::class, true)) {
            throw new \InvalidArgumentException(\sprintf('Exclude type must be a class string of %s', AbstractCard::class));
        }

        $this->loadCards();

        $cards = [];
        foreach ($this->cards as $cardId => $cardClass) {
            if ($rarity && $cardClass::$rarity !== $rarity) {
                continue;
            }

            if ($serie && $cardClass::$serie !== $serie) {
                continue;
            }

            if (\is_string($type) && !is_a($cardClass, $type, true)) {
                continue;
            }

            if (\is_string($excludeType) && is_a($cardClass, $excludeType, true)) {
                continue;
            }

            if (\is_array($groups) && [] !== $groups && [] === array_intersect($groups, $cardClass::getGroups())) {
                continue;
            }

            $cards[] = $cardId;
        }

        return $cards;
    }

    public function has(string $cardId): bool
    {
        $this->loadCards();

        return array_key_exists($cardId, $this->cards);
    }

    /**
     * @return array<string, class-string<AbstractCard>>
     */
    protected function getCardsList(): array
    {
        try {
            return require $this->cardsListPath;
        } catch (\Throwable) {
            throw new \RuntimeException(sprintf('Unable to load cards list from path "%s"', $this->cardsListPath));
        }
    }

    private function get(string $cardId): AbstractCard
    {
        return $this->instanciedCards[$cardId] ??= $this->createCardInstance($cardId);
    }

    private function createCardInstance(string $cardId): AbstractCard
    {
        if (!($this->cards[$cardId] ?? null)) {
            $this->loadCards();
        }

        if (!($class = $this->cards[$cardId] ?? null)) {
            throw new \RuntimeException(\sprintf('Card with id "%s" not found', $cardId));
        }

        return new $class();
    }

    private function loadCards(): void
    {
        if ([] !== $this->cards) {
            return;
        }

        $this->cards = $this->getCardsList();
    }
}
