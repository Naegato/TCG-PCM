import { NextRequest, NextResponse } from "next/server";

import { PASSWORD_EXPIRED_COOKIE, SESSION_COOKIE } from "@/lib/auth/constants";
import { isJwtExpired } from "@/lib/auth/jwt";

// Pages (ou sections, via préfixe) accessibles sans session, en plus de rester consultables une fois connecté.
const PUBLIC_PATHS = ["/how-to-play", "/legal", "/oauth/callback"];
// Pages réservées aux visiteurs non connectés (redirigées vers /boosters une fois connecté).
const GUEST_ONLY_PATHS = ["/login", "/register", "/forgot-password", "/reset-password"];
// Page toujours accessible une fois connecté, même avec un mot de passe expiré.
const CHANGE_PASSWORD_PATH = "/change-password";

// Décode le payload du JWT sans vérifier la signature (l'API reste seule autorité sur la
// validité du token) juste pour savoir si le cookie de session correspond à un token expiré :
// sans ça, un token expiré mais toujours présent en cookie fait croire au middleware que
// l'utilisateur est connecté, alors que /boosters (Server Component, ne peut pas rafraîchir le
// token pendant son render) le renvoie vers /login, que le middleware renvoie vers /boosters
// puisque le cookie est là — boucle de redirection infinie.
function isSessionExpired(token: string): boolean {
  return isJwtExpired(token);
}

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  const sessionToken = request.cookies.get(SESSION_COOKIE)?.value;
  const hasSession = !!sessionToken && !isSessionExpired(sessionToken);
  const isPublicPath = PUBLIC_PATHS.some(
    (path) => pathname === path || pathname.startsWith(`${path}/`),
  );
  const isGuestOnlyPath = GUEST_ONLY_PATHS.includes(pathname);

  if (!hasSession && !isPublicPath && !isGuestOnlyPath) {
    const response = NextResponse.redirect(new URL("/login", request.url));
    if (sessionToken) {
      response.cookies.delete(SESSION_COOKIE);
    }
    return response;
  }

  if (hasSession && isGuestOnlyPath) {
    return NextResponse.redirect(new URL("/boosters", request.url));
  }

  if (
    hasSession &&
    request.cookies.has(PASSWORD_EXPIRED_COOKIE) &&
    pathname !== CHANGE_PASSWORD_PATH
  ) {
    return NextResponse.redirect(new URL(CHANGE_PASSWORD_PATH, request.url));
  }

  return NextResponse.next();
}

export const config = {
  // Les fichiers statiques de public/ (images, polices...) et les routes API doivent rester
  // accessibles sans redirection : une route API doit répondre en JSON, jamais par une
  // redirection HTML vers /login.
  matcher: ["/((?!bff/|_next/static|_next/image|favicon.ico|.*\\.(?:png|jpg|jpeg|gif|webp|svg|ico|ttf|woff|woff2|pdf)$).*)"],
};
