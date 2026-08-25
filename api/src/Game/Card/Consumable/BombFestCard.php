<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardActions;
use App\Game\GameContext;

final class BombFestCard extends AbstractConsumableCard
{
    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::RARE;
    }

    public function getId(): string
    {
        return 'BombFest';
    }

    public function getImage(): string
    {
        return 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/62/Thomas_C._Lea_III_-_That_Two-Thousand_Yard_Stare_-_Original.jpg/960px-Thomas_C._Lea_III_-_That_Two-Thousand_Yard_Stare_-_Original.jpg?utm_source=en.wikipedia.org&utm_campaign=index&utm_content=thumbnail';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $cards = CardActions::getAllCardInGroups($context, 'bomb');

        foreach ($cards as $card) {
            $context->pushGameEvent(GameEventTypeEnum::CARD_CONSUMED, [
                'cardId' => $card->instanceId,
                'playerId' => $card->ownerId,
            ]);
        }

        $context->addCoins(\count($cards));
    }
}
