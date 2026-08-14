"use client";

import { CSSProperties, memo, useContext, useEffect, useRef, useState } from "react";
import { CardSize, CardTargetFlag, CardType } from "@/constants/card";
import type { BasicCard } from "@/lib/cards/types/card";
import CardWithZoom from "@/components/organisms/card/CardWithZoom";
import CardEffectBadges from "@/components/molecules/game/CardEffectBadges";
import { GameContext } from "@/contexts/GameContext";
import { emitter } from "@/lib/eventBus";
import {
  CARD_TRIGGERED_SHAKE_DURATION,
  CARD_MARKER_DURATION,
} from "@/lib/game/animationTimings";

type GameCardProps = {
  card: BasicCard;
  targetId: string;
  size?: CardSize;
  canSelectSource?: boolean;
  disableSelfTarget?: boolean;
  className?: string;
  style?: CSSProperties;
  isPlaying?: boolean;
  isOpponentSideForPlayAnimation?: boolean;
  rowCardCount?: number;
};

const CARD_TYPE_TARGET_FLAG: Partial<Record<CardType, number>> = {
  [CardType.MONSTER]: CardTargetFlag.MONSTER,
  [CardType.PASSIVE]: CardTargetFlag.PASSIVE,
  [CardType.CHARACTER]: CardTargetFlag.CHARACTER,
};

function getAnimatedCardStyle(
  isPlaying: boolean,
  isSelected: boolean,
  isActive: boolean,
  isOpponentSide: boolean,
  rowCardCount: number,
): CSSProperties {
  if (isPlaying) {
    const playOffset = isOpponentSide ? "-200px" : "200px";
    return {
      transform: `scale(1.1) translateZ(80px) translateY(${playOffset})`,
      position: "relative",
      zIndex: 50,
      boxShadow:
        "0 50px 40px rgba(0, 0, 0, 0.5), 0 10px 20px rgba(0, 0, 0, 0.3)",
      transition: "transform 300ms ease-in",
    };
  }

  if (isSelected) {
    return {
      transform: "scale(1.1) translateZ(80px) translateY(-40px)",
      zIndex: rowCardCount + 1,
      boxShadow:
        "0 50px 40px rgba(0, 0, 0, 0.5), 0 10px 20px rgba(0, 0, 0, 0.3)",
    };
  }

  return {
    transform: `scale(1) translateZ(0) translateY(0)${!isActive ? " rotateZ(90deg)" : ""}`,
  };
}

