import { GameState } from "@/lib/game/type/gameState";
import { ApiClient } from "../api";

export type GameDebugResponse = {
  state: GameState;
  mercure_url: string;
  mercure_token: string;
};

export class GameDebugResource {
  constructor(private client: ApiClient) {}

  async getDebugGame(id: string) {
    return this.client.get<GameDebugResponse>(`/game/${id}/debug`);
  }

  async giveCard(id: string, playerId: string, cardTemplateId: string, zone: "hand" | "board") {
    return this.client.post(`/game/${id}/debug`, {
      actionId: "give_card",
      payload: { playerId, cardTemplateId, zone },
    });
  }

  async setStats(id: string, playerId: string, healthPoints?: number, coins?: number) {
    return this.client.post(`/game/${id}/debug`, {
      actionId: "set_stats",
      payload: { playerId, healthPoints, coins },
    });
  }

  async forceEndTurn(id: string) {
    return this.client.post(`/game/${id}/debug`, {
      actionId: "force_end_turn",
      payload: {},
    });
  }

  async forceSetCurrentPlayer(id: string, playerId: string) {
    return this.client.post(`/game/${id}/debug`, {
      actionId: "force_set_current_player",
      payload: { playerId },
    });
  }

  async removeCard(id: string, cardId: string) {
    return this.client.post(`/game/${id}/debug`, {
      actionId: "remove_card",
      payload: { cardId },
    });
  }
}
