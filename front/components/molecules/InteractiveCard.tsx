"use client";

import React, { useState, useRef, useCallback, useEffect } from "react";
import Card from "./Card";
import CardEffectBadges from "@/components/molecules/game/CardEffectBadges";
import { BasicCard } from "@/lib/cards/types/card";
import {
  clamp,
  DEFAULT_TILT,
  DEFAULT_GLARE,
  NORMALIZED_CENTER,
  HALF_ROTATION,
  FLIP_DEG,
  NORMAL_ANIMATION_DURATION_MS,
  SNAPBACK_ANIMATION_DURATION_MS,
  SNAPBACK_DELAY_MS,
  calculateTiltOnHover,
  calculateGlareOnHover,
} from "@/lib/cards/cardUtils";
import { CardSize } from "@/constants/card";

export type InteractiveCardProps = {
  card: BasicCard;
  size?: CardSize;
  onHover?: (cardId: string) => void;
  onClick?: (cardId: string) => void;
  showLoadingUntilReady?: boolean;
  disableFlip?: boolean;
};

export default function InteractiveCard({
  card,
  size = CardSize.MD,
  onHover,
  onClick,
  showLoadingUntilReady = false,
  disableFlip = false,
}: InteractiveCardProps) {
  const [isHovering, setIsHovering] = useState(false);
  const [tilt, setTilt] = useState(DEFAULT_TILT);
  const [glare, setGlare] = useState(DEFAULT_GLARE);
  const [style, setStyle] = useState<React.CSSProperties>({});

  const rootRef = useRef<HTMLDivElement | null>(null);
  const tiltBackTimeoutRef = useRef<number | null>(null);
  const restoreTransitionTimeoutRef = useRef<number | null>(null);
  const lastPointerTypeRef = useRef<string | null>(null);

  const clearTimeouts = () => {
    if (tiltBackTimeoutRef.current) {
      clearTimeout(tiltBackTimeoutRef.current);
      tiltBackTimeoutRef.current = null;
    }
    if (restoreTransitionTimeoutRef.current) {
      clearTimeout(restoreTransitionTimeoutRef.current);
      restoreTransitionTimeoutRef.current = null;
    }
  };

  useEffect(() => {
    return () => clearTimeouts();
  }, []);

  const handlePointerMove = useCallback(
    (e: React.PointerEvent) => {
      if (e.pointerType !== "mouse") return;

      const rootElement = rootRef.current;
      if (!rootElement) return;

      setIsHovering((prev) => (prev ? prev : true));

      setStyle({
        transition: `transform ${NORMAL_ANIMATION_DURATION_MS}ms cubic-bezier(.2,.9,.2,1)`,
      });

      clearTimeouts();

      const bounds = rootElement.getBoundingClientRect();
      const x = clamp((e.clientX - bounds.left) / bounds.width);
      const y = clamp((e.clientY - bounds.top) / bounds.height);

      const newTilt = calculateTiltOnHover(x, y, tilt.y);
      const newGlare = calculateGlareOnHover(x, y, tilt.y);

      setTilt(newTilt);
      setGlare(newGlare);

      onHover?.(card.instanceId);
    },
    [onHover, card.instanceId, tilt.y],
  );

  const handlePointerLeave = useCallback((e: React.PointerEvent) => {
    if (e.pointerType !== "mouse") return;

    setIsHovering(false);

    clearTimeouts();

    tiltBackTimeoutRef.current = window.setTimeout(() => {
      setStyle({
        transition: `transform ${SNAPBACK_ANIMATION_DURATION_MS}ms cubic-bezier(.2,.9,.2,1)`,
      });

      const newTilt = calculateTiltOnHover(
        NORMALIZED_CENTER,
        NORMALIZED_CENTER,
        tilt.y,
      );
      const newGlare = calculateGlareOnHover(
        NORMALIZED_CENTER,
        NORMALIZED_CENTER,
        tilt.y,
      );

      setTilt(newTilt);
      setGlare(newGlare);

      restoreTransitionTimeoutRef.current = window.setTimeout(() => {
        setStyle({
          transition: `transform ${NORMAL_ANIMATION_DURATION_MS}ms cubic-bezier(.2,.9,.2,1)`,
        });
      }, SNAPBACK_ANIMATION_DURATION_MS);
    }, SNAPBACK_DELAY_MS);
  }, [tilt.y]);

  const handleClick = useCallback(() => {
    if (disableFlip) {
      onClick?.(card.instanceId);
      return;
    }

    if (
      lastPointerTypeRef.current === "touch" ||
      lastPointerTypeRef.current === "pen"
    ) {
      onClick?.(card.instanceId);
      return;
    }

    const newRotationY =
      tilt.y > HALF_ROTATION ? tilt.y - FLIP_DEG : tilt.y + FLIP_DEG;

    setGlare((prev) => ({ x: 100 - prev.x, y: prev.y }));
    setTilt((prev) => ({ ...prev, y: newRotationY }));

    onClick?.(card.instanceId);
  }, [disableFlip, onClick, card.instanceId, tilt.y]);

  return (
    <div
      ref={rootRef}
      onClick={handleClick}
      onPointerDown={(e) => {
        lastPointerTypeRef.current = e.pointerType;
      }}
      onPointerMove={handlePointerMove}
      onPointerLeave={handlePointerLeave}
      className="relative cursor-pointer"
    >
      {card.effects.length > 0 && (
        <CardEffectBadges
          effects={card.effects}
          size="lg"
          className="absolute -top-3 left-2 z-30 pointer-events-none"
        />
      )}
      <Card
        card={card}
        size={size}
        tilt={tilt}
        glare={glare}
        isHovering={isHovering}
        style={style}
        showLoadingUntilReady={showLoadingUntilReady}
      />
    </div>
  );
}
