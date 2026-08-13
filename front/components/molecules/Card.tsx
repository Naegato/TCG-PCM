"use client";

import React from "react";
import CardFront from "../atoms/CardFront";
import CardBack from "../atoms/CardBack";
import {
  CardRaririty,
  CardSet,
  CardType,
  CardSize,
  CardSizeMap,
  CardEffect,
  FoilEffects,
} from "@/constants/card";
import { BasicCard, CardLayer } from "@/lib/cards/types/card";
import { DEFAULT_TILT, DEFAULT_GLARE } from "@/lib/cards/cardUtils";
import { getImage } from "@/lib/api/api";
import LoadingSpinner from "@/components/atoms/LoadingSpinner";
import { useMemo, useState } from "react";

export type CardViewProps = {
  card: BasicCard;
  size?: CardSize;
  tilt?: { x: number; y: number; z: number };
  glare?: { x: number; y: number };
  isHovering?: boolean;
  style?: React.CSSProperties;
  className?: string;
  showLoadingUntilReady?: boolean;
};

const Card = ({
  card,
  size = CardSize.MD,
  tilt,
  glare,
  isHovering,
  style,
  className,
  showLoadingUntilReady = false,
}: CardViewProps) => {
  const [isCardReady, setIsCardReady] = useState(!showLoadingUntilReady);
  const readinessKey = `${card.instanceId}:${showLoadingUntilReady}`;
  const [prevReadinessKey, setPrevReadinessKey] = useState(readinessKey);

  // Resets readiness so the loading state re-applies when a different card is
  // shown, computed during render (see "Adjusting state in render" in the React docs).
  if (readinessKey !== prevReadinessKey) {
    setPrevReadinessKey(readinessKey);
    setIsCardReady(!showLoadingUntilReady);
  }

  const cardSizeInfo = CardSizeMap[size];
  const appliedTilt = tilt ?? DEFAULT_TILT;
  const appliedGlare = glare ?? DEFAULT_GLARE;
  const turnRemainingBeforeAction = useMemo(() => {
    if (!card.values || typeof card.values !== "object" || Array.isArray(card.values)) {
      return null;
    }

    const rawValue = card.values.turnRemainingBeforeAction;

    if (typeof rawValue === "number" && Number.isFinite(rawValue)) {
      return rawValue;
    }

    if (typeof rawValue === "string") {
      const numericValue = Number(rawValue);

      if (Number.isFinite(numericValue)) {
        return numericValue;
      }
    }

    return null;
  }, [card.values]);

  const cardLayers: CardLayer[] = useMemo(() => {
    const foilEffect =
      card.rarity === CardRaririty.LEGENDARY
        ? FoilEffects.RAINBOW
        : card.rarity === CardRaririty.EPIC
          ? FoilEffects.GOLDEN
          : card.rarity === CardRaririty.RARE
            ? FoilEffects.HOLO
            : null;

    const setFolderName = (cardSet: CardSet): string => {
      switch (cardSet) {
        case CardSet.BTD6:
          return "btd";
        case CardSet.TBOI:
          return "isaac";
        case CardSet.ORIGINAL:
        default:
          return "original";
      }
    };

    const buildFrontSrcs = (cardSet: CardSet) => {
      const folder = setFolderName(cardSet);
      return {
        main: `/card/${folder}/card_front_${folder}_main.png`,
        header: `/card/${folder}/card_front_${folder}_header.png`,
        outline: `/card/${folder}/card_front_${folder}_outline.png`,
        fullStats: `/card/${folder}/card_front_${folder}_full_stats.png`,
        smallStats: `/card/${folder}/card_front_${folder}_small_stats.png`,
      };
    };

    const frontSrcs = buildFrontSrcs(card.serie);

    const getStatsSrc = (type: CardType | undefined) => {
      switch (type) {
        case CardType.MONSTER:
          return frontSrcs.fullStats;
        case CardType.PASSIVE:
        case CardType.CONSUMABLE:
          return frontSrcs.smallStats;
        case CardType.CHARACTER:
        default:
          return null;
      }
    };

    const statusLayerSources: Partial<Record<CardEffect, string>> = {
      [CardEffect.HACKED]: "/card/status/hacked.webp",
      [CardEffect.TORNED]: "/card/status/thorn.webp",
      [CardEffect.POWER_BOOST]: "/card/status/powerBoost.webp",
    };

    const cardEffects = card.effects ?? [];

    const statusLayers: CardLayer[] = cardEffects.flatMap((effect) => {
      const src = statusLayerSources[effect.effect];

      if (!src) {
        return [];
      }

      return [
        {
          src,
          depth: -1,
          isIllustration: true,
          foilEffect: null,
          foil: null,
          mask: null,
        },
      ];
    });

    const layers: CardLayer[] = [
      {
        src: card?.image && getImage(card.image),
        depth: -5,
        isIllustration: true,
        foilEffect: null,
        foil: null,
        mask: null,
      },
      {
        src: frontSrcs.main,
        depth: 0,
        foilEffect: foilEffect,
        foil: foilEffect && "/card/card_foil.png",
        mask: foilEffect && "/card/card_mask.png",
      },
      {
        src: frontSrcs.outline,
        depth: 1,
        foilEffect: null,
        foil: null,
        mask: null,
      },
      {
        src: frontSrcs.header,
        depth: 1,
        foilEffect: null,
        foil: null,
        mask: null,
      },
      {
        src: "/card/card_bg_default.png",
        depth: -10,
        foilEffect: null,
        foil: null,
        mask: null,
      },
    ];

    const statsSrc = getStatsSrc(card.type);
    if (statsSrc) {
      layers.push({
        src: statsSrc,
        depth: 1,
        foilEffect: null,
        foil: null,
        mask: null,
      });
    }

    layers.push(...statusLayers);

    return layers;
  }, [card]);

  return (
    <div
      id={card?.instanceId}
      className={`relative rounded-sm aspect-card ${cardSizeInfo} transform-3d transform-gpu will-change-transform user-select-none ${className ?? ""}`}
      style={
        {
          transform: `perspective(1000px) rotateX(${appliedTilt.x}deg) rotateY(${appliedTilt.y}deg) rotateZ(${appliedTilt.z}deg)`,
          ...(style ?? {}),
        } as React.CSSProperties
      }
    >
      <CardFront
        layers={cardLayers}
        tilt={appliedTilt}
        glare={appliedGlare}
        isHovering={!!isHovering}
        readinessKey={card.instanceId}
        cardTitle={card.name}
        cardDescription={card.description}
        cardType={card.type ?? CardType.CONSUMABLE}
        cardRarity={card.rarity}
        cardStats={{ hp: card.hp, attack: card.attack, cost: card.cost }}
        turnRemainingBeforeAction={turnRemainingBeforeAction}
        requiresTarget={card.requiresTarget}
        onReadyStateChange={
          showLoadingUntilReady
            ? (isReady) => setIsCardReady(isReady)
            : undefined
        }
      />
      <CardBack />
      {showLoadingUntilReady && !isCardReady ? (
        <div className="absolute inset-0 z-50 flex items-center justify-center rounded-sm bg-black/35">
          <LoadingSpinner className="h-6 w-6" />
        </div>
      ) : null}
    </div>
  );
};

export default React.memo(Card);
