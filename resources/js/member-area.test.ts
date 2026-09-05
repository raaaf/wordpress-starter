import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

/**
 * member-area.ts only exports registerMemberAreaComponents(); the component
 * factories themselves are private. This suite captures them the same way
 * Alpine.data() does, then drives each component directly against a mocked
 * fetch, mirroring the AJAX contract in
 * src/Providers/MemberAreaServiceProvider.php: `member_get_nonces` returns
 * `{success:true, data:{login, logout, downloads}}`; a rejected nonce comes
 * back as HTTP 403 with `{success:false, data:{message}}`.
 *
 * The module keeps its nonce cache in module-scoped variables (not exported,
 * not reset by anything the tests can call). vi.resetModules() plus a fresh
 * dynamic import per test gives each test its own cache instead of leaking
 * cached nonces from one test into the next.
 */

type ComponentFactory = () => Record<string, unknown>;

async function captureComponents(): Promise<Record<string, ComponentFactory>> {
  vi.resetModules();
  const { registerMemberAreaComponents } = await import('./member-area');
  const registry: Record<string, ComponentFactory> = {};
  registerMemberAreaComponents({
    data: (name, component) => {
      registry[name] = component as ComponentFactory;
    },
  });
  return registry;
}

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

const NONCES = { login: 'login-nonce', logout: 'logout-nonce', downloads: 'downloads-nonce' };

function withNoop$watch<T extends Record<string, unknown>>(component: T): T {
  return {
    ...component,
    $watch: () => undefined,
  };
}

