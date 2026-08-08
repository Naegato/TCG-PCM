import "server-only";

import { getServerToken } from "@/lib/api/server";
import { decodeJwtPayload, isJwtExpired } from "@/lib/auth/jwt";

export type SessionUser = {
  username: string;
};

export async function getCurrentUser(): Promise<SessionUser | null> {
  const token = await getServerToken();
  if (!token || isJwtExpired(token)) return null;

  const payload = decodeJwtPayload(token);
  if (!payload || typeof payload.username !== "string") return null;

  return { username: payload.username };
}
