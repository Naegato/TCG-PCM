import "server-only";

import { cookies } from "next/headers";

import { SESSION_COOKIE } from "@/lib/auth/constants";

if (!process.env.API_INTERNAL_URL) {
  throw new Error(
    "API_INTERNAL_URL n'est pas configurée (variable d'environnement manquante côté serveur front).",
  );
}

const API_INTERNAL_URL = process.env.API_INTERNAL_URL;

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
  ) {
    super(message);
  }
}

type ServerApiFetchOptions = RequestInit & {
  skipAuth?: boolean;
  authorizationToken?: string;
};

export async function getServerToken(): Promise<string | null> {
  const store = await cookies();
  return store.get(SESSION_COOKIE)?.value ?? null;
}

export async function serverApiFetch<T>(
  endpoint: string,
  options?: ServerApiFetchOptions,
): Promise<T> {
  const token = options?.authorizationToken ?? (await getServerToken());
  const isFormData = options?.body instanceof FormData;
  const headers: HeadersInit = {
    ...(!isFormData && { "Content-Type": "application/json" }),
    ...(token && !options?.skipAuth && { Authorization: `Bearer ${token}` }),
    ...options?.headers,
  };

  const response = await fetch(`${API_INTERNAL_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const errorBody = (await response.json().catch(() => null)) as {
      detail?: string;
      message?: string;
    } | null;

    if (errorBody?.detail) {
      throw new ApiError(errorBody.detail, response.status);
    }

    if (errorBody?.message) {
      throw new ApiError(errorBody.message, response.status);
    }

    throw new ApiError(`API request failed with status ${response.status}`, response.status);
  }

  if (response.status === 204) {
    return {} as T;
  }

  return response.json();
}

export function serverApiGet<T>(endpoint: string): Promise<T> {
  return serverApiFetch<T>(endpoint, { method: "GET" });
}

export function serverApiPost<T>(endpoint: string, body: unknown = {}): Promise<T> {
  return serverApiFetch<T>(endpoint, {
    method: "POST",
    body: JSON.stringify(body),
  });
}

export function serverApiPostFormData<T>(endpoint: string, body: FormData): Promise<T> {
  return serverApiFetch<T>(endpoint, {
    method: "POST",
    body,
  });
}
