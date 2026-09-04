<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardEffectEnum;
use App\Enum\CardRarityEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class PhilippeCard extends AbstractConsumableCard
{
    public function getId(): string
    {
        return 'Philippe';
    }

    public function getImage(): string
    {
        return 'philippe.webp';
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'effect' => CardEffectEnum::TORNED,
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $opponent = $context->getOpponentState();

        foreach ($opponent->playArea->getAll() as $cardId) {
            $context->addEffect(CardEffectEnum::TORNED, $cardId);
        }
    }
}
