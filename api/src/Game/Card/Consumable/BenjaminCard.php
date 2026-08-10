<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardEffectEnum;
use App\Enum\CardSetEnum;
use App\Enum\CardTargetTypeEnum;
use App\Game\Card\Effect\HackedCardEffect;
use App\Game\GameContext;
use App\Game\GameUtils;

final class BenjaminCard extends AbstractConsumableCard
{
    public static CardSetEnum $serie = CardSetEnum::BTD6;

    private const int CARD_COUNT = 1;

    public function getId(): string
    {
        return 'Benjamin';
    }

    public function getImage(): string
    {
        return 'https://static.wikia.nocookie.net/b__/images/a/af/BenjaminPortrait.png/revision/latest?cb=20190612025211&path-prefix=bloons';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'effect' => CardEffectEnum::HACKED,
            'value' => $this->getValue(self::CARD_COUNT, true),
        ]);
    }

    public function requiresTarget(): bool
    {
        return true;
    }

    public function getTargetType(): ?CardTargetTypeEnum
    {
        return CardTargetTypeEnum::MONSTER_AND_PASSIVE;
    }

    public function play(GameContext $context, array $data = []): void
    {
        $target = $data['target'] ?? null;

        if (!\is_string($target)) {
            throw new \InvalidArgumentException('Missing target card for Benjamin card effect');
        }

        for ($i = 0; $i < $this->getValue(self::CARD_COUNT, true); $i++) {
            $context->addEffect(CardEffectEnum::HACKED, $target, [
                'value' => $context->randomIntBetween(HackedCardEffect::MIN_MODIFIER, HackedCardEffect::MAX_MODIFIER),
            ]);
        }
    }
}