describe('member-area.ts', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    (globalThis as Record<string, unknown>).memberAreaConfig = {
      ajaxUrl: 'https://wordpress.local/wp-admin/admin-ajax.php',
      authMode: 'password',
    };
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    delete (globalThis as Record<string, unknown>).memberAreaConfig;
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  function urlOf(call: unknown[]): string {
    const input = call[0];
    return typeof input === 'string' ? input : (input as URL | Request).toString();
  }

  describe('nonce caching', () => {
    it('reuses cached nonces for a second call within the TTL', async () => {
      fetchMock.mockImplementation((input: RequestInfo | URL) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          return Promise.resolve(jsonResponse({ success: true, data: NONCES }));
        }
        return Promise.resolve(
          jsonResponse({ success: true, data: { categories: [], extensions: [] } })
        );
      });

      const { downloadTable } = await captureComponents();
      const component = withNoop$watch(downloadTable()) as {
        loadFacets(): Promise<void>;
      };

      await component.loadFacets();
      await component.loadFacets();

      const nonceCalls = fetchMock.mock.calls.filter((call) =>
        urlOf(call).includes('action=member_get_nonces')
      );
      expect(nonceCalls).toHaveLength(1);
    });

    it('shares one in-flight nonce request between concurrent callers', async () => {
      const gate: { resolve?: (value: Response) => void } = {};
      fetchMock.mockImplementation((input: RequestInfo | URL) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          return new Promise<Response>((resolve) => {
            gate.resolve = resolve;
          });
        }
        return Promise.resolve(
          jsonResponse({ success: true, data: { categories: [], extensions: [] } })
        );
      });

      const { downloadTable } = await captureComponents();
      const component = withNoop$watch(downloadTable()) as {
        loadFacets(): Promise<void>;
      };

      // Both calls start before the nonce response arrives, like facets and
      // the first page do on load.
      const pending = Promise.all([component.loadFacets(), component.loadFacets()]);
      await Promise.resolve();
      gate.resolve?.(jsonResponse({ success: true, data: NONCES }));
      await pending;

      const nonceCalls = fetchMock.mock.calls.filter((call) =>
        urlOf(call).includes('action=member_get_nonces')
      );
      expect(nonceCalls).toHaveLength(1);
    });
  });

  describe('nonce invalidation on rejection', () => {
    it('clears the cache and retries once with a fresh nonce after a 403', async () => {
      let nonceRequests = 0;
      let queryRequests = 0;

      fetchMock.mockImplementation((input: RequestInfo | URL) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          nonceRequests += 1;
          const nonces = nonceRequests === 1 ? { ...NONCES, downloads: 'stale' } : NONCES;
          return Promise.resolve(jsonResponse({ success: true, data: nonces }));
        }
        if (url.includes('action=member_downloads_query')) {
          queryRequests += 1;
          if (url.includes('nonce=stale')) {
            return Promise.resolve(
              jsonResponse({ success: false, data: { message: 'Ungültige Anfrage.' } }, 403)
            );
          }
          return Promise.resolve(
            jsonResponse({ success: true, data: { categories: [], extensions: [] } })
          );
        }
        throw new Error(`unexpected fetch: ${url}`);
      });

      const { downloadTable } = await captureComponents();
      const component = withNoop$watch(downloadTable()) as {
        loadFacets(): Promise<void>;
      };

      await component.loadFacets();

      expect(nonceRequests).toBe(2); // first (stale) fetch, then the retry fetch
      expect(queryRequests).toBe(2); // rejected call with the stale nonce, then the retry
    });
  });

  describe('safeUrl (via downloadTable.fetch())', () => {
    async function fetchItemsWithUrl(downloadUrl: string): Promise<string> {
      fetchMock.mockImplementation((input: RequestInfo | URL) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          return Promise.resolve(jsonResponse({ success: true, data: NONCES }));
        }
        if (url.includes('action=member_downloads_query')) {
          return Promise.resolve(
            jsonResponse({
              success: true,
              data: {
                items: [
                  {
                    id: 1,
                    title: 'Doc',
                    ext: 'pdf',
                    ext_variant: 'pdf',
                    category_label: 'Cat',
                    last_modified: '2026-01-01',
                    is_updated: false,
                    available: true,
                    download_url: downloadUrl,
                  },
                ],
                total: 1,
                pages: 1,
                current_page: 1,
                per_page: 20,
              },
            })
          );
        }
        throw new Error(`unexpected fetch: ${url}`);
      });

      const { downloadTable } = await captureComponents();
      const component = withNoop$watch(downloadTable()) as {
        items: { download_url: string }[];
        fetch(): Promise<void>;
      };
      await component.fetch();
      return component.items[0].download_url;
    }

    it('keeps a same-origin https URL', async () => {
      const sameOriginUrl = `${window.location.origin}/download/1`;
      const url = await fetchItemsWithUrl(sameOriginUrl);
      expect(url).toBe(sameOriginUrl);
    });

    it('drops a cross-origin URL', async () => {
      const url = await fetchItemsWithUrl('https://evil.example.com/download/1');
      expect(url).toBe('');
    });

    it('drops a protocol-relative cross-origin URL', async () => {
      const url = await fetchItemsWithUrl('//evil.example.com/download/1');
      expect(url).toBe('');
    });

    it('drops a javascript: URL', async () => {
      const url = await fetchItemsWithUrl('javascript:alert(1)');
      expect(url).toBe('');
    });
  });

  describe('memberLogin credential field naming', () => {
    async function captureLoginBody(): Promise<{
      formData: FormData | null;
      submit: () => Promise<void>;
    }> {
      let formData: FormData | null = null;
      fetchMock.mockImplementation((input: RequestInfo | URL, init?: RequestInit) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          return Promise.resolve(jsonResponse({ success: true, data: NONCES }));
        }
        formData = init?.body as FormData;
        return Promise.resolve(jsonResponse({ success: true, data: { redirect: '/' } }));
      });

      const { memberLogin } = await captureComponents();
      const component = memberLogin() as {
        username: string;
        password: string;
        submit(): Promise<void>;
      };
      component.username = 'jane';
      component.password = 'secret';

      return {
        get formData() {
          return formData;
        },
        submit: () => component.submit(),
      } as unknown as { formData: FormData | null; submit: () => Promise<void> };
    }

    it('sends only "credential" for shared-password mode', async () => {
      (globalThis as Record<string, unknown>).memberAreaConfig = {
        ajaxUrl: 'https://wordpress.local/wp-admin/admin-ajax.php',
        authMode: 'password',
      };
      const capture = await captureLoginBody();
      await capture.submit();

      expect(capture.formData?.get('credential')).toBe('secret');
      expect(capture.formData?.has('password')).toBe(false);
    });

    it('sends "credential" (username) and "password" for wordpress mode', async () => {
      (globalThis as Record<string, unknown>).memberAreaConfig = {
        ajaxUrl: 'https://wordpress.local/wp-admin/admin-ajax.php',
        authMode: 'wordpress',
      };
      const capture = await captureLoginBody();
      await capture.submit();

      expect(capture.formData?.get('credential')).toBe('jane');
      expect(capture.formData?.get('password')).toBe('secret');
    });
  });

  describe('memberLogin redirect resolution (R2-S10)', () => {
    async function submitWithRedirect(redirect: string): Promise<string> {
      fetchMock.mockImplementation((input: RequestInfo | URL) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          return Promise.resolve(jsonResponse({ success: true, data: NONCES }));
        }
        return Promise.resolve(jsonResponse({ success: true, data: { redirect } }));
      });

      const { memberLogin } = await captureComponents();
      const component = memberLogin() as {
        password: string;
        submit(): Promise<void>;
      };
      component.password = 'secret';
      await component.submit();
      return window.location.href;
    }

    afterEach(() => {
      window.history.replaceState(null, '', '/');
    });

    it('resolves a relative same-origin redirect against the current origin', async () => {
      const href = await submitWithRedirect('/member/downloads');
      expect(href).toBe(`${window.location.origin}/member/downloads`);
    });

    it('resolves a protocol-relative same-origin redirect against the current origin', async () => {
      const href = await submitWithRedirect(`//${window.location.host}/member/downloads`);
      expect(href).toBe(`${window.location.origin}/member/downloads`);
    });

    it('rejects a foreign-origin redirect and stays on the current page', async () => {
      const before = window.location.href;
      const href = await submitWithRedirect('https://evil.example.com/phish');
      expect(href).toBe(before);
    });
  });

  describe('memberLogin nonce rejection (R2-C1/S2)', () => {
    it('does not auto-retry on a 403 and shows a German session-expired error instead', async () => {
      let nonceRequests = 0;
      let loginRequests = 0;

      fetchMock.mockImplementation((input: RequestInfo | URL) => {
        const url = typeof input === 'string' ? input : input.toString();
        if (url.includes('action=member_get_nonces')) {
          nonceRequests += 1;
          return Promise.resolve(jsonResponse({ success: true, data: NONCES }));
        }
        loginRequests += 1;
        return Promise.resolve(
          jsonResponse({ success: false, data: { message: 'Ungültige Anfrage.' } }, 403)
        );
      });

      const { memberLogin } = await captureComponents();
      const component = memberLogin() as {
        error: string;
        username: string;
        password: string;
        submit(): Promise<void>;
      };
      component.username = 'jane';
      component.password = 'secret';

      await component.submit();

      // Exactly one nonce fetch and one login POST: no automatic retry.
      expect(nonceRequests).toBe(1);
      expect(loginRequests).toBe(1);
      expect(component.error).toBe('Sitzung abgelaufen, bitte erneut anmelden.');
    });
  });
});

