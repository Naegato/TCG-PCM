<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\GameContext;
use App\Game\GameUtils;

final class SpicyD6Card extends AbstractConsumableCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const DAMAGE_MULTIPLIER = 10;

    public function getId(): string
    {
        return 'Spicy-D6';
    }

    public function getImage(): string
    {
        return 'https://www.shutterstock.com/image-photo/red-die-on-white-six-260nw-27724336.jpg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE_MULTIPLIER, true),
            'value2' => intdiv($this->getValue(self::DAMAGE_MULTIPLIER, true), 2),
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $rolled = $context->rollDice(6);
        $damage = $rolled * $this->getValue(self::DAMAGE_MULTIPLIER, true);

        $context->attack($damage);

        $ownerId = $this->getOwnerId();
        if (null === $ownerId) {
            return;
        }

        $context->attack(intdiv($damage, 2), $ownerId);
    }
}
