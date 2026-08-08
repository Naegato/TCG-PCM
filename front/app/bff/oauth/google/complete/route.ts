import { NextRequest, NextResponse } from "next/server";

import {
  PASSWORD_EXPIRED_COOKIE,
  REFRESH_TOKEN_COOKIE,
  SESSION_COOKIE,
} from "@/lib/auth/constants";

const JWT_COOKIE_MAX_AGE = 60 * 60 * 24;
const REFRESH_TOKEN_COOKIE_MAX_AGE = 60 * 60 * 24 * 30;

export async function POST(request: NextRequest) {
  const body = (await request.json().catch(() => null)) as
    | { token?: string; refreshToken?: string }
    | null;

  const token = body?.token ?? "";
  const refreshToken = body?.refreshToken ?? "";

  if (token.split(".").length !== 3 || !refreshToken) {
    return NextResponse.json(
      { detail: "Jeton de connexion invalide." },
      { status: 400 },
    );
  }

  const response = NextResponse.json({ ok: true });
  response.cookies.set(SESSION_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: JWT_COOKIE_MAX_AGE,
  });
  response.cookies.set(REFRESH_TOKEN_COOKIE, refreshToken, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: REFRESH_TOKEN_COOKIE_MAX_AGE,
  });
  response.cookies.delete(PASSWORD_EXPIRED_COOKIE);

  return response;
}
