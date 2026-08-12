import { GameEventType } from "@/lib/game/type/eventType";
import { GameEvent } from "@/lib/game/type/gameEvent";
import { CardState, GameState, PlayerState } from "@/lib/game/type/gameState";
import { emitter } from "@/lib/eventBus";

export type AnnouncementTone = "neutral" | "positive" | "negative";

export type AnnouncementPayload = {
  text: string;
  tone: AnnouncementTone;
  presentation?: "normal" | "giant";
};

export function getPlayerKey(
  state: GameState,
  playerId: string,
): "player1" | "player2" {
  return state.player1.player.id === playerId ? "player1" : "player2";
}

function getPlayer(state: GameState, playerId: string): PlayerState {
  const playerKey = getPlayerKey(state, playerId);
  return state[playerKey];
}

function getCard(state: GameState, cardId: string): CardState | undefined {
  return state.cards[cardId];
}

export function animateGameEvent(
  state: GameState,
  event: GameEvent,
): AnnouncementPayload | null {
  if (event.type === GameEventType.DICE_ROLLED) {
    if (!event.data.faces) return null;
    const rollValue = event.data.result;

    return {
      text: rollValue === null ? "🎲 Lancer de dés" : `🎲 ${rollValue}`,
      tone: "neutral",
      presentation: "giant",
    };
  }

  if (!event.view) return null;

  const view = event.view;

  switch (event.type) {
    case GameEventType.TURN_STARTED: {
      const player = getPlayer(state, String(view.currentPlayer));
      return {
        text: `Tour de ${player.player.name}`,
        tone: "neutral",
      };
    }
    case GameEventType.CARD_PLACED_IN_MONSTER_AREA:
    case GameEventType.CARD_PLACED_IN_PLAY_AREA: {
      const card = event.view.card || getCard(state, view.cardId);
      const player = getPlayer(state, view.playerId);
      if (card && player) {
        return {
          text: `${player.player.name} a joué ${card.name}`,
          tone: "neutral",
        };
      }
      return null;
    }
    case GameEventType.CARD_REDRAWN: {
      const card = getCard(state, view.cardId);
      const player = getPlayer(state, view.playerId);

      if (card && player) {
        return {
          text: `${player.player.name} a repris ${card.name} depuis le cimetière`,
          tone: "neutral",
        };
      }

      return null;
    }
    case GameEventType.COINS_GAINED:
    case GameEventType.COINS_LOST: {
      const playerKey = getPlayerKey(state, view.playerId);
      const previousCoins = state[playerKey].coins;
      const nextCoins = view.total;

      if (nextCoins !== previousCoins) {
        const delta = nextCoins - previousCoins;
        return {
          text: `${state[playerKey].player.name} ${
            delta > 0 ? "+" : ""
          }${delta} pièces`,
          tone: delta > 0 ? "positive" : "negative",
        };
      }

      return null;
    }

    case GameEventType.HEAL_APPLIED:
    case GameEventType.DAMAGE_DEALT: {
      if (event.type === GameEventType.DAMAGE_DEALT && event.data.sourceId) {
        const attackerCard = getCard(state, event.data.sourceId);
        const targetId = view.cardId ?? view.playerId;
        const targetPlayer = getPlayer(state, view.playerId);
        const targetCard = getCard(state, targetId);
        const targetName = targetCard?.name ?? targetPlayer.player.name;
        const damageDealt = event.data.damage;

        if (attackerCard && targetName && damageDealt > 0) {
          let text = `${attackerCard.name} attaque ${targetName} pour ${damageDealt} dégâts.`;
          return { text, tone: "neutral" };
        }
      }

      if (typeof view.total !== "number") {
        return null;
      }

      const playerKey = getPlayerKey(state, view.playerId);
      const previousHealth = state[playerKey].healthPoints;
      const nextHealth = view.total;

      if (nextHealth !== previousHealth) {
        const delta = nextHealth - previousHealth;
        return {
          text: `${state[playerKey].player.name} ${
            delta > 0 ? "+" : ""
          }${delta} PV`,
          tone: delta > 0 ? "positive" : "negative",
        };
      }

      return null;
    }
    case GameEventType.MONSTER_DIED: {
      const card = getCard(state, view.cardId);
      if (card) {
        return {
          text: `${card.name} a rejoint le cimetière.`,
          tone: "negative",
        };
      }
    }

    case GameEventType.CARD_STOLEN: {
      const card = getCard(state, event.data.cardId);
      const fromPlayer = getPlayer(state, event.data.fromPlayerId);
      const toPlayer = getPlayer(state, event.data.toPlayerId);

      if (card && fromPlayer && toPlayer) {
        return {
          text: `${toPlayer.player.name} a volé ${card.name} à ${fromPlayer.player.name}!`,
          tone: "neutral",
        };
      }
    }

    default:
      return null;
  }
}

