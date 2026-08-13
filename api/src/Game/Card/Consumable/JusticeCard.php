<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;

final class JusticeCard extends AbstractConsumableCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::RARE;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    public function getId(): string
    {
        return 'Justice';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $currentCount = count($context->getCurrentPlayerState()->hand);
        $otherCount = count($context->getOpponentState()->hand);

        $count = max($otherCount - $currentCount, 0);

        $context->drawCards($count);
    }
}
