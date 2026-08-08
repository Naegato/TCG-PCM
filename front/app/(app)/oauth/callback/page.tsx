"use client";

import { useEffect, useState } from "react";

const ERROR_MESSAGES: Record<string, string> = {
  oauth_cancelled: "Connexion annulée.",
  invalid_state: "La demande de connexion a expiré, réessaie.",
  oauth_failed: "La connexion avec Google a échoué.",
};

export default function GoogleOAuthCallbackPage() {
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function run() {
      const params = new URLSearchParams(window.location.search);
      const queryError = params.get("error");
      if (queryError) {
        return ERROR_MESSAGES[queryError] ?? "Une erreur est survenue.";
      }

      const hash = new URLSearchParams(window.location.hash.slice(1));
      const token = hash.get("token");
      const refreshToken = hash.get("refresh_token");
      if (!token || !refreshToken) {
        return "Une erreur est survenue.";
      }

      const response = await fetch("/bff/oauth/google/complete", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ token, refreshToken }),
      });

      if (!response.ok) {
        const payload = (await response.json().catch(() => null)) as
          | { detail?: string }
          | null;
        return payload?.detail ?? "Une erreur est survenue.";
      }

      window.location.replace("/boosters");
      return null;
    }

    run().then(setError);
  }, []);

  return (
    <main className="flex justify-center sm:mt-32">
      <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl border border-black/10 text-center">
        {error ? (
          <>
            <p className="text-destructive">{error}</p>
            <a href="/login" className="text-primary hover:underline">
              Retour à la connexion
            </a>
          </>
        ) : (
          <p>Connexion en cours…</p>
        )}
      </div>
    </main>
  );
}
