<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Enum\CardEffectEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Effect\EffectState;
use App\Game\Card\MonsterCardState;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayerState;

class GameEventApplier implements GameEventApplierInterface
{
    public function apply(GameEvent $event, GameState $gameState): GameState
    {
        $gameState = match ($event->type) {
            GameEventTypeEnum::CARD_DRAWN => $this->applyCardDrawn($event, $gameState),
            GameEventTypeEnum::CARD_PLAYED => $this->applyCardPlayed($event, $gameState),
            GameEventTypeEnum::DAMAGE_DEALT => $this->applyDamage($event, $gameState),
            GameEventTypeEnum::HEAL_APPLIED => $this->applyHeal($event, $gameState),
            GameEventTypeEnum::TURN_ENDED => $this->applyTurnEnded($event, $gameState),
            GameEventTypeEnum::EFFECT_ADDED => $this->applyEffectAdded($event, $gameState),
            GameEventTypeEnum::CARD_DISCARDED => $this->applyCardDiscarded($event, $gameState),
            GameEventTypeEnum::CARD_PLACED_IN_PLAY_AREA => $this->applyCardPlaceInPlayArea($event, $gameState),
            GameEventTypeEnum::CARD_PLACED_IN_MONSTER_AREA => $this->applyCardPlaceInMonsterArea($event, $gameState),
            GameEventTypeEnum::CARD_STATE_UPDATED => $this->applyCardStateUpdate($event, $gameState),
            GameEventTypeEnum::MONSTER_DIED => $this->applyMonsterDied($event, $gameState),
            GameEventTypeEnum::COINS_GAINED, GameEventTypeEnum::COINS_LOST => $this->applyCoinsChange($event, $gameState),
            GameEventTypeEnum::CARD_GENERATED => $this->applyCardGenerated($event, $gameState),
            GameEventTypeEnum::CARD_REDRAWN => $this->applyCardRedrawn($event, $gameState),
            GameEventTypeEnum::CARD_STOLEN => $this->ApplyCardStolen($event, $gameState),
            GameEventTypeEnum::CURRENT_PLAYER_SET => $this->applyCurrentPlayerSet($event, $gameState),
            GameEventTypeEnum::PLAYER_DIED,
            GameEventTypeEnum::CARD_CONSUMED,
            GameEventTypeEnum::ATTACKED,
            GameEventTypeEnum::CARD_RUNTIME_VALUE,
            GameEventTypeEnum::DICE_ROLLED,
            GameEventTypeEnum::CARD_ACTION_PREVENTED,
            GameEventTypeEnum::TURN_STARTED,
                => $this->noOp($event, $gameState),
        };

        return $event->id ? $gameState->withLastEventId($event->id) : $gameState;
    }

    public function applyMultiple(array $events, GameState $gameState): GameState
    {
        foreach ($events as $event) {
            $gameState = $this->apply($event, $gameState);
        }

        return $gameState;
    }

    private function applyCardDrawn(GameEvent $event, GameState $state): GameState
    {
        $playerId = $event->data['playerId'] ?? null;

        if (!\is_string($playerId)) {
            throw new \LogicException('CARD_DRAWN requires a playerId');
        }

        $player = $state->getPlayer($playerId);

        if (null === ($event->data['cardId'] ?? null)) {
            $deck = $player->drawPile;

            $instanceId = array_key_first($deck);
            $drawn = array_shift($deck);

            $newPlayer = $player->withNewHandAndDeck([...$player->hand, $instanceId], $deck);
            $cardState = new CardState($instanceId, $drawn, $playerId);
        } else {
            $instanceId = $event->data['cardId'];
            $newPlayer = $player->withNewHandAndDeck([...$player->hand, $instanceId], $player->drawPile);

            $cardState = $state->getCardState($instanceId);
        }

        if (!$instanceId) {
            // @ŧodo voir comportement quand plus de cartes
            throw new \LogicException(\sprintf('Player %s has no more cards to draw', $playerId));
        }
        $state = $state->withUpdatedPlayer($newPlayer);

        return $state->addCard($cardState);
    }

