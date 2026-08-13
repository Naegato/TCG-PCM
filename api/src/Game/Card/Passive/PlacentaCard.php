<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class PlacentaCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public const CardRarityEnum RARITY = CardRarityEnum::UNCOMMON;

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const HEALTH_GAIN = 5;

    public function getId(): string
    {
        return 'Placenta';
    }

    public function getImage(): string
    {
        return 'https://static.wikia.nocookie.net/bindingofisaac/images/f/f9/Objeto_Placenta.png/revision/latest?cb=20210304051738&path-prefix=es';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::HEALTH_GAIN, true),
        ]);
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $gameContext->heal($this->getValue(self::HEALTH_GAIN, true), $this->ownerId);
    }
}
