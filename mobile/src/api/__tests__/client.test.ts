/**
 * The API client is the only code in the app that touches the network, and
 * every feature will go through it. These tests cover the branches features
 * depend on but cannot see: the auth header, an empty body, Laravel's
 * validation shape, a reply that is not JSON, and the difference between
 * "offline" and "the caller cancelled".
 */
import { ApiError, apiRequest, setTokenReader } from '../client';

const mockFetch = global.fetch as jest.Mock;

function reply(body: string, init: { status?: number; statusText?: string } = {}): Response {
  return {
    ok: (init.status ?? 200) < 400,
    status: init.status ?? 200,
    statusText: init.statusText ?? '',
    text: () => Promise.resolve(body),
  } as Response;
}

beforeEach(() => {
  mockFetch.mockReset();
  setTokenReader(() => null);
});

it('sends the bearer token, and omits it when anonymous', async () => {
  setTokenReader(() => 'tok_123');
  mockFetch.mockResolvedValue(reply('{"ok":true}'));

  await apiRequest('/api/me');
  await apiRequest('/api/auth/login', { method: 'POST', anonymous: true, body: { a: 1 } });

  const [, authed] = mockFetch.mock.calls[0];
  const [, anon] = mockFetch.mock.calls[1];

  expect(authed.headers.Authorization).toBe('Bearer tok_123');
  expect(anon.headers.Authorization).toBeUndefined();
  expect(anon.body).toBe('{"a":1}');
});

it('returns nothing for a 204', async () => {
  mockFetch.mockResolvedValue(reply('', { status: 204 }));

  await expect(apiRequest('/api/couple', { method: 'DELETE' })).resolves.toBeUndefined();
});

it('carries Laravel validation errors on a 422', async () => {
  mockFetch.mockResolvedValue(
    reply('{"message":"Invalid.","errors":{"email":["Already taken."]}}', { status: 422 }),
  );

  const error = await apiRequest('/api/auth/register').catch((e: unknown) => e);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).isValidation).toBe(true);
  expect((error as ApiError).errors?.email).toEqual(['Already taken.']);
});

it('reports a transport failure as status 0, and as retryable', async () => {
  mockFetch.mockRejectedValue(new TypeError('Network request failed'));

  const error = (await apiRequest('/api/health').catch((e: unknown) => e)) as ApiError;

  expect(error.status).toBe(0);
  expect(error.isRetryable).toBe(true);
  expect(error.isUnauthenticated).toBe(false);
});

it('treats a 401 as unauthenticated, not retryable', async () => {
  mockFetch.mockResolvedValue(reply('{"message":"Unauthenticated."}', { status: 401 }));

  const error = (await apiRequest('/api/me').catch((e: unknown) => e)) as ApiError;

  expect(error.isUnauthenticated).toBe(true);
  expect(error.isRetryable).toBe(false);
});

it('refuses a successful reply that is not JSON', async () => {
  // A captive portal answers 200 with an HTML login page. Handing that back as
  // the expected shape is how the app ends up claiming it is connected.
  mockFetch.mockResolvedValue(reply('<html><body>Sign in to WiFi</body></html>'));

  await expect(apiRequest('/api/health')).rejects.toThrow(/not JSON/);
});

it('still uses a non-JSON error body as the message', async () => {
  mockFetch.mockResolvedValue(reply('PHP Fatal error: out of memory', { status: 500 }));

  const error = (await apiRequest('/api/health').catch((e: unknown) => e)) as ApiError;

  expect(error.status).toBe(500);
  expect(error.message).toContain('PHP Fatal error');
});

it('rethrows a cancellation instead of calling it a network failure', async () => {
  const caller = new AbortController();
  const cancelled = new Error('Aborted');

  mockFetch.mockImplementation(() => {
    caller.abort();
    return Promise.reject(cancelled);
  });

  await expect(apiRequest('/api/health', { signal: caller.signal })).rejects.toBe(cancelled);
});

it('gives up on a request that never answers', async () => {
  // A connection that neither answers nor fails: without the timeout this
  // promise never settles, so the UI can only spin.
  jest.useFakeTimers();

  mockFetch.mockImplementation(
    (_url: string, init: { signal: AbortSignal }) =>
      new Promise((_resolve, reject) => {
        init.signal.addEventListener('abort', () => reject(new Error('Aborted')));
      }),
  );

  try {
    const pending = apiRequest('/api/health', { anonymous: true });

    // Let the call reach `fetch` before moving the clock, or the timeout has
    // not been scheduled yet and nothing would fire.
    await Promise.resolve();
    jest.advanceTimersByTime(15_000);

    const error = (await pending.catch((e: unknown) => e)) as ApiError;

    expect(error).toBeInstanceOf(ApiError);
    expect(error.status).toBe(0);
  } finally {
    // In a finally so a failed expectation cannot leave fake timers installed
    // for every test after it.
    jest.useRealTimers();
  }
});
