import { GameEventTypeEnum } from "./eventType";

export type GameEvent = {
  // Local to the resolve() call that produced this event on the backend — not a persisted id,
  // just enough to link an event to the CARD_TRIGGERED_ACTION that caused it via parentId.
  id: number;
  parentId: number | null;
  type: GameEventTypeEnum;
  data: any;
  view: any;
}
