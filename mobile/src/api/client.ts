/**
 * The typed API client.
 *
 * One place that knows how to talk to the Laravel API: base URL, auth header,
 * JSON handling, and error shape. Features use TanStack Query hooks built on
 * top of this — they never call `fetch` directly.
 */
import { API_BASE_URL } from '../config/app';

/** A failed API call. Carries the status so callers can branch without parsing strings. */
export class ApiError extends Error {
  constructor(
    readonly status: number,
    message: string,
    /** Laravel validation errors, when status is 422. */
    readonly errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  get isValidation(): boolean {
    return this.status === 422;
  }

  get isUnauthenticated(): boolean {
    return this.status === 401;
  }

  /** True when retrying might help: network blip or server fault. */
  get isRetryable(): boolean {
    return this.status === 0 || this.status >= 500;
  }
}

type TokenReader = () => string | null | Promise<string | null>;

/**
 * How the client obtains the auth token.
 *
 * Injected rather than imported so this module has no dependency on storage,
 * which keeps it trivially testable. Phase 1 wires it to expo-secure-store.
 */
let readToken: TokenReader = () => null;

export function setTokenReader(reader: TokenReader): void {
  readToken = reader;
}

export type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: unknown;
  /** Skip the Authorization header (registration, login, invite preview). */
  anonymous?: boolean;
  signal?: AbortSignal;
};

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, anonymous = false, signal } = options;

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (!anonymous) {
    const token = await readToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  let response: Response;

  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      signal,
    });
  } catch {
    // A transport failure is status 0 — distinct from a server error, because
    // the app's offline handling (UX rule 11) depends on telling them apart.
    throw new ApiError(0, 'Network request failed', undefined);
  }

  if (response.status === 204) return undefined as T;

  const text = await response.text();
  const payload: unknown = text.length > 0 ? safeParse(text) : undefined;

  if (!response.ok) {
    const record = isRecord(payload) ? payload : {};

    throw new ApiError(
      response.status,
      typeof record.message === 'string' ? record.message : response.statusText,
      isRecord(record.errors) ? (record.errors as Record<string, string[]>) : undefined,
    );
  }

  return payload as T;
}

function safeParse(text: string): unknown {
  try {
    return JSON.parse(text);
  } catch {
    // A non-JSON body from a JSON API means something is wrong upstream (a PHP
    // fatal, an HTML error page, a captive portal). Surface it rather than
    // crashing on the parse.
    return { message: text.slice(0, 200) };
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

/** `GET /api/health` — what the app calls on boot to confirm the API is reachable. */
export type HealthResponse = {
  status: 'ok';
  app: string;
  environment: string;
  version: string;
  time: string;
};

export function fetchHealth(signal?: AbortSignal): Promise<HealthResponse> {
  return apiRequest<HealthResponse>('/api/health', { anonymous: true, signal });
}
