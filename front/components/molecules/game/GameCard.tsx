"use client";

import { CSSProperties, memo, useContext } from "react";
import { CardSize, CardTargetType, CardType } from "@/constants/card";
import type { BasicCard } from "@/lib/cards/types/card";
import CardWithZoom from "@/components/organisms/card/CardWithZoom";
import CardEffectBadges from "@/components/molecules/game/CardEffectBadges";
import { GameContext } from "@/contexts/GameContext";

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
  const isValidCardTarget = !!(
    pendingCard &&
    targetId !== pendingPlayCardId &&
    (pendingCard.targetType === CardTargetType.MONSTER_AND_PASSIVE
      ? card.type === CardType.MONSTER || card.type === CardType.PASSIVE
      : card.type === CardType.MONSTER)
  );

  const isPulseTarget = isValidAttackTarget || isValidCardTarget;

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
      className={`relative card-selected ${canSelectSource || isTargeting ? "cursor-pointer" : ""} ${isPulseTarget ? "target-pulse" : ""} ${className ?? ""}`}
      style={{ ...(animatedStyle ?? {}), ...(style ?? {}) }}
    >
      {card.effects.length > 0 && (
        <CardEffectBadges
          effects={card.effects}
          className="absolute -top-2 left-1 z-30 pointer-events-none"
        />
      )}
      <CardWithZoom card={card} size={size} />
    </div>
  );
}

export default memo(GameCard);
