<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardSetEnum;
use App\Game\Card\CardHelper;
use App\Game\GameContext;
use App\Game\GameUtils;
use Override;

final class BombCard extends AbstractConsumableCard
{
    public static CardSetEnum $serie = CardSetEnum::TBOI;

    private const DAMAGE = 5;

    public function getId(): string
    {
        return 'Bomb';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
        ]);
    }

    #[Override]
    public function getImage(): string
    {
        return 'bomb.png';
    }

    public function play(GameContext $context, array $data = []): void
    {
        foreach (CardHelper::getAllMonster($context) as $monster) {
            $context->damageCard($monster, $this->getValue(self::DAMAGE, true));
        }
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }
}
