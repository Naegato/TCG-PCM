<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardEffectEnum;
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

    public function getTargetType(): int
    {
        return self::TARGET_TYPE_MONSTER | self::TARGET_SELF_CARDS;
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