describe('downloadTable focus preservation on re-render (R2-F4)', () => {
  let fetchMock: ReturnType<typeof vi.fn>;
  let root: HTMLElement;

  beforeEach(() => {
    (globalThis as Record<string, unknown>).memberAreaConfig = {
      ajaxUrl: 'https://wordpress.local/wp-admin/admin-ajax.php',
      authMode: 'password',
    };
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    root = document.createElement('div');
    root.innerHTML = `
      <table><tbody><tr><td><button id="row-btn">x</button></td></tr></tbody></table>
      <p aria-live="polite"></p>
    `;
    document.body.appendChild(root);
  });

  afterEach(() => {
    delete (globalThis as Record<string, unknown>).memberAreaConfig;
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    root.remove();
  });

  function stubDownloadsQuery(): void {
    fetchMock.mockImplementation((input: RequestInfo | URL) => {
      const url = typeof input === 'string' ? input : input.toString();
      if (url.includes('action=member_get_nonces')) {
        return Promise.resolve(jsonResponse({ success: true, data: NONCES }));
      }
      if (url.includes('action=member_downloads_query')) {
        return Promise.resolve(
          jsonResponse({
            success: true,
            data: { items: [], total: 0, pages: 1, current_page: 1, per_page: 20 },
          })
        );
      }
      throw new Error(`unexpected fetch: ${url}`);
    });
  }

  it('moves focus to the aria-live status line when focus was inside the table', async () => {
    stubDownloadsQuery();
    root.querySelector<HTMLButtonElement>('#row-btn')!.focus();

    const { downloadTable } = await captureComponents();
    const component = withNoop$watch(downloadTable()) as {
      $el: HTMLElement;
      $nextTick: (callback?: () => void) => Promise<void>;
      fetch(): Promise<void>;
    };
    component.$el = root;
    component.$nextTick = (callback?: () => void) => {
      callback?.();
      return Promise.resolve();
    };

    await component.fetch();

    expect(document.activeElement).toBe(root.querySelector('[aria-live="polite"]'));
  });

  it('leaves focus untouched when it was outside the table', async () => {
    stubDownloadsQuery();
    document.body.focus();

    const { downloadTable } = await captureComponents();
    const component = withNoop$watch(downloadTable()) as {
      $el: HTMLElement;
      $nextTick: (callback?: () => void) => Promise<void>;
      fetch(): Promise<void>;
    };
    component.$el = root;
    component.$nextTick = (callback?: () => void) => {
      callback?.();
      return Promise.resolve();
    };

    await component.fetch();

    expect(document.activeElement).not.toBe(root.querySelector('[aria-live="polite"]'));
  });
});
