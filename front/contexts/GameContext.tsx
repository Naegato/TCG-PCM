"use client";

import { BasicCard } from "@/lib/cards/types/card";
import useMercure from "@/hooks/useMercure";
import { GameEvent } from "@/lib/game/type/gameEvent";
import { GameState } from "@/lib/game/type/gameState";
import { ChatMessage } from "@/lib/game/type/chatMessage";
import { playGameAction } from "@/lib/api/gameProxy";
import api from "@/lib/api/api";
import {
  AnnouncementPayload,
  AnnouncementTone,
  animateGameEvent,
  applyGameView,
  getCardMarker,
  reorderEventsForPlayback,
} from "@/lib/game/gameEventReducer";
import { CardType, CardSet } from "@/constants/card";

import {
  createContext,
  ReactNode,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import { PlayerActionType } from "@/lib/game/type/playerAction";
import { emitter } from "@/lib/eventBus";
import { GameEventType } from "@/lib/game/type/eventType";
import {
  CARD_TRIGGERED_SHAKE_DURATION,
  CARD_MARKER_DURATION,
} from "@/lib/game/animationTimings";

export type { AnnouncementTone };

export type GameAnnouncement = {
  id: number;
  leaving?: boolean;
} & AnnouncementPayload;

const ANNOUNCEMENT_LIFETIME_MS = 2200;
const ANNOUNCEMENT_FADE_MS = 450;

// Pacing for one-by-one event playback in processEvents — how long to hold on each event
// before starting the next, based on what (if anything) it shows the player.
const SILENT_EVENT_STEP_MS = 150;
const ANNOUNCEMENT_STEP_MS = 850;

type ActionObject = {
  playCard: (cardId: string, data?: Record<string, unknown>) => void;
  attack: (cardId: string, targetId: string) => void;
  endTurn: () => void;
  pushAnnouncement: (announcement: AnnouncementPayload) => void;
  sendChatMessage: (message: string) => void;
};

export type TargetingState = {
  selectedAttackerId: string | null;
  hoveredTargetId: string | null;
  pendingPlayCardId: string | null;
  selectedHandCardId: string | null;
  isTargeting: boolean;
};

export type TargetingActions = {
  selectAttacker: (cardId: string | null) => void;
  clearSelectedAttacker: () => void;
  hoverTarget: (targetId: string | null) => void;
  requestCardTarget: (cardId: string) => void;
  handleTargetClick: (targetId: string) => void;
  cancelPendingCardTarget: () => void;
  clearAllTargeting: () => void;
  selectHandCard: (cardId: string | null) => void;
};

type GameContextType = {
  game: GameState | null;
  gameData: GameData | null;
  getCardById: (cardId: string) => BasicCard | undefined;
  announcements: GameAnnouncement[];
  chatMessages: ChatMessage[];
  actions: ActionObject;
  currentUsername?: string;
  isLoggedPlayerTurn: boolean;
  targeting: TargetingState;
  targetingActions: TargetingActions;
};

type Props = {
  children: ReactNode;
  gameId: string;
  game?: GameState | null;
  username?: string;
  mercureToken?: string;
  /** Overrides the locally-derived topic subscription (which only ever grants the
   * connected player's own private topic). Use this when the caller already knows
   * the full, correctly-scoped set of topics to subscribe to — e.g. the admin debug
   * view, which has no `username` and needs both players' private topics. */
  mercureUrl?: string;
  /** True when this viewer is subscribed to every player's private topic (the admin
   * debug view). See `applyGameView`'s `omniscient` param for why this matters. */
  omniscient?: boolean;
  chatHistory?: ChatMessage[];
};

export const GameContext = createContext<GameContextType>({
  game: null,
  gameData: null,
  getCardById: () => undefined,
  announcements: [],
  chatMessages: [],
  actions: {
    playCard: () => undefined,
    attack: () => undefined,
    endTurn: () => undefined,
    pushAnnouncement: () => undefined,
    sendChatMessage: () => undefined,
  },
  isLoggedPlayerTurn: false,
  targeting: {
    selectedAttackerId: null,
    hoveredTargetId: null,
    pendingPlayCardId: null,
    selectedHandCardId: null,
    isTargeting: false,
  },
  targetingActions: {
    selectAttacker: () => undefined,
    clearSelectedAttacker: () => undefined,
    hoverTarget: () => undefined,
    requestCardTarget: () => undefined,
    handleTargetClick: () => undefined,
    cancelPendingCardTarget: () => undefined,
    clearAllTargeting: () => undefined,
    selectHandCard: () => undefined,
  },
});

export type GameData = {
  cardEffects: Record<string, CardEffectData>;
  cards: Record<string, BasicCard>;
};

type CardEffectData = {
  name: string;
  description: string;
};

const isGameData = (value: unknown): value is GameData => {
  if (!value || typeof value !== "object") {
    return false;
  }

  const candidate = value as {
    cards?: unknown;
    cardEffects?: unknown;
  };

  return (
    !!candidate.cards &&
    typeof candidate.cards === "object" &&
    !!candidate.cardEffects &&
    typeof candidate.cardEffects === "object"
  );
};

export const GameProvider = ({
  children,
  gameId,
  game: initialGame,
  username,
  mercureToken,
  mercureUrl: mercureUrlOverride,
  omniscient = false,
  chatHistory,
}: Props) => {
  useEffect(() => {
    if (!mercureToken) return;

    document.cookie = `mercureAuthorization=${mercureToken}; path=/; max-age=3600; secure; samesite=strict`;
  }, [mercureToken]);

  const normalizeGameState = useCallback(
    (state: GameState | null | undefined) => {
      if (!state) {
        return null;
      }

      const legacyCurrentPlayer = (
        state as GameState & { currentPlayer?: string | number }
      ).currentPlayer;

      const normalized = {
        ...state,
        currentPlayerId:
          state.currentPlayerId ||
          (legacyCurrentPlayer !== undefined
            ? String(legacyCurrentPlayer)
            : ""),
      } as GameState;

      return normalized;
    },
    [],
  );

  const [game, setGame] = useState<GameState | null>(
    normalizeGameState(initialGame),
  );
  const [announcements, setAnnouncements] = useState<GameAnnouncement[]>([]);
  const [chatMessages, setChatMessages] = useState<ChatMessage[]>(
    chatHistory ?? [],
  );
  const gameRef = useRef<GameState | null>(normalizeGameState(initialGame));
  const announcementIdRef = useRef(0);
  const timeoutRefs = useRef<number[]>([]);

  const [isAnimating, setIsAnimating] = useState(false);
  const [queuedEvents, setQueuedEvents] = useState<GameEvent[]>([]);

  const [gameData, setGameData] = useState<GameData | null>(null);

  const pushAnnouncement = useCallback((announcement: AnnouncementPayload) => {
    const id = ++announcementIdRef.current;

    setAnnouncements((current: GameAnnouncement[]) => [
      ...current,
      { id, ...announcement },
    ]);

    const fadeTimeoutId = window.setTimeout(() => {
      setAnnouncements((current: GameAnnouncement[]) =>
        current.map((announcement: GameAnnouncement) =>
          announcement.id === id
            ? { ...announcement, leaving: true }
            : announcement,
        ),
      );

      const removeTimeoutId = window.setTimeout(() => {
        setAnnouncements((current: GameAnnouncement[]) =>
          current.filter(
            (announcement: GameAnnouncement) => announcement.id !== id,
          ),
        );

        timeoutRefs.current = timeoutRefs.current.filter(
          (currentTimeoutId: number) => currentTimeoutId !== removeTimeoutId,
        );
      }, ANNOUNCEMENT_FADE_MS);

      timeoutRefs.current.push(removeTimeoutId);
      timeoutRefs.current = timeoutRefs.current.filter(
        (currentTimeoutId: number) => currentTimeoutId !== fadeTimeoutId,
      );
    }, ANNOUNCEMENT_LIFETIME_MS);

    timeoutRefs.current.push(fadeTimeoutId);
  }, []);

  useEffect(() => {
    return () => {
      timeoutRefs.current.forEach((timeoutId: number) =>
        window.clearTimeout(timeoutId),
      );
      timeoutRefs.current = [];
    };
  }, []);

  const getCardById = useCallback(
    (cardId: string): BasicCard | undefined => {
      if (!game) {
        return undefined;
      }

      return game.cards[cardId] as unknown as BasicCard | undefined;
    },
    [game],
  );

  const playCard = useCallback(
    async (cardId: string, data: Record<string, unknown> = {}) => {
      try {
        await playGameAction(gameId, PlayerActionType.PLAY_CARD, {
          cardId,
          data,
        });
      } catch (error) {
        const message =
          error instanceof Error ? error.message : "Une erreur est survenue";

        pushAnnouncement({
          text: message,
          tone: "negative",
        });
      }
    },
    [gameId, pushAnnouncement],
  );

  const sendChatMessage = useCallback(
    async (message: string) => {
      try {
        await api.game.sendChatMessage(gameId, message);
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Une erreur est survenue";

        pushAnnouncement({
          text: errorMessage,
          tone: "negative",
        });
      }
    },
    [gameId, pushAnnouncement],
  );

  const attack = useCallback(
    (cardId: string, targetId: string) => {
      playGameAction(gameId, PlayerActionType.ATTACK, { cardId, targetId });
      const attackerCard = getCardById(cardId);
      const cardSet: CardSet = attackerCard?.serie || CardSet.ORIGINAL;
      emitter.emit("attack-animation:start", {
        attackerId: cardId,
        targetId,
        cardSet,
      });
      setIsAnimating(true);
    },
    [gameId, getCardById],
  );

  const endTurn = useCallback(() => {
    playGameAction(gameId, PlayerActionType.END_TURN);
  }, [gameId]);

  const processEvents = useCallback(
    async (events: GameEvent[]) => {
      if (!gameRef.current) {
        return;
      }

      // Locks out incoming batches (see the `if (isAnimating)` queueing check in the
      // game_events handler below) for the whole time it takes to play this one through —
      // reusing the same lock the opponent-attack animation already relies on.
      setIsAnimating(true);

      try {
        let next = { ...gameRef.current };

        // Batch-level lookahead: a CARD_TRIGGERED_ACTION only gets animated if something in
        // this same batch actually resulted from it (an empty trigger has nothing worth
        // showing), and an event caused by one is grouped with — and animated on — that card
        // instead of a generic toast. Both need the whole batch, not just the current event,
        // hence computing them here rather than inside animateGameEvent/applyGameView.
        const eventIdsWithChildren = new Set(
          events.filter((e) => e.parentId != null).map((e) => e.parentId),
        );
        const cardIdByTriggerEventId = new Map<number, string>();
        for (const e of events) {
          if (
            e.type === GameEventType.CARD_TRIGGERED_ACTION &&
            typeof e.data?.cardId === "string"
          ) {
            cardIdByTriggerEventId.set(e.id, e.data.cardId);
          }
        }

        // Regroup so each card's trigger is immediately followed by its own effects (see
        // reorderEventsForPlayback) — otherwise BFS resolution order plays as "every card
        // shakes, then every effect fires" instead of one card finishing before the next.
        const orderedEvents = reorderEventsForPlayback(events);

        // Played one at a time — each event waits for its own animation (or a short beat if it
        // has nothing to show) before the next one starts, instead of the whole batch landing
        // in a single tick where simultaneous reactions were unreadable.
        for (const event of orderedEvents) {
          const previous = next;
          let stepDelay = SILENT_EVENT_STEP_MS;

          if (event.type === GameEventType.CARD_TRIGGERED_ACTION) {
            if (
              typeof event.data?.cardId === "string" &&
              eventIdsWithChildren.has(event.id)
            ) {
              emitter.emit("card:triggered", { cardId: event.data.cardId });
              stepDelay = CARD_TRIGGERED_SHAKE_DURATION;
            }
          } else {
            const triggeringCardId =
              event.parentId != null
                ? cardIdByTriggerEventId.get(event.parentId)
                : undefined;
            const marker = triggeringCardId ? getCardMarker(event) : null;

            if (triggeringCardId && marker) {
              emitter.emit("card:marker", { cardId: triggeringCardId, ...marker });
              stepDelay = CARD_MARKER_DURATION;
            } else {
              const announcement = animateGameEvent(previous, event);

              if (announcement) {
                pushAnnouncement(announcement);
                stepDelay = ANNOUNCEMENT_STEP_MS;
              }
            }
          }

          next = applyGameView(next, event, username, omniscient);

          const normalizedNext = normalizeGameState(next);
          gameRef.current = normalizedNext;
          setGame(normalizedNext);

          await new Promise((resolve) => setTimeout(resolve, stepDelay));
        }
      } finally {
        setIsAnimating(false);
      }
    },
    [normalizeGameState, pushAnnouncement, username, omniscient],
  );

  useEffect(() => {
    const onAnimationComplete = () => {
      setIsAnimating(false);
    };
    emitter.on("attack-animation:completed", onAnimationComplete);
    return () => {
      emitter.off("attack-animation:completed", onAnimationComplete);
    };
  }, []);

  useEffect(() => {
    if (!isAnimating && queuedEvents.length > 0) {
      processEvents(queuedEvents);
      setQueuedEvents([]);
    }
  }, [isAnimating, queuedEvents, processEvents]);

  const isLoggedPlayerTurn = useMemo(() => {
    if (!game || !username) return false;

    const connectedPlayerId =
      game.player1.player.name === username
        ? game.player1.player.id
        : game.player2.player.id;

    return connectedPlayerId === game.currentPlayerId;
  }, [game, username]);

  const [selectedAttackerId, setSelectedAttackerId] = useState<string | null>(
    null,
  );
  const [hoveredTargetId, setHoveredTargetId] = useState<string | null>(null);
  const [pendingPlayCardId, setPendingPlayCardId] = useState<string | null>(
    null,
  );
  const [selectedHandCardId, setSelectedHandCardId] = useState<string | null>(
    null,
  );

  const isTargeting = selectedAttackerId !== null || pendingPlayCardId !== null;

  const selectAttacker = useCallback(
    (cardId: string | null) => {
      if (!isLoggedPlayerTurn) return;

      setSelectedAttackerId((current) => (current === cardId ? null : cardId));
      setHoveredTargetId(null);
      setSelectedHandCardId(null);
    },
    [isLoggedPlayerTurn],
  );

  const clearSelectedAttacker = useCallback(() => {
    setSelectedAttackerId(null);
  }, []);

  const hoverTarget = useCallback((targetId: string | null) => {
    setHoveredTargetId(targetId);
  }, []);

  const requestCardTarget = useCallback((cardId: string) => {
    setPendingPlayCardId(cardId);
    setSelectedAttackerId(null);
    setHoveredTargetId(null);
    setSelectedHandCardId(null);
  }, []);

  const selectHandCard = useCallback(
    (cardId: string | null) => {
      if (!isLoggedPlayerTurn) return;

      setSelectedHandCardId((current) => (current === cardId ? null : cardId));
      setSelectedAttackerId(null);
      setHoveredTargetId(null);
    },
    [isLoggedPlayerTurn],
  );

  const handleTargetClick = useCallback(
    (targetId: string) => {
      if (pendingPlayCardId) {
        playCard(pendingPlayCardId, { target: targetId });
        setPendingPlayCardId(null);
        setHoveredTargetId(null);
        setSelectedAttackerId(null);
        return;
      }

      if (!selectedAttackerId) return;

      const attackerCard = getCardById(selectedAttackerId);
      const targetCard = getCardById(targetId);

      if (attackerCard?.type === CardType.PASSIVE) {
        pushAnnouncement({
          text: "Cible invalide",
          tone: "negative",
        });
        return;
      }

      if (
        attackerCard?.type === CardType.MONSTER &&
        targetCard?.type === CardType.PASSIVE
      ) {
        pushAnnouncement({
          text: "Cible invalide",
          tone: "negative",
        });
        return;
      }

      if (attackerCard?.type === CardType.MONSTER && game && username) {
        const loggedPlayerState =
          game.player1.player.name === username ? game.player1 : game.player2;

        const isOwnCharacterTarget =
          targetId === loggedPlayerState.characterCardId ||
          targetId === loggedPlayerState.player.id;
        const isOwnMonsterTarget =
          loggedPlayerState.playArea.monsterCards.includes(targetId);

        if (isOwnCharacterTarget || isOwnMonsterTarget) {
          pushAnnouncement({
            text: "Cible invalide",
            tone: "negative",
          });
          return;
        }
      }

      attack(selectedAttackerId, targetId);
      setHoveredTargetId(null);
      setSelectedAttackerId(null);
    },
    [
      pendingPlayCardId,
      selectedAttackerId,
      playCard,
      getCardById,
      game,
      pushAnnouncement,
      username,
      attack,
    ],
  );

  const cancelPendingCardTarget = useCallback(() => {
    setPendingPlayCardId(null);
    setHoveredTargetId(null);
  }, []);

  const clearAllTargeting = useCallback(() => {
    setHoveredTargetId(null);
    setSelectedAttackerId(null);
    setPendingPlayCardId(null);
    setSelectedHandCardId(null);
  }, []);

  const playerNumber = useMemo(() => {
    if (!initialGame || !username) return null;

    return username === initialGame.player1.player.name ? "1" : "2";
  }, [initialGame, username]);

  const mercureUrl =
    mercureUrlOverride ??
    (playerNumber
      ? `${process.env.NEXT_PUBLIC_MERCURE_URL}?topic=game/${gameId}&topic=game/${gameId}-${playerNumber}`
      : `${process.env.NEXT_PUBLIC_MERCURE_URL}?topic=game/${gameId}`);

  useMercure(mercureUrl, {
    game_events: (e: { events: GameEvent[] }) => {
      if (isAnimating) {
        setQueuedEvents((current) => [...current, ...e.events]);
        return;
      }

      const attackEvent = e.events.find(
        (ev) => ev.type === GameEventType.ATTACKED,
      );
      if (attackEvent) {
        const attackerId = attackEvent.data.attackerId;
        const targetId = attackEvent.data.targetId;
        const attackerCard = getCardById(attackerId);
        const cardSet: CardSet = attackerCard?.serie || CardSet.ORIGINAL;

        const opponentPlayerKey =
          gameRef.current?.player1.player.name === username
            ? "player2"
            : "player1";
        const opponent = gameRef.current?.[opponentPlayerKey];
        if (
          opponent &&
          (opponent.playArea.monsterCards.includes(attackerId) ||
            opponent.characterCardId === attackerId)
        ) {
          setIsAnimating(true);
          setQueuedEvents(e.events);
          emitter.emit("attack-animation:start", {
            attackerId,
            targetId,
            cardSet,
          });
          return;
        }
      }

      processEvents(e.events);
    },
    chat_message: (e: { message: ChatMessage }) => {
      setChatMessages((current) => [...current, e.message]);
    },
  });

  useEffect(() => {
    const fetchGameData = async () => {
      try {
        const data = await api.game.getGameData();

        if (!isGameData(data)) {
          console.error("Format de game data invalide", data);
          return;
        }

        setGameData(data);
      } catch (error) {
        console.error("Erreur lors de la récupération des données du jeu :", error);
      }
    };

    fetchGameData();
  }, []);

  useEffect(() => {
    gameRef.current = normalizeGameState(game);
  }, [game, normalizeGameState]);

  return (
    <GameContext.Provider
      value={{
        game,
        gameData,
        getCardById,
        announcements,
        chatMessages,
        actions: {
          playCard,
          attack,
          endTurn,
          pushAnnouncement,
          sendChatMessage,
        },
        currentUsername: username,
        isLoggedPlayerTurn,
        targeting: {
          selectedAttackerId,
          hoveredTargetId,
          pendingPlayCardId,
          selectedHandCardId,
          isTargeting,
        },
        targetingActions: {
          selectAttacker,
          clearSelectedAttacker,
          hoverTarget,
          requestCardTarget,
          handleTargetClick,
          cancelPendingCardTarget,
          clearAllTargeting,
          selectHandCard,
        },
      }}
    >
      {children}
    </GameContext.Provider>
  );
};
