"use client";

import { ReactNode, useContext, useState } from "react";
import { toast } from "sonner";
import { Gift, HeartPulse, RotateCw, Trash2 } from "lucide-react";
import { GameContext } from "@/contexts/GameContext";
import api from "@/lib/api/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const selectClassName =
  "h-9 w-full rounded-xl border-2 border-ink-outline bg-background px-3 text-sm outline-none focus-visible:ring-3 focus-visible:ring-primary/35";

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex flex-col gap-1.5">
      <Label className="text-muted-foreground">{label}</Label>
      {children}
    </div>
  );
}

function PlayerSelect({
  players,
  value,
  onChange,
}: {
  players: { id: string; label: string }[];
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <select className={selectClassName} value={value} onChange={(e) => onChange(e.target.value)}>
      {players.map((p) => (
        <option key={p.id} value={p.id}>
          {p.label}
        </option>
      ))}
    </select>
  );
}

export default function DebugActionPanel({ gameId }: { gameId: string }) {
  const { game, getCardById } = useContext(GameContext);

  if (!game) {
    return null;
  }

  const players = [
    { id: game.player1.player.id, label: game.player1.player.name || "Player 1" },
    { id: game.player2.player.id, label: game.player2.player.name || "Player 2" },
  ];

  const boardCardIds = [
    ...game.player1.playArea.passiveCards,
    ...game.player1.playArea.monsterCards,
    ...game.player2.playArea.passiveCards,
    ...game.player2.playArea.monsterCards,
  ];

  return (
    <div className="flex flex-col gap-4">
      <GiveCardForm gameId={gameId} players={players} />
      <SetStatsForm gameId={gameId} players={players} />
      <ForceTurnForm gameId={gameId} players={players} />
      <RemoveCardForm gameId={gameId} cardIds={boardCardIds} getCardById={getCardById} />
    </div>
  );
}

function runAction(promise: Promise<unknown>) {
  promise.then(
    () => toast.success("Action appliquée"),
    (err: unknown) => toast.error("Erreur", { description: err instanceof Error ? err.message : String(err) }),
  );
}

function GiveCardForm({ gameId, players }: { gameId: string; players: { id: string; label: string }[] }) {
  const [playerId, setPlayerId] = useState(players[0]?.id ?? "");
  const [cardTemplateId, setCardTemplateId] = useState("");
  const [zone, setZone] = useState<"hand" | "board">("hand");

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Gift className="size-4 text-primary" />
          Donner une carte
        </CardTitle>
      </CardHeader>
      <CardContent>
        <Field label="Joueur">
          <PlayerSelect players={players} value={playerId} onChange={setPlayerId} />
        </Field>
        <Field label="ID de la carte (template)">
          <Input value={cardTemplateId} onChange={(e) => setCardTemplateId(e.target.value)} placeholder="ex: D6" />
        </Field>
        <Field label="Zone">
          <div className="flex gap-4 text-sm">
            <label className="flex items-center gap-1.5">
              <input type="radio" checked={zone === "hand"} onChange={() => setZone("hand")} /> Main
            </label>
            <label className="flex items-center gap-1.5">
              <input type="radio" checked={zone === "board"} onChange={() => setZone("board")} /> Plateau
            </label>
          </div>
        </Field>
        <Button
          className="w-full"
          disabled={!cardTemplateId}
          onClick={() => runAction(api.gameDebug.giveCard(gameId, playerId, cardTemplateId, zone))}
        >
          Donner
        </Button>
      </CardContent>
    </Card>
  );
}

function SetStatsForm({ gameId, players }: { gameId: string; players: { id: string; label: string }[] }) {
  const [playerId, setPlayerId] = useState(players[0]?.id ?? "");
  const [healthPoints, setHealthPoints] = useState("");
  const [coins, setCoins] = useState("");

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <HeartPulse className="size-4 text-primary" />
          PV / pièces
        </CardTitle>
      </CardHeader>
      <CardContent>
        <Field label="Joueur">
          <PlayerSelect players={players} value={playerId} onChange={setPlayerId} />
        </Field>
        <div className="grid grid-cols-2 gap-3">
          <Field label="PV">
            <Input
              type="number"
              value={healthPoints}
              onChange={(e) => setHealthPoints(e.target.value)}
              placeholder="—"
            />
          </Field>
          <Field label="Pièces">
            <Input type="number" value={coins} onChange={(e) => setCoins(e.target.value)} placeholder="—" />
          </Field>
        </div>
        <Button
          className="w-full"
          disabled={!healthPoints && !coins}
          onClick={() =>
            runAction(
              api.gameDebug.setStats(
                gameId,
                playerId,
                healthPoints ? Number(healthPoints) : undefined,
                coins ? Number(coins) : undefined,
              ),
            )
          }
        >
          Appliquer
        </Button>
      </CardContent>
    </Card>
  );
}

function ForceTurnForm({ gameId, players }: { gameId: string; players: { id: string; label: string }[] }) {
  const [playerId, setPlayerId] = useState(players[0]?.id ?? "");

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <RotateCw className="size-4 text-primary" />
          Tour
        </CardTitle>
      </CardHeader>
      <CardContent>
        <Button variant="outline" className="w-full" onClick={() => runAction(api.gameDebug.forceEndTurn(gameId))}>
          Terminer le tour courant
        </Button>
        <Separator />
        <Field label="Passer la main à">
          <PlayerSelect players={players} value={playerId} onChange={setPlayerId} />
        </Field>
        <Button className="w-full" onClick={() => runAction(api.gameDebug.forceSetCurrentPlayer(gameId, playerId))}>
          Forcer ce joueur
        </Button>
      </CardContent>
    </Card>
  );
}

function RemoveCardForm({
  gameId,
  cardIds,
  getCardById,
}: {
  gameId: string;
  cardIds: string[];
  getCardById: (cardId: string) => { name: string } | undefined;
}) {
  const [cardId, setCardId] = useState(cardIds[0] ?? "");

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Trash2 className="size-4 text-cherry" />
          Retirer une carte
        </CardTitle>
      </CardHeader>
      <CardContent>
        {cardIds.length === 0 ? (
          <p className="text-sm text-muted-foreground italic">Aucune carte sur le plateau.</p>
        ) : (
          <>
            <Field label="Carte">
              <select className={selectClassName} value={cardId} onChange={(e) => setCardId(e.target.value)}>
                {cardIds.map((id) => (
                  <option key={id} value={id}>
                    {id}
                  </option>
                ))}
              </select>
            </Field>
            <Button variant="destructive" className="w-full" onClick={() => runAction(api.gameDebug.removeCard(gameId, cardId))}>
              Retirer
            </Button>
          </>
        )}
      </CardContent>
    </Card>
  );
}