    private function applyCardPlayed(GameEvent $event, GameState $gameState): GameState
    {
        $cardId = $event->data['cardId'] ?? null;
        $playerId = $event->data['playerId'] ?? null;

        if (!\is_string($cardId)) {
            throw new \LogicException('CARD_PLAYED requires a cardId');
        }

        if (!\is_string($playerId)) {
            throw new \LogicException('CARD_PLAYED requires a playerId');
        }

        $player = $gameState->getPlayer($playerId);

        if (!$player->hasCardInHand($cardId)) {
            throw new \LogicException(\sprintf('Player %s does not have card %s in hand', $playerId, $cardId));
        }

        $player = $player->removeCardFromHand($cardId);

        return $gameState->withUpdatedPlayer($player);
    }

    private function applyDamage(GameEvent $event, GameState $gameState): GameState
    {
        $target = $event->data['targetId'] ?? null;
        $damage = $event->data['damage'] ?? null;

        if (!\is_string($target)) {
            throw new \LogicException('DAMAGE requires a targetId');
        }

        if (!\is_int($damage)) {
            throw new \LogicException('DAMAGE requires a damage integer');
        }

        if ($damage < 0) {
            throw new \LogicException('DAMAGE requires a positive damage integer');
        }

        $state = match ($target) {
            $gameState->player1->player->id, $gameState->player1->characterCardId => $gameState->player1,
            $gameState->player2->player->id, $gameState->player2->characterCardId => $gameState->player2,
            default => $gameState->getCardState($target),
        };

        if ($state instanceof PlayerState) {
            $newState = $state->withUpdatedHealth($state->healthPoints - $damage);
            $newGameState = $gameState->withUpdatedPlayer($newState);
        } elseif ($state instanceof MonsterCardState) {
            $newState = $state->withCurrentHealthPoints(max(0, $state->currentHealthPoints - $damage));
            $newGameState = $gameState->withUpdatedCardState($newState);
        } else {
            throw new \LogicException('DAMAGE target must be a player or a monster card');
        }

        return $newGameState;
    }

    public function applyHeal(GameEvent $event, GameState $gameState): GameState
    {
        $target = $event->data['targetId'] ?? null;
        $amount = $event->data['amount'] ?? null;

        if (!\is_string($target)) {
            throw new \LogicException('HEAL requires a targetId');
        }

        if (!\is_int($amount)) {
            throw new \LogicException('HEAL requires a amount integer');
        }

        $targetState = match ($target) {
            $gameState->player1->player->id, $gameState->player1->characterCardId => $gameState->player1,
            $gameState->player2->player->id, $gameState->player2->characterCardId => $gameState->player2,
            default => $gameState->getCardState($target),
        };

        if ($targetState instanceof PlayerState) {
            $newHealth = $targetState->healthPoints + $amount;
            if ($newHealth > $targetState->maxHealthPoints) {
                $newHealth = $targetState->maxHealthPoints;
            }

            $newPlayerState = $targetState->withUpdatedHealth($newHealth);

            return $gameState->withUpdatedPlayer($newPlayerState);
        }

        if ($targetState instanceof MonsterCardState) {
            return $gameState->withUpdatedCardState($targetState->withCurrentHealthPoints($targetState->currentHealthPoints + $amount));
        }

        throw new \LogicException('HEAL target must be a player or a monster card');
    }

    private function applyTurnEnded(GameEvent $event, GameState $gameState): GameState
    {
        return $gameState->withCurrentPlayer($gameState->getNextPlayer()->id);
    }

    private function noOp(GameEvent $event, GameState $gameState): GameState
    {
        // no-op

        return $gameState;
    }

