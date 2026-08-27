"use client";

import React from "react";
import { createPortal } from "react-dom";
import Card from "./Card";
import { BasicCard } from "@/lib/cards/types/card";
import { CardSize } from "@/constants/card";
import { resolveDropZone } from "@/lib/dropZones/dropzoneResolver";
import { emitter } from "@/lib/eventBus";
import { useEffect, useState } from "react";

type DraggedCardProps = {
  card: BasicCard;
  pointerPos: { x: number; y: number } | null;
  tilt: { x: number; y: number; z: number };
  originPos: { x: number; y: number; z: number } | null;
  originSize: CardSize;
  originTilt: { x: number; y: number; z: number };
  isDropped: boolean;
};

export default function DraggedCard({
  card,
  originPos,
  originSize,
  originTilt,
  pointerPos,
  tilt,
  isDropped,
}: DraggedCardProps) {
  const [isMobileDevice, setIsMobileDevice] = useState(false);

  useEffect(() => {
    const mediaQuery = window.matchMedia(
      "(max-width: 1024px), (pointer: coarse)",
    );

    const updateDeviceType = () => {
      setIsMobileDevice(mediaQuery.matches);
    };

    updateDeviceType();
    mediaQuery.addEventListener("change", updateDeviceType);

    return () => {
      mediaQuery.removeEventListener("change", updateDeviceType);
    };
  }, []);

  if (typeof document === "undefined") return null;
  if (!pointerPos && !isDropped) return null;

  const getMobileDragSize = (size: CardSize): CardSize => {
    switch (size) {
      case CardSize.XLL:
        return CardSize.XL;
      case CardSize.XL:
        return CardSize.LG;
      case CardSize.LG:
        return CardSize.MD;
      case CardSize.MD:
        return CardSize.SM;
      case CardSize.SM:
      default:
        return CardSize.SM;
    }
  };

  let x = 0;
  let y = 0;
  let z = originPos?.z ?? 50;
  let currentSize: CardSize = CardSize.MD;
  let currentTilt = { ...tilt, z: 0 };
  let shouldTransition = false;

  if (pointerPos && !isDropped) {
    x = pointerPos.x - window.innerWidth / 2;
    y = pointerPos.y - window.innerHeight / 2;
  } else {
    if (!pointerPos) return null;

    const dropResult = resolveDropZone(pointerPos, card);
    if (dropResult) {
      return null;
    }

    emitter.emit("card:return-hand", { pos: pointerPos, card });

    if (!originPos) return null;

    x = originPos.x - window.innerWidth / 2;
    y = originPos.y - window.innerHeight / 2;
    z = originPos.z;
    currentSize = originSize;
    currentTilt = originTilt;
    shouldTransition = true;
  }

  const style: React.CSSProperties = {
    position: "fixed",
    top: "50%",
    left: "50%",
    transform: `translate(calc(-50% + ${x}px), calc(-50% + ${y}px))`,
    zIndex: z,
    cursor: "grabbing",
    transition: shouldTransition
      ? "transform 250ms cubic-bezier(.2,.8,.2,1)"
      : undefined,
  };

  const portal = (
    <div style={style}>
      <Card
        card={card}
        size={isMobileDevice ? getMobileDragSize(currentSize) : currentSize}
        tilt={currentTilt}
        showLoadingUntilReady
      />
    </div>
  );

  return createPortal(portal, document.body);
}
