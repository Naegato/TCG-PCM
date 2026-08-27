import { useCallback, useEffect, useState, useRef } from "react";
import GameProjectile from "./GameProjectile";
import { emitter } from "@/lib/eventBus";
import { CardSet } from "@/constants/card";

export default function GameAttack() {
  const [attack, setAttack] = useState<{
    attackerId: string;
    targetId: string;
    cardSet: CardSet;
  } | null>(null);
  const [impactFlash, setImpactFlash] = useState(false);
  const [startPos, setStartPos] = useState<{ x: number; y: number } | null>(
    null,
  );
  const [endPos, setEndPos] = useState<{ x: number; y: number } | null>(null);
  const gameAreaRef = useRef<HTMLDivElement | null>(null);
  const flashTimeoutRef = useRef<number | null>(null);

  const clearFlashTimeout = useCallback(() => {
    if (flashTimeoutRef.current !== null) {
      window.clearTimeout(flashTimeoutRef.current);
      flashTimeoutRef.current = null;
    }
  }, []);

  useEffect(() => {
    gameAreaRef.current = document.querySelector<HTMLDivElement>(".game-board");
  }, [clearFlashTimeout]);

  useEffect(() => {
    return () => {
      clearFlashTimeout();
    };
  }, []);

  useEffect(() => {
    const handleAttack = (event: {
      attackerId: string;
      targetId: string;
      cardSet: CardSet;
    }) => {
      clearFlashTimeout();
      setImpactFlash(false);
      setAttack({
        attackerId: event.attackerId,
        targetId: event.targetId,
        cardSet: event.cardSet,
      });
    };

    emitter.on("attack-animation:start", handleAttack);

    return () => {
      emitter.off("attack-animation:start", handleAttack);
    };
  }, []);

  useEffect(() => {
    if (!attack || !gameAreaRef.current) {
      setStartPos(null);
      setEndPos(null);
      return;
    }

    const scale =
      parseFloat(
        getComputedStyle(gameAreaRef.current).getPropertyValue(
          "--game-board-scale",
        ),
      ) || 1;

    const gameAreaRect = gameAreaRef.current.getBoundingClientRect();

    const getCardElement = (id: string): Element | null => {
      let cardId = id;
      return document.querySelector(`[data-card-id="${cardId}"]`);
    };

    const attackerElement = getCardElement(attack.attackerId);
    const targetElement = getCardElement(attack.targetId);

    if (attackerElement && targetElement) {
      const attackerRect = attackerElement.getBoundingClientRect();
      const targetRect = targetElement.getBoundingClientRect();

      const projectileOffset = 128;

      const start = {
        x:
          (attackerRect.left - gameAreaRect.left + attackerRect.width / 2) /
            scale -
          projectileOffset,
        y:
          (attackerRect.top - gameAreaRect.top + attackerRect.height / 2) /
          scale,
      };

      const end = {
        x:
          (targetRect.left - gameAreaRect.left + targetRect.width / 2) / scale -
          projectileOffset,
        y: (targetRect.top - gameAreaRect.top + targetRect.height / 2) / scale,
      };

      setStartPos(start);
      setEndPos(end);
    } else {
      // If elements not found, cancel the animation and proceed with the attack
      emitter.emit("attack-animation:completed", attack);
      setAttack(null);
    }
  }, [attack]);

  if (!attack && !impactFlash) {
    return null;
  }

  return (
    <>
      {attack && startPos && endPos && (
        <GameProjectile
          startPosition={startPos}
          endPosition={endPos}
          duration={300}
          cardSet={attack.cardSet}
          onAnimationComplete={() => {
            emitter.emit("attack-animation:completed", attack);
            setImpactFlash(true);
            clearFlashTimeout();
            flashTimeoutRef.current = window.setTimeout(() => {
              setImpactFlash(false);
            }, 180);
            setAttack(null);
          }}
        />
      )}
      {impactFlash && <div className="attack-flash" />}
    </>
  );
}
