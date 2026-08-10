<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardSetEnum;
use App\Game\AbstractCard;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Trait\DeathAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class ConsolationPricesCard extends AbstractPassiveCard implements DeathAwareInterface
{
    use DeathAwareTrait;

    public static CardSetEnum $serie = CardSetEnum::TBOI;

    private const int COINS_PER_DEATH = 1;

    public function getId(): string
    {
        return 'consolation_prices';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), ['value' => $this->getValue(self::COINS_PER_DEATH)]);
    }

    public function onCardDeath(AbstractCard $card, GameContext $gameContext): void
    {
        if ($card->getOwnerId() !== $this->ownerId) {
            return;
        }

        $gameContext->addCoins($this->getValue(self::COINS_PER_DEATH, true), $card->getOwnerId());
    }
}
