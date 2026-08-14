"use client";

import { useContext, useEffect, useState } from "react";
import { GameContext } from "@/contexts/GameContext";
import type { GameAnnouncement } from "@/contexts/GameContext";
import { getImage } from "@/lib/api/api";
import {
  COUNTDOWN_AFTER_DIGIT_COUNT,
  COUNTDOWN_BEAT_MS,
  COUNTDOWN_CARD_SETTLE_MS,
  COUNTDOWN_DIGIT_COUNT,
} from "@/lib/game/animationTimings";

type GameAnnouncementsProps = {
  regularAnnouncements: GameAnnouncement[];
  giantAnnouncement: GameAnnouncement | null;
};

// Flanking symbols slapped onto each countdown number, just for the gag — [prefix, suffix].
const COUNTDOWN_AFFIXES: [string, string][] = [
  ["#", "!"],
  ["x", ""],
  ["", "%"],
  ["★", "★"],
  ["", "..."],
  ["+", "!"],
  ["", ""],
];

// Not an actual countdown — a burst of random numbers flashes by, just for the gag.
const getRandomCountdownDigits = (count: number) =>
  Array.from({ length: count }, () => {
    const [prefix, suffix] =
      COUNTDOWN_AFFIXES[Math.floor(Math.random() * COUNTDOWN_AFFIXES.length)];
    const number = Math.floor(Math.random() * 90) + 10;
    return `${prefix}${number}${suffix}`;
  });

// before: burst of random numbers, then the card slams in. after: a second, smaller burst
// flashes over the now-revealed card, just to keep the gag going. done: numbers gone, card sits.
type RevealPhase = "before" | "card" | "after" | "done";

function ConsumableCardReveal({
  announcement,
}: {
  announcement: GameAnnouncement;
}) {
  // The parent keys this whole component by announcement.id (see GameAnnouncements below), so
  // a new consumable reveal always remounts it fresh — phase/digitIndex naturally start back at
  // their defaults, no need to reset them here. Digits are re-rolled on every mount too, via the
  // useState initializers, so each reveal gets a fresh set of random numbers.
  const [beforeDigits] = useState(() =>
    getRandomCountdownDigits(COUNTDOWN_DIGIT_COUNT),
  );
  const [afterDigits] = useState(() =>
    getRandomCountdownDigits(COUNTDOWN_AFTER_DIGIT_COUNT),
  );
  const [phase, setPhase] = useState<RevealPhase>("before");
  const [digitIndex, setDigitIndex] = useState(0);

  useEffect(() => {
    const timeouts: number[] = [];
    const beforeDuration = beforeDigits.length * COUNTDOWN_BEAT_MS;
    const afterStart = beforeDuration + COUNTDOWN_CARD_SETTLE_MS;
    const afterDuration = afterDigits.length * COUNTDOWN_BEAT_MS;

    beforeDigits.slice(1).forEach((_, i) => {
      timeouts.push(
        window.setTimeout(
          () => setDigitIndex(i + 1),
          (i + 1) * COUNTDOWN_BEAT_MS,
        ),
      );
    });

    timeouts.push(
      window.setTimeout(() => {
        setPhase("card");
      }, beforeDuration),
    );

    timeouts.push(
      window.setTimeout(() => {
        setPhase("after");
        setDigitIndex(0);
      }, afterStart),
    );

    afterDigits.slice(1).forEach((_, i) => {
      timeouts.push(
        window.setTimeout(
          () => setDigitIndex(i + 1),
          afterStart + (i + 1) * COUNTDOWN_BEAT_MS,
        ),
      );
    });

    timeouts.push(
      window.setTimeout(() => {
        setPhase("done");
      }, afterStart + afterDuration),
    );

    return () => timeouts.forEach((timeoutId) => window.clearTimeout(timeoutId));
  }, [beforeDigits, afterDigits]);

  if (phase === "before") {
    const digit = beforeDigits[digitIndex];
    return (
      <>
        <div key={`flash-${digit}`} className="card-reveal-countdown-flash" />
        <div className="pointer-events-none fixed inset-0 z-30 flex items-center justify-center">
          <div key={digit} className="card-reveal-countdown-number">
            {digit}
          </div>
        </div>
      </>
    );
  }

  const afterDigit = phase === "after" ? afterDigits[digitIndex] : undefined;

  return (
    <>
      {phase === "card" && !announcement.leaving && (
        <div className="card-reveal-warning-flash" />
      )}
      <div
        className={`pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center gap-6 px-6 transition-opacity duration-[450ms] ease-out ${
          announcement.leaving ? "opacity-0" : "opacity-100"
        }`}
      >
        <div
          className={
            phase === "card" && !announcement.leaving
              ? "card-reveal-impact-shake"
              : undefined
          }
        >
          <div className="animate-card-reveal-in">
            <img
              src={getImage(announcement.cardImage!)}
              alt={announcement.cardName ?? ""}
              className="w-[min(96vw,52rem)] rounded-[2.5rem] border-8 border-ink-outline shadow-[var(--sticker-shadow-lg)]"
            />
          </div>
          <div className="mt-6 rounded-full border-4 border-ink-outline bg-white px-10 py-4 text-center font-display text-4xl font-extrabold leading-none tracking-tight text-ink-outline shadow-[var(--sticker-shadow-sm)] sm:text-7xl">
            {announcement.text}
          </div>
        </div>
      </div>
      {afterDigit && (
        <>
          <div
            key={`after-flash-${afterDigit}`}
            className="card-reveal-countdown-flash"
          />
          <div className="pointer-events-none fixed inset-0 z-40 flex items-center justify-center">
            <div key={afterDigit} className="card-reveal-countdown-number">
              {afterDigit}
            </div>
          </div>
        </>
      )}
    </>
  );
}

