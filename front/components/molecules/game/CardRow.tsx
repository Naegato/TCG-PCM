"use client";

import { memo, useContext, useEffect, useRef, useState } from "react";
import { GameContext } from "@/contexts/GameContext";
import { emitter } from "@/lib/eventBus";
import { CardSize, CardType } from "@/constants/card";
import GameCard from "./GameCard";

type CardRowProps = {
  cardIds: string[];
  className?: string;
  isLoggedPlayerSide?: boolean;
};

const CARD_PLAY_ANIMATION_TIME = 500;

function CardRow({
  cardIds,
  className,
  isLoggedPlayerSide = false,
}: CardRowProps) {
  const { getCardById, targeting } = useContext(GameContext);
  const { isTargeting } = targeting;
  const [playingCardIds, setPlayingCardIds] = useState<Set<string>>(new Set());
  const [pendingCardIds, setPendingCardIds] = useState<Set<string>>(new Set());
  const timersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(
    new Map(),
  );

  useEffect(() => {
    const handleCardPlayed = (event: { card: { instanceId: string } }) => {
      const playedId = event.card.instanceId;

      setPendingCardIds((prev) => new Set(prev).add(playedId));
    };

    emitter.on("card:played", handleCardPlayed);
    emitter.on("card:stolen", handleCardPlayed);

    const timers = timersRef.current;
    return () => {
      emitter.off("card:played", handleCardPlayed);
      for (const timeoutId of timers.values()) {
        clearTimeout(timeoutId);
      }
      timers.clear();
    };
  }, []);

  useEffect(() => {
    const cardsReadyToAnimate = [...pendingCardIds].filter((cardId) =>
      cardIds.includes(cardId),
    );

    if (cardsReadyToAnimate.length === 0) {
      return;
    }

    const frameId = window.requestAnimationFrame(() => {
      setPendingCardIds((prev) => {
        const next = new Set(prev);
        cardsReadyToAnimate.forEach((cardId) => next.delete(cardId));
        return next;
      });
      setPlayingCardIds((prev) => {
        const next = new Set(prev);
        cardsReadyToAnimate.forEach((cardId) => next.add(cardId));
        return next;
      });

      cardsReadyToAnimate.forEach((cardId) => {
        const existingTimer = timersRef.current.get(cardId);
        if (existingTimer) {
          clearTimeout(existingTimer);
        }

        const timeoutId = setTimeout(() => {
          setPlayingCardIds((prev) => {
            const next = new Set(prev);
            next.delete(cardId);
            return next;
          });
          timersRef.current.delete(cardId);
        }, CARD_PLAY_ANIMATION_TIME);

        timersRef.current.set(cardId, timeoutId);
      });
    });

    return () => window.cancelAnimationFrame(frameId);
  }, [cardIds, pendingCardIds]);

  const cardIdsLength = cardIds.length;

  const cardSize =
    cardIdsLength > 8
      ? CardSize.XS
      : cardIdsLength > 6
        ? CardSize.SM
        : CardSize.MD;

  return (
    <div className={`flex flex-wrap justify-center gap-2 ${className}`}>
      {cardIds.map((cardId) => {
        const card = getCardById(cardId);
        const canSelect =
          isLoggedPlayerSide &&
          card?.isActive &&
          !isTargeting &&
          card?.type === CardType.MONSTER;
        const isPlaying = playingCardIds.has(card?.instanceId ?? "");

        return (
          card && (
            <GameCard
              key={card.instanceId}
              card={card}
              targetId={card.instanceId}
              canSelectSource={canSelect}
              disableSelfTarget
              isPlaying={isPlaying}
              isOpponentSideForPlayAnimation={!isLoggedPlayerSide}
              rowCardCount={cardIdsLength}
              size={cardSize}
            />
          )
        );
      })}
    </div>
  );
}

export default memo(CardRow);
