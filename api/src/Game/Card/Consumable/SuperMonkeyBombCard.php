<?php

declare(strict_types=1);

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Game\Card\CardActions;
use App\Game\GameContext;
use App\Game\GameUtils;

final class SuperMonkeyBombCard extends AbstractConsumableCard
{
    private const DAMAGE = 15;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::LEGENDARY;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'SuperMonkeyBomb';
    }

    public function getImage(): string
    {
        return 'supermonkeybomb.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        foreach (CardActions::getAllMonster($context) as $monster) {
            $context->damageCard($monster, $this->getValue(self::DAMAGE, true));
        }
    }
}
