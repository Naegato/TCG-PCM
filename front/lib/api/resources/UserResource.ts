import { ApiClient } from "../api";
import { CardCollectionResponse } from "@/app/types/collection";

export class UserResource {
  constructor(private client: ApiClient) {}

  async getInventoryCollection() {
    return this.client.get(`/inventory/collection`) as Promise<CardCollectionResponse>;
  }

  async getUser() {
    return this.client.get(`/user`);
  }

  async getInventorySetStats() {
    return this.client.get(`/inventory/stats`);
  }

  public generateBoosterTokens() {
    return this.client.post("/user/generate_booster_tokens");
  }
}
