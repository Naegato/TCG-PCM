"use client";

import { ReactNode, useContext } from "react";
import { Crown, Hand, Layers, Swords, Trash2, Shield } from "lucide-react";
import { cn } from "@/lib/utils";
import { GameContext } from "@/contexts/GameContext";
import { PlayerState, CardState } from "@/lib/game/type/gameState";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

type GetCardById = (id: string) => { name: string } | undefined;

function IdList({ ids, getCardById }: { ids: string[]; getCardById: GetCardById }) {
  if (ids.length === 0) {
    return <p className="text-xs text-muted-foreground italic">vide</p>;
  }

  return (
    <ul className="flex flex-col gap-1">
      {ids.map((id) => (
        <li
          key={id}
          className="flex items-baseline justify-between gap-2 rounded-lg bg-muted/50 px-2 py-1 text-xs"
        >
          <span className="truncate font-medium">{getCardById(id)?.name ?? "?"}</span>
          <span className="shrink-0 font-mono text-[0.65rem] text-muted-foreground">{id}</span>
        </li>
      ))}
    </ul>
  );
}

function Section({
  icon,
  label,
  count,
  children,
}: {
  icon: ReactNode;
  label: string;
  count: number;
  children: ReactNode;
}) {
  return (
    <div>
      <div className="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
        {icon}
        <span>
          {label} ({count})
        </span>
      </div>
      {children}
    </div>
  );
}

function PlayerInspector({
  player,
  accent,
  isCurrentPlayer,
  getCardById,
}: {
  player: PlayerState;
  accent: "sky" | "cherry";
  isCurrentPlayer: boolean;
  getCardById: GetCardById;
}) {
  const discardIds = Object.keys(player.discardPile);
  const hpRatio = player.maxHealthPoints > 0 ? player.healthPoints / player.maxHealthPoints : 0;

  return (
    <Card
      className={cn(
        "gap-4 p-4",
        accent === "sky" ? "border-sky-400" : "border-cherry",
      )}
    >
      <div className="flex items-center justify-between gap-2">
        <div className="flex items-center gap-2 min-w-0">
          <span
            className={cn(
              "size-2.5 shrink-0 rounded-full",
              accent === "sky" ? "bg-sky-400" : "bg-cherry",
            )}
          />
          <h3 className="truncate font-display font-bold">{player.player.name}</h3>
          {isCurrentPlayer && <Crown className="size-4 shrink-0 text-primary" />}
        </div>
        <span className="shrink-0 font-mono text-[0.65rem] text-muted-foreground">{player.player.id}</span>
      </div>

      <dl className="grid grid-cols-2 gap-2 text-xs">
        <div className="rounded-lg bg-muted/50 px-2 py-1.5">
          <dt className="text-muted-foreground">PV</dt>
          <dd className="font-semibold">
            {player.healthPoints} / {player.maxHealthPoints}
          </dd>
          <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-background">
            <div
              className={cn("h-full rounded-full", accent === "sky" ? "bg-sky-400" : "bg-cherry")}
              style={{ width: `${Math.max(0, Math.min(100, hpRatio * 100))}%` }}
            />
          </div>
        </div>
        <div className="rounded-lg bg-muted/50 px-2 py-1.5">
          <dt className="text-muted-foreground">Pièces</dt>
          <dd className="font-semibold">{player.coins}</dd>
        </div>
        <div className="col-span-2 rounded-lg bg-muted/50 px-2 py-1.5">
          <dt className="text-muted-foreground">characterCardId</dt>
          <dd className="truncate font-mono text-[0.65rem]">{player.characterCardId || "—"}</dd>
        </div>
      </dl>

      <Section icon={<Hand className="size-3.5" />} label="Main" count={player.hand.length}>
        <IdList ids={player.hand} getCardById={getCardById} />
      </Section>

      <Section icon={<Swords className="size-3.5" />} label="Monstres" count={player.playArea.monsterCards.length}>
        <IdList ids={player.playArea.monsterCards} getCardById={getCardById} />
      </Section>

      <Section icon={<Shield className="size-3.5" />} label="Passifs" count={player.playArea.passiveCards.length}>
        <IdList ids={player.playArea.passiveCards} getCardById={getCardById} />
      </Section>

      <Section icon={<Trash2 className="size-3.5" />} label="Défausse" count={discardIds.length}>
        <IdList ids={discardIds} getCardById={getCardById} />
      </Section>
    </Card>
  );
}

function CardsTable({ cards }: { cards: Record<string, CardState> }) {
  const entries = Object.values(cards);

  return (
    <div className="overflow-x-auto rounded-xl border-2 border-ink-outline">
      <table className="w-full text-xs">
        <thead>
          <tr className="bg-muted/70 text-left">
            <th className="px-3 py-2 font-semibold">nom</th>
            <th className="px-3 py-2 font-mono font-semibold text-muted-foreground">instanceId</th>
            <th className="px-3 py-2 font-semibold">effects</th>
            <th className="px-3 py-2 font-semibold">values</th>
          </tr>
        </thead>
        <tbody>
          {entries.map((card, i) => (
            <tr key={card.instanceId} className={cn("border-t border-ink-outline/15", i % 2 === 1 && "bg-muted/30")}>
              <td className="px-3 py-2 align-top font-medium">{card.name}</td>
              <td className="px-3 py-2 align-top font-mono text-[0.65rem] text-muted-foreground">
                {card.instanceId}
              </td>
              <td className="px-3 py-2 align-top font-mono whitespace-pre-wrap text-[0.7rem]">
                {card.effects.length > 0 ? JSON.stringify(card.effects) : "—"}
              </td>
              <td className="px-3 py-2 align-top font-mono whitespace-pre-wrap text-[0.7rem]">
                {card.values && Object.keys(card.values as object).length > 0 ? JSON.stringify(card.values) : "—"}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default function GameStateInspector() {
  const { game, getCardById } = useContext(GameContext);

  if (!game) {
    return null;
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <PlayerInspector
          player={game.player1}
          accent="sky"
          isCurrentPlayer={game.currentPlayerId === game.player1.player.id}
          getCardById={getCardById}
        />
        <PlayerInspector
          player={game.player2}
          accent="cherry"
          isCurrentPlayer={game.currentPlayerId === game.player2.player.id}
          getCardById={getCardById}
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Layers className="size-4 text-primary" />
            Toutes les cartes ({Object.keys(game.cards).length})
          </CardTitle>
        </CardHeader>
        <CardContent>
          <CardsTable cards={game.cards} />
        </CardContent>
      </Card>
    </div>
  );
}
