<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Game\Card\Trait\CardAwareTrait;
use App\Game\GameContext;

final class PierreSaidNoMonsterZone extends AbstractConsumableCard
{
    use CardAwareTrait;

    public static CardRarityEnum $rarity = CardRarityEnum::LEGENDARY;

    public function getId(): string
    {
        return 'PierreSaidNoMonsterZone';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $monsters = $context->getMonsters();

        foreach ($monsters as $monster) {
            $context->discardCard($monster);
        }
    }
}
