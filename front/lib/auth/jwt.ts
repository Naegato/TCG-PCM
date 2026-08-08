export type JwtPayload = {
  exp?: number;
  username?: string;
  [key: string]: unknown;
};

function normalizeBase64Url(value: string): string {
  const base64 = value.replace(/-/g, "+").replace(/_/g, "/");
  const padding = base64.length % 4;
  return padding === 0 ? base64 : base64 + "=".repeat(4 - padding);
}

export function decodeJwtPayload(token: string): JwtPayload | null {
  const payload = token.split(".")[1];
  if (!payload) return null;

  try {
    const decoded =
      typeof atob === "function"
        ? atob(normalizeBase64Url(payload))
        : Buffer.from(normalizeBase64Url(payload), "base64").toString("utf-8");

    return JSON.parse(decoded) as JwtPayload;
  } catch {
    return null;
  }
}

export function isJwtExpired(token: string): boolean {
  const payload = decodeJwtPayload(token);
  if (!payload || typeof payload.exp !== "number") return true;
  return payload.exp * 1000 <= Date.now();
}
