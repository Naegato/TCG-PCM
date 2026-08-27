"use client";

import { useContext, useEffect, useRef, useState } from "react";
import { CardSize } from "@/constants/card";
import Card from "../Card";
import CardWithZoom from "@/components/organisms/card/CardWithZoom";
import PileTooltip from "@/components/atoms/PileTooltip";
import { GameContext } from "@/contexts/GameContext";
import { emitter } from "@/lib/eventBus";
import {
  GAMEBOARD_ANIMATION_DURATION,
  GAMEBOARD_ANIMATION_TIMING,
} from "@/constants/gameArea";

type CemeteryProps = {
  cardIds: string[];
  className?: string;
  mirrored?: boolean;
};

const CARD_PLAY_ANIMATION_TIME = 500;
const TOOLTIP_TAP_DURATION_MS = 1800;

export default function Cemetery({
  cardIds,
  className = "",
  mirrored = false,
}: CemeteryProps) {
  const { getCardById } = useContext(GameContext);
  const [isHovered, setIsHovered] = useState(false);
  const [isTooltipPinned, setIsTooltipPinned] = useState(false);
  const [playingCardIds, setPlayingCardIds] = useState<Set<string>>(new Set());
  const tooltipTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const handleCardAnimated = (event: { card: { instanceId: string } }) => {
      setPlayingCardIds((prev) => new Set(prev).add(event.card.instanceId));

      setTimeout(() => {
        setPlayingCardIds((prev) => {
          const next = new Set(prev);
          next.delete(event.card.instanceId);
          return next;
        });
      }, CARD_PLAY_ANIMATION_TIME);
    };

    emitter.on("card:played", handleCardAnimated);
    emitter.on("card:discarded", handleCardAnimated);

    return () => {
      emitter.off("card:played", handleCardAnimated);
      emitter.off("card:discarded", handleCardAnimated);
    };
  }, []);

  useEffect(() => {
    return () => {
      if (tooltipTimerRef.current) {
        clearTimeout(tooltipTimerRef.current);
      }
    };
  }, []);

  const handleTapTooltip = () => {
    setIsTooltipPinned(true);

    if (tooltipTimerRef.current) {
      clearTimeout(tooltipTimerRef.current);
    }

    tooltipTimerRef.current = setTimeout(() => {
      setIsTooltipPinned(false);
    }, TOOLTIP_TAP_DURATION_MS);
  };

  const showTooltip = isHovered || isTooltipPinned;

  return (
    <div
      className={`relative w-card-md aspect-card ${className}`}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      onClick={handleTapTooltip}
    >
      {cardIds.map((cardId, i) => {
        const card = getCardById(cardId);
        if (!card) return null;

        const isPlaying = playingCardIds.has(card.instanceId);
        const playOffset = mirrored ? "-200px" : "200px";
        const isTopCard = i === cardIds.length - 1;

        return (
          <div
            key={cardId}
            className="absolute"
            style={{
              transform: isPlaying
                ? `translateZ(80px)  translateY(${playOffset})`
                : `translateZ(0px) translateX(0px)`,
              zIndex: i,
              transition: `transform ${GAMEBOARD_ANIMATION_DURATION}ms ${GAMEBOARD_ANIMATION_TIMING}`,
            }}
          >
            {isTopCard ? (
              <CardWithZoom card={card} size={CardSize.MD} />
            ) : (
              <Card card={card} size={CardSize.MD} />
            )}
          </div>
        );
      })}
      <PileTooltip
        isVisible={showTooltip}
        count={cardIds.length}
        isMirrored={mirrored}
        label="cartes"
      />
    </div>
  );
}
