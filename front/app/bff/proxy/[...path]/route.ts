import { NextRequest, NextResponse } from "next/server";

import { refreshAccessToken } from "@/lib/actions/auth";
import { ApiError, serverApiFetch } from "@/lib/api/server";

function errorResponse(err: unknown) {
  const message = err instanceof Error ? err.message : "API request failed";
  const status = err instanceof ApiError ? err.status : 400;
  return NextResponse.json({ detail: message }, { status });
}

async function handle(request: NextRequest, path: string[]) {
  const endpoint = `/${path.join("/")}${request.nextUrl.search}`;
  const hasBody = request.method !== "GET" && request.method !== "HEAD";
  const contentType = request.headers.get("content-type");
  const body = hasBody ? await request.text() : undefined;

  const forward = () =>
    serverApiFetch(endpoint, {
      method: request.method,
      body,
      headers: contentType ? { "Content-Type": contentType } : undefined,
    });

  try {
    return NextResponse.json(await forward());
  } catch (err) {
    const refreshed = err instanceof ApiError && err.status === 401 ? await refreshAccessToken() : null;
    if (!refreshed) {
      return errorResponse(err);
    }

    try {
      return NextResponse.json(
        await serverApiFetch(endpoint, {
          method: request.method,
          body,
          headers: contentType ? { "Content-Type": contentType } : undefined,
          authorizationToken: refreshed.token,
        }),
      );
    } catch (retryErr) {
      return errorResponse(retryErr);
    }
  }
}

type RouteContext = { params: Promise<{ path: string[] }> };

export async function GET(request: NextRequest, { params }: RouteContext) {
  const { path } = await params;
  return handle(request, path);
}

export async function POST(request: NextRequest, { params }: RouteContext) {
  const { path } = await params;
  return handle(request, path);
}

export async function PATCH(request: NextRequest, { params }: RouteContext) {
  const { path } = await params;
  return handle(request, path);
}

export async function DELETE(request: NextRequest, { params }: RouteContext) {
  const { path } = await params;
  return handle(request, path);
}
