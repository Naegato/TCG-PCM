<?php

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\AbstractCard;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\Trait\CardAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class RacismCard extends AbstractPassiveCard implements CardAwareInterface
{
    use CardAwareTrait;

    private const int BASE_ATTACK = 5;

    public static CardRarityEnum $rarity = CardRarityEnum::RARE;

    public function getId(): string
    {
        return 'Racism';
    }

    public function getImage(): string
    {
        return 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQCtN28pegjPCSbS7bHpav-R_25zXujpIE1QFJYqX2LQ_yowb4KN-tFs0u0&s=10';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::BASE_ATTACK, true),
        ]);
    }

    public function onCardPlayed(AbstractCard $card, GameContext $gameContext): void
    {
        if (!$card instanceof AbstractMonsterCard) {
            return;
        }

        if (CardSetEnum::ORIGINAL !== $card::$serie) {
            return;
        }

        if (!($cardId = $card->getInstanceId())) {
            throw new \RuntimeException('Card instance ID is not set.');
        }

        $gameContext->damageCard($cardId, $this->getValue(self::BASE_ATTACK, true));
    }
}
