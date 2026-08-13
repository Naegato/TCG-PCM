<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Api\DTO\CardDTO;
use App\Api\DTO\GameStateDTO;
use App\Api\DTO\PlayerStateDTO;
use App\Game\Card\CardState;
use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\MonsterCardState;
use App\Game\State\GameState;

final class GameStateConverter
{
    public function __construct(
        private CardFactoryInterface $cardFactory,
    ) {}

    public function convertGameState(GameState $gameState, string $playerId): GameStateDTO
    {
        $cards = $this->removeCardsNotVisibleToPlayer($gameState, $playerId) |> $this->convertCards(...);

        return new GameStateDTO(
            player1: PlayerStateDTO::fromPlayerState($gameState->player1),
            player2: PlayerStateDTO::fromPlayerState($gameState->player2),
            currentPlayer: $gameState->currentPlayer,
            cards: $cards,
        );
    }

    /**
     * Same as convertGameState(), but exposes both players' hands unfiltered — for admin/debug tooling only.
     */
    public function convertGameStateForAdmin(GameState $gameState): GameStateDTO
    {
        return new GameStateDTO(
            player1: PlayerStateDTO::fromPlayerState($gameState->player1),
            player2: PlayerStateDTO::fromPlayerState($gameState->player2),
            currentPlayer: $gameState->currentPlayer,
            cards: $this->convertCards($gameState->cards),
        );
    }

    public function createCardDTO(CardState $state): CardDTO
    {
        $template = $this->cardFactory->createWithState($state->templateId, $state);

        $cost = null;
        $hp = null;
        $attack = null;

        if (!$template instanceof AbstractCharacterCard) {
            $cost = $template->getCost();
        }

        if ($template instanceof AbstractMonsterCard) {
            $hp = $state instanceof MonsterCardState ? $template->getCurrentHealthPoints() : $template->getHealPoints();
            $attack = $template->getAttack();
        }

        return new CardDTO(
            name: $template->getName(),
            description: $template->getDescription(),
            image: $template->getImage(),
            requiresTarget: $template->requiresTarget(),
            targetType: $template->getTargetType(),
            rarity: $template->getRarity(),
            set: $template->getSerie(),
            instanceId: $state->instanceId,
            effects: $state->effects,
            isActive: $state instanceof MonsterCardState ? $state->canAttack : true,
            type: $template->getType(),
            cost: $cost,
            hp: $hp,
            attack: $attack,
            values: $state->values,
        );
    }

    /**
     * @param array<string, CardState> $cardsState
     *
     * @return array<string, CardDTO>
     */
    private function convertCards(array $cardsState): array
    {
        return array_map($this->createCardDTO(...), $cardsState);
    }

    /**
     * @return array<string, CardState>
     */
    private function removeCardsNotVisibleToPlayer(GameState $gameState, string $playerId): array
    {
        return array_filter(
            $gameState->cards,
            static fn(CardState $state) => !\in_array($state->instanceId, $gameState->getOtherPlayerStateById($playerId)->hand, true),
        );
    }
}
