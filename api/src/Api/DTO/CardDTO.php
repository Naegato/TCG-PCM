<?php

declare(strict_types=1);

namespace App\Api\DTO;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\CardTypeEnum;
use App\Game\Card\Effect\EffectState;

final readonly class CardDTO
{
    /**
     * @param EffectState[] $effects
     * @param array<string, mixed> $values
     */
    // @mago-ignore lint:excessive-parameter-list
    public function __construct(
        public string $name,
        public string $description,
        public string $image,
        public bool $requiresTarget,
        public ?int $targetType,
        public CardRarityEnum $rarity,
        public CardSetEnum $set,
        public string $instanceId,
        public array $effects,
        public bool $isActive = true,
        public ?CardTypeEnum $type = null,
        public ?int $cost = null,
        public ?int $hp = null,
        public ?int $attack = null,
        public array $values = [],
    ) {}
}
