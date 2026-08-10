<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardEffectEnum;
use App\Enum\CardTargetTypeEnum;
use App\Game\GameContext;

final class ViciousStingerCard extends AbstractConsumableCard
{
    public function getId(): string
    {
        return 'ViciousStinger';
    }

    public function getImage(): string
    {
        return 'vicious_stinger.webp';
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

        $context->addEffect(CardEffectEnum::POWER_BOOST, $target, [
            'value' => 1.5,
        ]);
    }
}