function GameCard({
  card,
  targetId,
  size = CardSize.MD,
  canSelectSource = false,
  disableSelfTarget = false,
  className,
  style,
  isPlaying = false,
  isOpponentSideForPlayAnimation = false,
  rowCardCount = 0,
}: GameCardProps) {
  const { targeting, targetingActions, game, currentUsername, getCardById } =
    useContext(GameContext);
  const { isTargeting, hoveredTargetId, selectedAttackerId, pendingPlayCardId } =
    targeting;

  const isSelectedSource = selectedAttackerId === card.instanceId;
  const isHovered =
    hoveredTargetId === targetId && isTargeting && !isSelectedSource;

  const [isTriggered, setIsTriggered] = useState(false);
  const triggeredTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const handleCardTriggered = (event: { cardId: string }) => {
      if (event.cardId !== card.instanceId) {
        return;
      }

      if (triggeredTimeoutRef.current) {
        clearTimeout(triggeredTimeoutRef.current);
      }

      setIsTriggered(true);
      triggeredTimeoutRef.current = setTimeout(() => {
        setIsTriggered(false);
        triggeredTimeoutRef.current = null;
      }, CARD_TRIGGERED_SHAKE_DURATION);
    };

    emitter.on("card:triggered", handleCardTriggered);

    return () => {
      emitter.off("card:triggered", handleCardTriggered);
      if (triggeredTimeoutRef.current) {
        clearTimeout(triggeredTimeoutRef.current);
      }
    };
  }, [card.instanceId]);

  const [marker, setMarker] = useState<{ text: string; tone: "positive" | "negative" } | null>(
    null,
  );
  const markerTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const handleCardMarker = (event: {
      cardId: string;
      text: string;
      tone: "positive" | "negative";
    }) => {
      if (event.cardId !== card.instanceId) {
        return;
      }

      if (markerTimeoutRef.current) {
        clearTimeout(markerTimeoutRef.current);
      }

      setMarker({ text: event.text, tone: event.tone });
      markerTimeoutRef.current = setTimeout(() => {
        setMarker(null);
        markerTimeoutRef.current = null;
      }, CARD_MARKER_DURATION);
    };

    emitter.on("card:marker", handleCardMarker);

    return () => {
      emitter.off("card:marker", handleCardMarker);
      if (markerTimeoutRef.current) {
        clearTimeout(markerTimeoutRef.current);
      }
    };
  }, [card.instanceId]);

  const loggedPlayerState =
    game && currentUsername
      ? game.player1.player.name === currentUsername
        ? game.player1
        : game.player2
      : null;
  const isOwnSide = !!(
    loggedPlayerState &&
    (targetId === loggedPlayerState.characterCardId ||
      targetId === loggedPlayerState.player.id ||
      loggedPlayerState.playArea.monsterCards.includes(targetId) ||
      loggedPlayerState.playArea.passiveCards.includes(targetId))
  );

  const isValidAttackTarget =
    !!selectedAttackerId &&
    !isSelectedSource &&
    card.type !== CardType.PASSIVE &&
    !isOwnSide;

  const pendingCard = pendingPlayCardId
    ? getCardById(pendingPlayCardId)
    : undefined;
  const pendingCardTargetType = pendingCard?.targetType ?? CardTargetFlag.NONE;
  const cardEntityFlag = card.type ? (CARD_TYPE_TARGET_FLAG[card.type] ?? 0) : 0;
  const cardOwnershipFlag = isOwnSide
    ? CardTargetFlag.SELF_CARDS
    : CardTargetFlag.OPPONENT_CARDS;
  const isValidCardTarget = !!(
    pendingCard &&
    targetId !== pendingPlayCardId &&
    (pendingCardTargetType & cardEntityFlag) !== 0 &&
    (pendingCardTargetType & cardOwnershipFlag) !== 0
  );

  const isPulseTarget = isValidAttackTarget || isValidCardTarget;

  const isBlockedSelfTarget = disableSelfTarget && selectedAttackerId === targetId;
  const isNonClickableWhileTargeting =
    isTargeting && !isSelectedSource && (isBlockedSelfTarget || !isPulseTarget);

  const animatedStyle = getAnimatedCardStyle(
    isPlaying,
    isSelectedSource,
    card.isActive ?? true,
    isOpponentSideForPlayAnimation,
    rowCardCount,
  );

  return (
    <div
      data-card-id={card.instanceId}
      onClick={(e) => {
        e.stopPropagation();

        if (isTargeting) {
          if (disableSelfTarget && selectedAttackerId === targetId) {
            return;
          }

          targetingActions.handleTargetClick(targetId);
          return;
        }

        if (canSelectSource) {
          targetingActions.selectAttacker(
            isSelectedSource ? null : card.instanceId,
          );
        }
      }}
      onMouseEnter={() => {
        if (isTargeting) {
          targetingActions.hoverTarget(targetId);
        }
      }}
      onMouseLeave={() => targetingActions.hoverTarget(null)}
      className={`relative card-selected ${(canSelectSource || isTargeting) && !isNonClickableWhileTargeting ? "cursor-pointer" : ""} ${isPulseTarget ? "target-pulse" : ""} ${isNonClickableWhileTargeting ? "opacity-40 grayscale pointer-events-none" : ""} ${className ?? ""}`}
      style={{ ...(animatedStyle ?? {}), ...(style ?? {}) }}
    >
      {card.effects.length > 0 && (
        <CardEffectBadges
          effects={card.effects}
          className="absolute -top-2 left-1 z-30 pointer-events-none"
        />
      )}
      {marker && (
        <span
          className={`card-marker ${marker.tone === "positive" ? "card-marker-positive" : "card-marker-negative"}`}
        >
          {marker.text}
        </span>
      )}
      <div className={isTriggered ? "card-triggered-shake" : undefined}>
        <CardWithZoom card={card} size={size} />
      </div>
    </div>
  );
}

export default memo(GameCard);
