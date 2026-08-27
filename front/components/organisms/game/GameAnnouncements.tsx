"use client";

import { useContext } from "react";
import { GameContext } from "@/contexts/GameContext";
import type { GameAnnouncement } from "@/contexts/GameContext";
import { getImage } from "@/lib/api/api";

type GameAnnouncementsProps = {
  regularAnnouncements: GameAnnouncement[];
  giantAnnouncement: GameAnnouncement | null;
};

function ConsumableCardReveal({
  announcement,
}: {
  announcement: GameAnnouncement;
}) {
  return (
    <>
      <div
        className={`pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center gap-6 px-6 transition-opacity duration-150 ease-out ${
          announcement.leaving ? "opacity-0" : "opacity-100"
        }`}
      >
        <div>
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
          // Keyed by announcement id so consecutive consumables get independent reveals.
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
