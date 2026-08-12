<?php

declare(strict_types=1);

namespace App\Game\Card;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Service\Game\Helper\CardHelper as HelperCardHelper;

/**
 * @todo add tests but I'm a little sad rn
 * @todo rename
 */
final /* static */ class CardHelper
{
    /**
     * @return string[]
     */
    public static function getAllMonster(GameContext $context): array
    {
        return array_merge($context->getCurrentPlayerState()->playArea->monsterCards, $context->getOpponentState()->playArea->monsterCards);
    }

    public static function generatedAndPlay(GameContext $context, string $playerId, string $cardTemplateId, bool $monster = false): void
    {
        $newInstanceId = (string) $context->state->randomizer->roll(0xFFFF_FFFF);

        $context->pushGameEvent(GameEventTypeEnum::CARD_GENERATED, [
            'playerId' => $playerId,
            'cardTemplateId' => $cardTemplateId,
            'cardInstanceId' => $newInstanceId,
        ]);

        if ($monster) {
            $replacementMonster = GameUtils::getService('cards')->createCardInstance($cardTemplateId);

            if (!$replacementMonster instanceof AbstractMonsterCard) {
                throw new \LogicException('Chaos picked a non-monster template for a monster slot');
            }

            $context->pushGameEvent(GameEventTypeEnum::CARD_PLACE_IN_MONSTER_AREA, [
                'playerId' => $playerId,
                'cardId' => $newInstanceId,
                'cardHealthPoints' => $replacementMonster->getHealPoints(),
            ]);
        } else {
            $context->pushGameEvent(GameEventTypeEnum::CARD_PLACE_IN_PLAY_AREA, [
                'playerId' => $playerId,
                'cardId' => $newInstanceId,
            ]);
        }
    }

    public static function generateAndDraw(GameContext $context, string $playerId, string $cardTemplateId): void
    {
        $newInstanceId = (string) $context->state->randomizer->roll(0xFFFF_FFFF);

        $context->pushGameEvent(GameEventTypeEnum::CARD_GENERATED, [
            'playerId' => $playerId,
            'cardTemplateId' => $cardTemplateId,
            'cardInstanceId' => $newInstanceId,
        ]);

        $context->pushGameEvent(GameEventTypeEnum::CARD_DRAWN, [
            'playerId' => $playerId,
            'cardId' => $newInstanceId,
        ]);
    }

    /**
     * @return CardState[]
     */
    public static function getAllCardInGroups(GameContext $ctx, string $group): array
    {
        /** @var HelperCardHelper $helper */
        $helper = GameUtils::getService('cards');
        $cards = $helper->getCardsInGroup($group);

        return array_filter($ctx->state->cards, static fn(CardState $s) => \in_array($s->templateId, $cards, true));
    }
}
