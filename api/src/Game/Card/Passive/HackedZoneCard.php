<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardEffectEnum;
use App\Enum\CardRarityEnum;
use App\Game\Card\Effect\HackedCardEffect;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Trait\CardAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class HackedZoneCard extends AbstractPassiveCard implements CardAwareInterface
{
    use CardAwareTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getId(): string
    {
        return 'HackedZone';
    }

    public function getImage(): string
    {
        return 'https://m.media-amazon.com/images/I/71I-l6f6OaL._AC_SX569_.jpg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'effect' => CardEffectEnum::HACKED,
        ]);
    }

    public function onCardPlace(GameContext $gameContext): void
    {
        $cards = array_merge(
            $gameContext->state->player1->hand,
            $gameContext->state->player2->hand,
            $gameContext->state->player1->playArea->getAll(),
            $gameContext->state->player2->playArea->getAll(),
            [
                $gameContext->state->player1->characterCardId,
                $gameContext->state->player2->characterCardId,
            ],
        );

        foreach ($cards as $card) {
            $gameContext->addEffect(CardEffectEnum::HACKED, $card, [
                'value' => $gameContext->randomIntBetween(HackedCardEffect::MIN_MODIFIER, HackedCardEffect::MAX_MODIFIER),
            ]);
        }
    }

    public function onCardDrawn(string $cardId, GameContext $gameContext): void
    {
        $this->beforeAction($gameContext);

        if ($gameContext->lastActionHasBeenPrevented()) {
            return;
        }

        $gameContext->addEffect(CardEffectEnum::HACKED, $cardId, [
            'value' => $gameContext->randomIntBetween(HackedCardEffect::MIN_MODIFIER, HackedCardEffect::MAX_MODIFIER),
        ]);
    }
}
