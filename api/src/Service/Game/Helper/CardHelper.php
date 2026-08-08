<?php

declare(strict_types=1);

namespace App\Service\Game\Helper;

use App\Game\AbstractCard;
use App\Service\Game\CardFactoryInterface;
use App\Service\Game\CardIdGeneratorInterface;
use App\Service\Game\CardRegistryInterface;

final class CardHelper
{
    public function __construct(
        private CardRegistryInterface $cardRegistry,
        private CardIdGeneratorInterface $cardIdGenerator,
        private CardFactoryInterface $cardFactory,
    ) {}

    public function getCardTemplate(string $templateId): AbstractCard
    {
        return $this->cardRegistry->getCardTemplateById($templateId);
    }

    public function generateCardId(string $templateId): string
    {
        return $this->cardIdGenerator->generateCardId($templateId);
    }

    public function createCardInstance(string $templateId): AbstractCard
    {
        return $this->cardFactory->create($templateId);
    }

    /**
     * @param array<string, mixed> $criterias
     *
     * @return string[]
     */
    public function getCardsBy(array $criterias): array
    {
        return $this->cardRegistry->getAllBy($criterias);
    }

    public function getCardsInGroup(string $group): array
    {
        return $this->cardRegistry->getAllBy(['groups' => [$group]]);
    }
}
