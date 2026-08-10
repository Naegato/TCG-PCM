<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;

final class WololoCard extends AbstractConsumableCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::LEGENDARY;
    public static CardSetEnum $serie = CardSetEnum::ORIGINAL;

    public function getId(): string
    {
        return 'Wololo';
    }

    public function requiresTarget(): bool
    {
        return true;
    }

    public function play(GameContext $context, array $data = []): void
    {
        $targetId = $data['target'] ?? null;

        if (!\is_string($targetId)) {
            throw new \InvalidArgumentException('Missing target key');
        }

        $opponentCharacterId = $context->getOpponentState()->characterCardId;

        if ($targetId === $opponentCharacterId) {
            throw new \InvalidArgumentException('Cannot target opponent character card');
        }

        $context->stealCard($targetId, $context->getOtherPlayerId($this->ownerId), $this->ownerId);
    }
}
