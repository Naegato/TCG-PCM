import Image from "./Image";
import HoloFoil from "./HoloFoil";
import RainbowFoil from "./RainbowFoil";
import GoldenFoil from "./GoldenFoil";
import CardTextOverlay from "./CardTextOverlay";
import CardGlare from "./CardGlare";
import { Fragment } from "react/jsx-runtime";
import { useEffect, useMemo, useState } from "react";
import { CardLayer } from "@/lib/cards/types/card";
import { CardRaririty, CardType } from "@/constants/card";

const ILLUSTRATION_ZONE_STYLE = {
  top: "9%",
  left: "3%",
  width: "95%",
  height: "45%",
} as const;

export type CardFrontProps = {
  layers: CardLayer[];
  tilt: { x: number; y: number };
  glare: { x: number; y: number };
  isHovering: boolean;
  readinessKey: string;
  cardTitle: string;
  cardDescription: string;
  cardType: CardType;
  cardRarity: CardRaririty;
  cardStats: { hp?: number; attack?: number; cost?: number };
  turnRemainingBeforeAction?: number | null;
  requiresTarget?: boolean;
  onReadyStateChange?: (isReady: boolean) => void;
};

const CardFront = ({
  layers,
  tilt,
  glare,
  isHovering,
  readinessKey,
  cardTitle,
  cardDescription,
  cardType,
  cardRarity,
  cardStats,
  turnRemainingBeforeAction,
  requiresTarget,
  onReadyStateChange,
}: CardFrontProps) => {
  const [isTextReady, setIsTextReady] = useState(false);
  const [isImagesReady, setIsImagesReady] = useState(false);
  const [prevReadinessKey, setPrevReadinessKey] = useState(readinessKey);
  const sortedLayers = useMemo(
    () => [...layers].sort((a, b) => a.depth - b.depth),
    [layers],
  );
  const requiredLayerIndexes = useMemo(
    () =>
      sortedLayers
        .map((layer, index) => (layer.src ? index : null))
        .filter((index): index is number => index !== null),
    [sortedLayers],
  );
  const requiredLayerSrcs = useMemo(
    () =>
      requiredLayerIndexes
        .map((index) => sortedLayers[index]?.src)
        .filter(
          (src): src is string => typeof src === "string" && src.length > 0,
        ),
    [requiredLayerIndexes, sortedLayers],
  );
  const requiredLayersKey = useMemo(
    () => requiredLayerSrcs.map((src, index) => `${index}:${src}`).join("|"),
    [requiredLayerSrcs],
  );
  const hasNoRequiredImages = requiredLayerSrcs.length === 0;
  const [prevRequiredLayersKey, setPrevRequiredLayersKey] =
    useState(requiredLayersKey);

  // Resets image readiness so the probe effect below can recompute it for the new
  // layers, computed during render (see "Adjusting state in render" in the React docs).
  if (requiredLayersKey !== prevRequiredLayersKey) {
    setPrevRequiredLayersKey(requiredLayersKey);
    if (!hasNoRequiredImages) {
      setIsImagesReady(false);
    }
  }

  useEffect(() => {
    if (hasNoRequiredImages) {
      return;
    }

    let settledCount = 0;

    requiredLayerSrcs.forEach((src) => {
      const probe = new window.Image();

      const settle = () => {
        settledCount += 1;
        if (settledCount >= requiredLayerSrcs.length) {
          setIsImagesReady(true);
        }
      };

      probe.onload = settle;
      probe.onerror = settle;
      probe.src = src;

      if (probe.complete) {
        settle();
      }
    });
  }, [requiredLayersKey, hasNoRequiredImages]);

  // Resets readiness so the effects above can recompute it for the new content,
  // computed during render (see "Adjusting state in render" in the React docs).
  if (readinessKey !== prevReadinessKey) {
    setPrevReadinessKey(readinessKey);
    setIsTextReady(false);
  }

  useEffect(() => {
    if (!onReadyStateChange) {
      return;
    }

    onReadyStateChange(isTextReady && (isImagesReady || hasNoRequiredImages));
  }, [isImagesReady, isTextReady, hasNoRequiredImages, onReadyStateChange]);

  return (
    <div className="absolute inset-0 overflow-hidden backface-hidden select-none rounded-sm z-0">
      {sortedLayers.map((layer, i) => {
        const depthFactor = (layer.depth / 100) * 5;
        const isIllustrationLayer = !!layer.isIllustration;

        const { foilEffect, foil, mask } = layer;

        const foilComponentMap = {
          Holographic: HoloFoil,
          Rainbow: RainbowFoil,
          Golden: GoldenFoil,
        };
        const FoilComponent = foilEffect ? foilComponentMap[foilEffect] : null;

        return (
          <Fragment key={i}>
            {isIllustrationLayer ? (
              <div
                className="absolute overflow-hidden"
                style={ILLUSTRATION_ZONE_STYLE}
              >
                <Image
                  src={layer.src}
                  alt={layer.alt ?? `layer-${i}`}
                  fill
                  className={`object-cover ${!isHovering ? "transition-transform duration-300 ease-[cubic-bezier(.2,.9,.2,1)]" : ""} z-0`}
                  style={{
                    transform: `translateX(${tilt.y * depthFactor}px) translateY(${tilt.x * depthFactor}px)`,
                  }}
                />
              </div>
            ) : (
              <Image
                src={layer.src}
                alt={layer.alt ?? `layer-${i}`}
                fill
                className={`object-cover ${!isHovering ? "transition-transform duration-300 ease-[cubic-bezier(.2,.9,.2,1)]" : ""} z-0`}
                style={{
                  transform: `translateX(${tilt.y * depthFactor}px) translateY(${tilt.x * depthFactor}px)`,
                }}
              />
            )}
            {FoilComponent && foil && mask && (
              <FoilComponent tilt={tilt} foil={foil} mask={mask} />
            )}
          </Fragment>
        );
      })}
      {turnRemainingBeforeAction !== null &&
        turnRemainingBeforeAction !== undefined && (
          <div className="absolute top-[6%] right-[6%] z-20 rounded-full border border-amber-300/80 bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold leading-none text-amber-900 shadow-sm">
            {turnRemainingBeforeAction}
          </div>
        )}
      {requiresTarget && (
        <div
          title="Cette carte nécessite une cible"
          className="absolute top-[6%] left-[6%] z-20 flex items-center justify-center rounded-full border border-sky-300/80 bg-sky-100 w-5 h-5 text-[11px] leading-none text-sky-900 shadow-sm"
        >
          <span aria-hidden="true">🎯</span>
        </div>
      )}
      <CardGlare glare={glare} isHovering={isHovering} />
      <CardTextOverlay
        key={readinessKey}
        readinessKey={readinessKey}
        cardTitle={cardTitle}
        cardDescription={cardDescription}
        cardType={cardType}
        cardRarity={cardRarity}
        cardStats={cardStats}
        onLayoutReady={() => setIsTextReady(true)}
      />
    </div>
  );
};

export default CardFront;
