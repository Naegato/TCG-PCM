import mitt from "mitt";
import { BasicCard } from "./cards/types/card";
import { CardSet } from "@/constants/card";

type Events = {
  "card:drag:start": { pos: { x: number; y: number }; card: BasicCard };
  "card:drag:end": {
    pos: { x: number | undefined; y: number | undefined };
    card: BasicCard;
  };
  "card:drag:move": { pos: { x: number; y: number }; card: BasicCard };
  "card:played": { card: BasicCard; playerId?: string };
  "card:discarded": { card: BasicCard };
  "card:return-hand": {
    pos: { x: number | undefined; y: number | undefined };
    card: BasicCard;
  };
  "card:dropped": { card: BasicCard; zoneId: string };
  "game:card-drawn": { playerId: string; cardId: string };
  "game:coins-changed": { playerId: string; delta: number };
  "game:health-changed": {
    playerId: string;
    delta: number;
    type: "damage" | "heal";
  };
  "animation:card-draw-complete": void;
  "attack-animation:start": { attackerId: string; targetId: string; cardSet: CardSet };
  "attack-animation:completed": { attackerId: string; targetId: string };
  "card:stolen": { card: BasicCard; fromPlayerId: string; toPlayerId: string };
  "card:triggered": { cardId: string };
  "card:marker": { cardId: string; text: string; tone: "positive" | "negative" };
};

export const emitter = mitt<Events>();