export default function GameAnnouncements({
  regularAnnouncements,
  giantAnnouncement,
}: GameAnnouncementsProps) {
  const { targeting } = useContext(GameContext);
  return (
    <>
      <div className="pointer-events-none absolute left-1/2 top-30 z-20 flex w-full max-w-md -translate-x-1/2 flex-col gap-2 px-4 lg:top-4">
        {regularAnnouncements.map((announcement: GameAnnouncement) => (
          <div
            key={announcement.id}
            className={`rounded-full border-3 px-4 py-2 text-center font-display text-sm font-extrabold shadow-[var(--sticker-shadow-sm)] transition-opacity duration-[450ms] ease-out ${
              announcement.leaving ? "opacity-0" : "opacity-100"
            } ${
              announcement.tone === "positive"
                ? "border-white bg-mint text-ink-outline"
                : announcement.tone === "negative"
                  ? "border-white bg-cherry text-white"
                  : "border-ink-outline bg-white text-ink-outline"
            }`}
          >
            {announcement.text}
          </div>
        ))}
        {targeting.selectedAttackerId && (
          <div className="rounded-full border-3 border-white bg-sky-400 text-ink-outline px-4 py-2 text-center font-display text-sm font-extrabold shadow-[var(--sticker-shadow-sm)]">
            Choisis une cible pour attaquer
          </div>
        )}
      </div>

      {giantAnnouncement &&
        (giantAnnouncement.cardImage ? (
          // Keyed by announcement id so two consumables played back-to-back each get a fresh
          // mount — otherwise React would patch the existing nodes instead of remounting them,
          // and the countdown wouldn't restart.
          <ConsumableCardReveal
            key={giantAnnouncement.id}
            announcement={giantAnnouncement}
          />
        ) : (
          <div
            className={`pointer-events-none absolute inset-0 z-30 flex items-center justify-center px-6 transition-opacity duration-[450ms] ease-out ${
              giantAnnouncement.leaving ? "opacity-0" : "opacity-100"
            }`}
          >
            <div className="flex min-h-64 min-w-64 flex-col items-center justify-center rounded-[2.5rem] border-4 border-ink-outline bg-white px-10 py-8 text-center shadow-[var(--sticker-shadow-lg)]">
              <div className="relative">
                <div className="dice-burst" />
                <div className="dice-face" />
              </div>
              <div className="mt-4 font-display text-7xl font-extrabold leading-none tracking-tight text-ink-outline sm:text-[8rem]">
                {giantAnnouncement.text.replace(/^🎲\s*/, "")}
              </div>
            </div>
          </div>
        ))}
    </>
  );
}