    private function applyEffectAdded(GameEvent $event, GameState $gameState): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('EffectAdded requires a cardId');
        }

        if (null === ($effectId = $event->data['effect'] ?? null)) {
            throw new \LogicException('EffectAdded requires an effect');
        }

        if (!($cardState = $gameState->cards[$cardId] ?? null)) {
            throw new \LogicException('EffectAdded requires a valid cardId');
        }

        if (!($effect = CardEffectEnum::tryFrom((string) $effectId))) {
            throw new \LogicException('EffectAdded requires a valid effect');
        }

        $cardState = $cardState->addEffect(new EffectState($effect, $event->data['effectValues'] ?? []));

        return $gameState->withUpdatedCardState($cardState);
    }

    private function applyCardDiscarded(GameEvent $event, GameState $gameState): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('DiscardCard requires a cardId');
        }

        $cardState = $gameState->getCardState($cardId);

        if (!$cardState) {
            throw new \LogicException('DiscardCard requires a valid cardId');
        }

        $playerId = $cardState->ownerId;

        $player = $gameState->getPlayer($playerId);

        if (\in_array($cardId, $player->playArea->passiveCards, true)) {
            $newPlayArea = $player->playArea->removePassiveCard($cardId);
            $player = $player->withPlayArea($newPlayArea);
        } elseif (\in_array($cardId, $player->playArea->monsterCards, true)) {
            $newPlayArea = $player->playArea->removeMonsterCard($cardId);
            $player = $player->withPlayArea($newPlayArea);
        }

        $player = $player->withDiscardedCard($cardId, $cardState->templateId);

        return $gameState->withUpdatedPlayer($player)->resetCardState($cardId);
    }

    private function applyCardPlaceInPlayArea(GameEvent $event, GameState $gameState): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('CardPlaceInPlayArea requires a cardId');
        }

        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('CardPlaceInPlayArea requires a playerId');
        }

        $player = $gameState->getPlayer($playerId);
        $newArea = $player->playArea->addPassiveCard($cardId);

        return $gameState->withUpdatedPlayer($player->withPlayArea($newArea));
    }

    private function applyCardPlaceInMonsterArea(GameEvent $event, GameState $gameState): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('CardPlaceInMonsterArea requires a cardId');
        }

        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('CardPlaceInMonsterArea requires a playerId');
        }

        if (null === ($healthpoints = $event->data['cardHealthPoints'] ?? null) || !\is_int($healthpoints)) {
            throw new \LogicException('CardPlaceInMonsterArea requires a cardHealthPoints');
        }

        if (!($cardState = $gameState->getCardState($cardId))) {
            throw new \LogicException('CardPlaceInMonsterArea requires a valid cardId');
        }

        $player = $gameState->getPlayer($playerId);
        $newArea = $player->playArea->addMonsterCard($cardId);
        $newCardState = MonsterCardState::fromParent($cardState, $healthpoints);

        return $gameState->withUpdatedPlayer($player->withPlayArea($newArea))->withUpdatedCardState($newCardState);
    }

    private function applyCardStateUpdate(GameEvent $event, GameState $gameState): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('Update card state requires a cardId');
        }

        if (!($state = $gameState->getCardState($cardId))) {
            throw new \LogicException('Update card state requires a valid cardId');
        }

        if (null === ($newStats = $event->data['stateToUpdate'] ?? null) || !\is_array($newStats)) {
            if (null === ($event->data['canAttack'] ?? null) || !$state instanceof MonsterCardState) {
                throw new \LogicException('Update card state requires a playerId');
            }
            $newState = $state->withCanAttack($event->data['canAttack']);
        } else {
            $newState = $state->updateValues($newStats);
        }

        if (null !== ($currentHealthPoints = $event->data['currentHealthPoints'] ?? null) && $newState instanceof MonsterCardState) {
            $newState = $newState->withCurrentHealthPoints($currentHealthPoints);
        }

        return $gameState->withUpdatedCardState($newState);
    }

    private function applyCoinsChange(GameEvent $event, GameState $state): GameState
    {
        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('Coins changed requires a playerId');
        }

        if (null === ($amount = $event->data['amount'] ?? null) || !\is_int($amount)) {
            throw new \LogicException('Coins changed requires an amount');
        }

        $player = $state->getPlayer($playerId);

        $newCoins = match ($event->type) {
            GameEventTypeEnum::COINS_GAINED => $player->coins + $amount,
            GameEventTypeEnum::COINS_LOST => $player->coins - $amount,
            default => throw new \LogicException('Invalid event type for coins change'),
        };

        $newPlayer = $player->withUpdatedCoins($newCoins);

        return $state->withUpdatedPlayer($newPlayer);
    }

    private function applyMonsterDied(GameEvent $event, GameState $state): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('MonsterDied requires a cardId');
        }

        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('MonsterDied requires a playerId');
        }

        $cardState = $state->getCardState($cardId);

        if (!$cardState) {
            throw new \LogicException('MonsterDied requires a valid cardId');
        }

        $player = $state->getPlayer($playerId);
        $newPlayArea = $player->playArea->removeMonsterCard($cardId);
        $player = $player->withPlayArea($newPlayArea);
        $player = $player->withDiscardedCard($cardId, $cardState->templateId);
        $state = $state->resetCardState($cardId);

        return $state->withUpdatedPlayer($player);
    }

    private function applyCardGenerated(GameEvent $event, GameState $state): GameState
    {
        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('CardGenerated requires a playerId');
        }

        if (null === ($cardTemplateId = $event->data['cardTemplateId'] ?? null) || !\is_string($cardTemplateId)) {
            throw new \LogicException('CardGenerated requires a cardId');
        }

        if (!($id = $event->data['cardInstanceId'] ?? null)) {
            throw new \LogicException('CardGenerated requires a cardInstanceId');
        }

        $cardState = new CardState((string) $id, $cardTemplateId, $playerId);

        return $state->addCard($cardState);
    }

    private function applyCardRedrawn(GameEvent $event, GameState $state): GameState
    {
        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('CardRedrawn requires a playerId');
        }

        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('CardRedrawn requires a cardId');
        }

        $player = $state->getPlayer($playerId);
        $discard = $player->discardPile;

        if (!\array_key_exists($cardId, $discard)) {
            throw new \LogicException(\sprintf('Player %s does not have card %s in hand', $playerId, $cardId));
        }
        $templateId = $discard[$cardId] ?? null;

        if (!$templateId) {
            throw new \LogicException(\sprintf('Card %s not found in discard pile of player %s', $cardId, $playerId));
        }

        $newDiscard = array_filter($discard, static fn($id) => $id !== $cardId, ARRAY_FILTER_USE_KEY);
        $player = $player->withDiscarded($newDiscard);
        $player = $player->withNewHandAndDeck([...$player->hand, $cardId], $player->drawPile);

        return $state->addCard(new CardState($cardId, $templateId, $playerId))->withUpdatedPlayer($player);
    }

    private function applyCurrentPlayerSet(GameEvent $event, GameState $state): GameState
    {
        if (null === ($playerId = $event->data['playerId'] ?? null) || !\is_string($playerId)) {
            throw new \LogicException('CurrentPlayerSet requires a playerId');
        }

        return $state->withCurrentPlayer($playerId);
    }

    private function applyCardStolen(GameEvent $event, GameState $state): GameState
    {
        if (null === ($cardId = $event->data['cardId'] ?? null) || !\is_string($cardId)) {
            throw new \LogicException('CardStolen requires a cardId');
        }

        if (null === ($fromPlayerId = $event->data['fromPlayerId'] ?? null) || !\is_string($fromPlayerId)) {
            throw new \LogicException('CardStolen requires a fromPlayerId');
        }

        if (null === ($toPlayerId = $event->data['toPlayerId'] ?? null) || !\is_string($toPlayerId)) {
            throw new \LogicException('CardStolen requires a toPlayerId');
        }

        $cardState = $state->getCardState($cardId);

        if (!$cardState) {
            throw new \LogicException('CardStolen requires a valid cardId');
        }

        $cardState = $cardState->updateOwner($toPlayerId);

        $fromPlayer = $state->getPlayer($fromPlayerId);
        $toPlayer = $state->getPlayer($toPlayerId);

        $isPassiveCard = \in_array($cardId, $fromPlayer->playArea->passiveCards, true);
        $isMonsterCard = \in_array($cardId, $fromPlayer->playArea->monsterCards, true);

        if (!$isPassiveCard && !$isMonsterCard) {
            throw new \LogicException(\sprintf('Card %s is not in the play area of player %s', $cardId, $fromPlayerId));
        }

        $newTargetPlayArea = $isPassiveCard ? $fromPlayer->playArea->removePassiveCard($cardId) : $fromPlayer->playArea->removeMonsterCard($cardId);
        $newThiefPlayArea = $isPassiveCard ? $toPlayer->playArea->addPassiveCard($cardId) : $toPlayer->playArea->addMonsterCard($cardId);

        return $state
            ->withUpdatedPlayer($fromPlayer->withPlayArea($newTargetPlayArea))
            ->withUpdatedPlayer($toPlayer->withPlayArea($newThiefPlayArea))
            ->withUpdatedCardState($cardState);
    }
}
