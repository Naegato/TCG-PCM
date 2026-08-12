import { notFound } from "next/navigation";
import { Bug } from "lucide-react";
import { authApiGet } from "@/lib/api/authServer";
import { GameDebugResponse } from "@/lib/api/resources/GameDebugResource";
import DebugActionPanel from "@/components/organisms/game-debug/DebugActionPanel";
import GameStateInspector from "@/components/organisms/game-debug/GameStateInspector";
import { GameProvider } from "@/contexts/GameContext";

export default async function GameDebugPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const user = await authApiGet<{ isAdmin: boolean }>("/user");
  if (!user.isAdmin) {
    notFound();
  }

  const { state, mercure_token } = await authApiGet<GameDebugResponse>(`/game/${id}/debug`);

  // Neither player is "the connected user" here, so GameProvider's default topic
  // derivation (based on username) would only grant the public topic. Subscribe to
  // both players' private topics explicitly so admin-triggered draws/reveals resolve.
  const mercureUrl = `${process.env.NEXT_PUBLIC_MERCURE_URL}?topic=game/${id}&topic=game/${id}-1&topic=game/${id}-2`;

  return (
    <GameProvider gameId={id} game={state} mercureToken={mercure_token} mercureUrl={mercureUrl} omniscient>
      <div className="min-h-screen bg-background">
        <header className="border-b-2 border-ink-outline bg-card">
          <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-4 md:px-6">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-ink-outline bg-primary text-primary-foreground shadow-[var(--sticker-shadow-sm)]">
              <Bug className="size-5" />
            </div>
            <div className="min-w-0">
              <h1 className="font-display text-xl font-extrabold md:text-2xl">Debug — Partie</h1>
              <p className="truncate font-mono text-xs text-muted-foreground">{id}</p>
            </div>
          </div>
        </header>

        <main className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 md:px-6 lg:flex-row lg:items-start">
          <div className="w-full lg:sticky lg:top-6 lg:w-80 lg:shrink-0">
            <DebugActionPanel gameId={id} />
          </div>
          <div className="min-w-0 flex-1">
            <GameStateInspector />
          </div>
        </main>
      </div>
    </GameProvider>
  );
}