export function applyGameView(
  state: GameState,
  event: GameEvent,
  currentUsername?: string,
): GameState {
  if (event.type === GameEventType.CARD_STATE_UPDATED) {
    const updateCardId = event.view?.cardId ?? event.data?.cardId;
    const cardToUpdate = updateCardId ? state.cards[updateCardId] : undefined;

    if (!updateCardId || !cardToUpdate) {
      return state;
    }

    const stateToUpdate =
      event.data &&
      typeof event.data === "object" &&
      !Array.isArray(event.data) &&
      event.data.stateToUpdate &&
      typeof event.data.stateToUpdate === "object" &&
      !Array.isArray(event.data.stateToUpdate)
        ? (event.data.stateToUpdate as Record<string, unknown>)
        : null;

    if (!stateToUpdate) {
      if (!event.view?.card) {
        return state;
      }

      const nextCard = {
        ...cardToUpdate,
        ...event.view.card,
      };

      return {
        ...state,
        cards: {
          ...state.cards,
          [updateCardId]: nextCard,
        },
      };
    }

    const currentValues =
      cardToUpdate.values &&
      typeof cardToUpdate.values === "object" &&
      !Array.isArray(cardToUpdate.values)
        ? (cardToUpdate.values as Record<string, unknown>)
        : {};
    const viewCardValues =
      event.view?.card &&
      typeof event.view.card === "object" &&
      !Array.isArray(event.view.card) &&
      event.view.card.values &&
      typeof event.view.card.values === "object" &&
      !Array.isArray(event.view.card.values)
        ? (event.view.card.values as Record<string, unknown>)
        : {};
    const nextCard = {
      ...cardToUpdate,
      ...(event.view?.card ?? {}),
      values: {
        ...currentValues,
        ...viewCardValues,
        ...stateToUpdate,
      },
    };

    return {
      ...state,
      cards: {
        ...state.cards,
        [updateCardId]: nextCard,
      },
    };
  }

  if (!event.view) return state;

  const next = { ...state };
  const view = event.view;

  switch (event.type) {
    case GameEventType.CARD_DRAWN: {
      const playerKey = getPlayerKey(state, view.playerId);
      const player = state[playerKey];
      // skip
      if (!view.card && player.player.name === currentUsername) {
        return next;
      }

      const newHand = [...player.hand, view.cardId];

      const newDrawPile = player.drawPile.filter((id) => id !== view.cardId);

      next[playerKey] = {
        ...player,
        hand: newHand,
        drawPile: newDrawPile,
      };

      if (view.card) {
        next.cards = {
          ...next.cards,
          [view.card.instanceId]: view.card,
        };
      }

      emitter.emit("game:card-drawn", {
        playerId: view.playerId,
        cardId: view.cardId,
      });

      return next;
    }
    case GameEventType.CARD_REDRAWN: {
      const playerKey = getPlayerKey(state, view.playerId);
      const player = state[playerKey];
      const nextHand = [...player.hand, view.cardId];
      const nextDiscardPile = { ...player.discardPile };
      delete nextDiscardPile[view.cardId];

      return {
        ...state,
        [playerKey]: {
          ...player,
          hand: nextHand,
          discardPile: nextDiscardPile,
        },
      };
    }

    case GameEventType.TURN_STARTED: {
      return {
        ...state,
        currentPlayerId: String(view.currentPlayer),
      };
    }

    case GameEventType.CARD_DISCARDED:
    case GameEventType.CARD_PLACED_IN_PLAY_AREA:
    case GameEventType.CARD_PLACED_IN_MONSTER_AREA: {
      const cardId = view.cardId;
      const card = state.cards[cardId];

      const playerKey = getPlayerKey(state, view.playerId);
      const player = state[playerKey];

      const nextPlayer = {
        ...player,
        hand: player.hand.filter((id) => id !== cardId),
      };

      if (event.type === GameEventType.CARD_DISCARDED) {
        if (nextPlayer.playArea.monsterCards.includes(cardId)) {
          nextPlayer.playArea.monsterCards =
            nextPlayer.playArea.monsterCards.filter((id) => id !== cardId);
        } else if (nextPlayer.playArea.passiveCards.includes(cardId)) {
          nextPlayer.playArea.passiveCards =
            nextPlayer.playArea.passiveCards.filter((id) => id !== cardId);
        }

        const cardToEmit = view.card || card;
        if (cardToEmit) {
          emitter.emit("card:discarded", { card: cardToEmit });
        }

        return {
          ...state,
          [playerKey]: {
            ...nextPlayer,
            discardPile: {
              ...player.discardPile,
              [cardId]: card?.instanceId ?? cardId,
            },
          },
        };
      }

      const cardToEmit = view.card || card;
      if (cardToEmit) {
        emitter.emit("card:played", {
          card: cardToEmit,
          playerId: view.playerId,
        });
      }

      return {
        ...state,
        [playerKey]: {
          ...nextPlayer,
          playArea: {
            passiveCards:
              event.type === GameEventType.CARD_PLACED_IN_PLAY_AREA
                ? [...player.playArea.passiveCards, cardId]
                : player.playArea.passiveCards,
            monsterCards:
              event.type === GameEventType.CARD_PLACED_IN_MONSTER_AREA
                ? [...player.playArea.monsterCards, cardId]
                : player.playArea.monsterCards,
          },
        },
        cards: {
          ...state.cards,
          ...(view.card ? { [cardId]: view.card } : {}),
        },
      };
    }

    case GameEventType.COINS_GAINED:
    case GameEventType.COINS_LOST: {
      const nextCoins = view.total;

      const playerKey = getPlayerKey(state, view.playerId);
      const player = state[playerKey];
      const previousCoins = player.coins;
      const delta = nextCoins - previousCoins;

      emitter.emit("game:coins-changed", {
        playerId: view.playerId,
        delta,
      });

      return {
        ...state,
        [playerKey]: {
          ...state[playerKey],
          coins: nextCoins,
        },
      };
    }

    case GameEventType.HEAL_APPLIED:
    case GameEventType.DAMAGE_DEALT: {
      if (view.cardId && view.card) {
        const newCards = { ...state.cards };
        newCards[view.cardId] = { ...newCards[view.cardId], ...view.card };

        return {
          ...state,
          cards: newCards,
        };
      }

      if (typeof view.total !== "number") {
        return state;
      }

      const nextHealth = view.total;

      const playerKey = getPlayerKey(state, view.playerId);
      const player = state[playerKey];
      const previousHealth = player.healthPoints;
      const delta = nextHealth - previousHealth;

      emitter.emit("game:health-changed", {
        playerId: view.playerId,
        delta,
        type: event.type === GameEventType.DAMAGE_DEALT ? "damage" : "heal",
      });

      return {
        ...state,
        [playerKey]: {
          ...state[playerKey],
          healthPoints: nextHealth,
        },
      };
    }

    case GameEventType.EFFECT_ADDED:
    case GameEventType.CARD_STATE_UPDATED: {
      const cardId = view.cardId;

      if (!cardId || !view.card) {
        return state;
      }

      return {
        ...state,
        cards: {
          ...state.cards,
          [cardId]: view.card,
        },
      };
    }

    case GameEventType.MONSTER_DIED: {
      const cardId = view.cardId;
      const playerKey = getPlayerKey(state, view.playerId);
      const player = state[playerKey];

      return {
        ...state,
        [playerKey]: {
          ...player,
          playArea: {
            ...player.playArea,
            monsterCards: player.playArea.monsterCards.filter(
              (id) => id !== cardId,
            ),
          },
          discardPile: {
            ...player.discardPile,
            [cardId]: cardId,
          },
        },
      };
    }
    case GameEventType.CARD_STOLEN: {
      const cardId = event.data.cardId;
      const card = getCard(state, cardId);

      if (!card) {
        return state;
      }
      const thiefPlayerKey = getPlayerKey(state, event.data.toPlayerId);
      const targetPlayerKey = getPlayerKey(state, event.data.fromPlayerId);
      const thiefPlayer = state[thiefPlayerKey];
      const targetPlayer = state[targetPlayerKey];
      
      const isMonsterCard = targetPlayer.playArea.monsterCards.includes(cardId);

      const cardToEmit = view.card || card;
      
      emitter.emit("card:stolen", {
        card: cardToEmit,
        fromPlayerId: view.fromPlayerId,
        toPlayerId: view.toPlayerId,
      });
      
      return {
        ...state,
        [targetPlayerKey]: {
          ...targetPlayer,
          playArea: {
            ...targetPlayer.playArea,
            ...(isMonsterCard
              ? {
                  monsterCards: targetPlayer.playArea.monsterCards.filter(
                    (id) => id !== cardId,
                  ),
                }
              : {}),
            ...(!isMonsterCard
              ? {
                  passiveCards: targetPlayer.playArea.passiveCards.filter(
                    (id) => id !== cardId,
                  ),
                } : {}),
          }
        },
        [thiefPlayerKey]: {
          ...thiefPlayer,
          playArea: {
            ...thiefPlayer.playArea,
            ...(isMonsterCard
              ? {
                  monsterCards: [...thiefPlayer.playArea.monsterCards, cardId],
                }
              : {}),
            ...(!isMonsterCard
              ? {
                  passiveCards: [...thiefPlayer.playArea.passiveCards, cardId],
                } : {}), 
          }
        }
      }
    };
      
    default:
      return state;
  }
}
