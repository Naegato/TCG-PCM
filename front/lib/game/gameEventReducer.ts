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

export type CardMarkerPayload = {
  text: string;
  tone: "positive" | "negative";
};

/**
 * Short "+1"/"-2 PV"-style marker for an event caused by a specific card's CARD_TRIGGERED_ACTION
 * (see processEvents in GameContext.tsx, which resolves which card via event.parentId) — shown
 * floating on that card instead of the generic toast, since the card itself is who acted.
 */
export function getCardMarker(event: GameEvent): CardMarkerPayload | null {
  switch (event.type) {
    case GameEventType.COINS_GAINED:
      return typeof event.data?.amount === "number"
        ? { text: `+${event.data.amount}`, tone: "positive" }
        : null;
    case GameEventType.COINS_LOST:
      return typeof event.data?.amount === "number"
        ? { text: `-${event.data.amount}`, tone: "negative" }
        : null;
    case GameEventType.HEAL_APPLIED:
      return typeof event.data?.amount === "number"
        ? { text: `+${event.data.amount} PV`, tone: "positive" }
        : null;
    case GameEventType.DAMAGE_DEALT:
      return typeof event.data?.damage === "number"
        ? { text: `-${event.data.damage} PV`, tone: "negative" }
        : null;
    default:
      return null;
  }
}

/**
 * The backend resolves breadth-first: every card's CARD_TRIGGERED_ACTION for a given trigger is
 * generated before any of their own effects (e.g. two cards reacting to TURN_STARTED both get
 * triggered, *then* both of their COINS_GAINED reactions follow) — correct for game logic, but
 * played back literally it reads as "every card shakes, then every effect fires" instead of one
 * card finishing before the next starts.
 *
 * Regroups the batch so each CARD_TRIGGERED_ACTION is immediately followed by everything caused
 * by it (found by walking event.parentId), while preserving the original relative order both
 * across groups and within them — a stable bucket sort, not a resort. Events with no
 * CARD_TRIGGERED_ACTION anywhere in their ancestry (e.g. TURN_STARTED itself, or a direct
 * attack's DAMAGE_DEALT) are left exactly where they were, each standing alone.
 */
export function reorderEventsForPlayback(events: GameEvent[]): GameEvent[] {
  const eventById = new Map(events.map((e) => [e.id, e]));
  const cardTriggerAncestorCache = new Map<number, number | null>();

  const findCardTriggerAncestor = (event: GameEvent): number | null => {
    if (cardTriggerAncestorCache.has(event.id)) {
      return cardTriggerAncestorCache.get(event.id) ?? null;
    }

    // Set before recursing so a cycle (shouldn't happen, but the data comes over the wire)
    // resolves to "no ancestor" instead of looping forever.
    cardTriggerAncestorCache.set(event.id, null);

    let result: number | null = null;
    if (event.type === GameEventType.CARD_TRIGGERED_ACTION) {
      result = event.id;
    } else if (event.parentId != null) {
      const parent = eventById.get(event.parentId);
      result = parent ? findCardTriggerAncestor(parent) : null;
    }

    cardTriggerAncestorCache.set(event.id, result);
    return result;
  };

  const buckets = new Map<number, GameEvent[]>();
  const orderedKeys: number[] = [];

  for (const event of events) {
    const key = findCardTriggerAncestor(event) ?? event.id;
    let bucket = buckets.get(key);

    if (!bucket) {
      bucket = [];
      buckets.set(key, bucket);
      orderedKeys.push(key);
    }

    bucket.push(event);
  }

  return orderedKeys.flatMap((key) => buckets.get(key)!);
}

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

    case GameEventType.CARD_ACTION_PREVENTED: {
      const card = getCard(state, event.data.cardId);

      if (card) {
        return {
          text: `${card.name} : action empêchée (${event.data.reason})`,
          tone: "negative",
        };
      }

      return null;
    }

    default:
      return null;
  }
}

export function applyGameView(
  state: GameState,
  event: GameEvent,
  currentUsername?: string,
  // True for viewers subscribed to every player's private topic (e.g. the admin debug
  // view). Such a viewer always receives the enriched private version of an event
  // like CARD_DRAWN in addition to the bare public one, so the public one must always
  // be skipped for them — not just when it happens to match "their" player.
  omniscient = false,
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
      // the enriched private version of this event (with view.card) is always
      // delivered separately to whoever can see this player's hand — skip the bare
      // public one to avoid double-applying the same draw
      if (!view.card && (omniscient || player.player.name === currentUsername)) {
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

    case GameEventType.CURRENT_PLAYER_SET: {
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
