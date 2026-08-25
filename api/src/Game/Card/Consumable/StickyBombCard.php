<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class StickyBombCard extends AbstractConsumableCard
{
    private const DAMAGE = 12;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'StickyBomb';
    }

    public function getImage(): string
    {
        return 'stickybomb.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
        ]);
    }

    public function getTargetType(): int
    {
        return self::TARGET_TYPE_MONSTER | self::TARGET_OPPONENT_CARDS;
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
