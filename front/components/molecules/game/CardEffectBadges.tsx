"use client";

import { useContext } from "react";
import { GameContext } from "@/contexts/GameContext";
import { CardEffectState } from "@/lib/cards/types/card";
import CardEffectBadge from "@/components/atoms/game/CardEffectBadge";

type CardEffectBadgesProps = {
  effects: CardEffectState[];
  size?: "sm" | "lg";
  className?: string;
};

export default function CardEffectBadges({
  effects,
  size = "sm",
  className,
}: CardEffectBadgesProps) {
  const { gameData } = useContext(GameContext);

  if (!effects.length) {
    return null;
  }

  return (
    <div className={`flex flex-wrap gap-1 ${className ?? ""}`}>
      {effects.map((effectState, index) => {
        const effectData = gameData?.cardEffects?.[effectState.effect];

        return (
          <CardEffectBadge
            key={`${effectState.effect}-${index}`}
            effect={effectState.effect}
            label={effectData?.name ?? effectState.effect}
            description={effectData?.description}
            size={size}
          />
        );
      })}
    </div>
  );
}
