<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\CardTargetTypeEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class DartCard extends AbstractConsumableCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::COMMON;
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const DAMAGE = 7;

    public function getId(): string
    {
        return 'Dart';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
        ]);
    }

    public function requiresTarget(): bool
    {
        return true;
    }

    public function getTargetType(): ?CardTargetTypeEnum
    {
        return CardTargetTypeEnum::MONSTER;
    }

    public function play(GameContext $context, array $data = []): void
    {
        $target = $data['target'] ?? null;

        if (!\is_string($target)) {
            throw new \InvalidArgumentException('Missing target key');
        }

        $context->damageCard($target, $this->getValue(self::DAMAGE, true));
    }
}
