// Member Area Alpine.js Components
import type { AlpineMagics } from '../../src/types/alpine';

// Declare localized config from WordPress.
// Nonces are intentionally not part of this payload — the page may be served
// from a cache and would carry stale nonces. We fetch fresh ones on demand.
declare const memberAreaConfig: {
  ajaxUrl: string;
  authMode: string;
};

interface NonceSet {
  login: string;
  logout: string;
  downloads: string;
}

let cachedNonces: NonceSet | null = null;
let cachedAt = 0;
const NONCE_CACHE_MS = 60_000;

async function fetchNonces(): Promise<NonceSet> {
  const now = Date.now();
  if (cachedNonces && now - cachedAt < NONCE_CACHE_MS) {
    return cachedNonces;
  }
  const url = `${memberAreaConfig.ajaxUrl}?action=member_get_nonces&_=${now}`;
  const response = await fetch(url, {
    credentials: 'same-origin',
    cache: 'no-store',
  });
  const data = await response.json();
  if (!data?.success || !data.data) {
    throw new Error('member_get_nonces failed');
  }
  cachedNonces = data.data as NonceSet;
  cachedAt = now;
  return cachedNonces;
}

// ============================================
// Member Login Component
// ============================================

interface MemberLoginState {
  loading: boolean;
  error: string;
  username: string;
  password: string;
  submit(): Promise<void>;
}

function createMemberLoginComponent(): MemberLoginState {
  return {
    loading: false,
    error: '',
    username: '',
    password: '',

    async submit() {
      this.loading = true;
      this.error = '';

      const config = typeof memberAreaConfig !== 'undefined' ? memberAreaConfig : null;
      if (!config) {
        this.error = 'Konfigurationsfehler.';
        this.loading = false;
        return;
      }

      try {
        const nonces = await fetchNonces();

        const body = new FormData();
        body.append('action', 'member_login');
        body.append('nonce', nonces.login);
        body.append('redirect', window.location.href);

        if (config.authMode === 'wordpress') {
          body.append('credential', this.username);
          body.append('password', this.password);
        } else {
          body.append('credential', this.password);
        }

        const response = await fetch(config.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body,
        });

        const data = await response.json();

        if (data.success) {
          window.location.href = data.data?.redirect || window.location.href;
        } else {
          this.error = data.data?.message || 'Anmeldung fehlgeschlagen.';
        }
      } catch {
        this.error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
      } finally {
        this.loading = false;
      }
    },
  };
}

// ============================================
// Download Table Component
// ============================================

interface DownloadItem {
  id: number;
  title: string;
  ext: string;
  ext_variant: string;
  category_label: string;
  last_modified: string;
  is_updated: boolean;
  available: boolean;
  download_url: string;
}

interface FacetOption {
  slug?: string;
  value?: string;
  label: string;
  count: number;
}

interface DownloadTableState extends AlpineMagics {
  items: DownloadItem[];
  total: number;
  pages: number;
  currentPage: number;
  perPage: number;
  search: string;
  category: string;
  ext: string;
  loading: boolean;
  error: string;
  categories: FacetOption[];
  extensions: FacetOption[];
  init(): void;
  loadFacets(): Promise<void>;
  fetch(): Promise<void>;
  setPage(page: number): void;
  pageNumbers(): (number | string)[];
  badgeClass(variant: string): string;
}

function createDownloadTableComponent(): DownloadTableState {
  return {
    // Alpine magic properties ($el, $watch, etc.) are injected at runtime
    ...({} as AlpineMagics),
    items: [],
    total: 0,
    pages: 1,
    currentPage: 1,
    perPage: 20,
    search: '',
    category: '',
    ext: '',
    loading: true,
    error: '',
    categories: [],
    extensions: [],

    init() {
      this.loadFacets();
      this.fetch();
      // Reset to page 1 and refetch on any filter change
      // search uses x-model.debounce.350ms in the template, so this fires after the delay
      this.$watch('search', () => {
        this.currentPage = 1;
        this.fetch();
      });
      this.$watch('category', () => {
        this.currentPage = 1;
        this.fetch();
      });
      this.$watch('ext', () => {
        this.currentPage = 1;
        this.fetch();
      });
      this.$watch('perPage', () => {
        this.currentPage = 1;
        this.fetch();
      });
    },

    async loadFacets() {
      const config = typeof memberAreaConfig !== 'undefined' ? memberAreaConfig : null;
      if (!config) return;

      try {
        const nonces = await fetchNonces();
        const params = new URLSearchParams({
          action: 'member_downloads_query',
          nonce: nonces.downloads,
          facets: '1',
        });
        const response = await fetch(`${config.ajaxUrl}?${params}`, {
          credentials: 'same-origin',
        });
        const data = await response.json();
        if (data.success) {
          this.categories = data.data.categories;
          this.extensions = data.data.extensions;
        }
      } catch {
        // Facets are non-critical — fail silently
      }
    },

    async fetch() {
      this.loading = true;
      this.error = '';

      const config = typeof memberAreaConfig !== 'undefined' ? memberAreaConfig : null;
      if (!config) {
        this.error = 'Konfigurationsfehler.';
        this.loading = false;
        return;
      }

      try {
        const nonces = await fetchNonces();
        const params = new URLSearchParams({
          action: 'member_downloads_query',
          nonce: nonces.downloads,
          page: String(this.currentPage),
          per_page: String(this.perPage),
          search: this.search,
          category: this.category,
          ext: this.ext,
        });
        const response = await fetch(`${config.ajaxUrl}?${params}`, {
          credentials: 'same-origin',
        });
        const data = await response.json();

        if (data.success) {
          this.items = data.data.items;
          this.total = data.data.total;
          this.pages = data.data.pages;
          this.currentPage = data.data.current_page;
          this.perPage = data.data.per_page;
        } else {
          this.error = data.data?.message || 'Fehler beim Laden der Dokumente.';
          this.items = [];
        }
      } catch {
        this.error = 'Verbindungsfehler. Bitte Seite neu laden.';
        this.items = [];
      } finally {
        this.loading = false;
      }
    },

    setPage(page: number) {
      if (page < 1 || page > this.pages) return;
      this.currentPage = page;
      this.fetch();
    },

    pageNumbers(): (number | string)[] {
      if (this.pages <= 7) {
        return Array.from({ length: this.pages }, (_, i) => i + 1);
      }

      const current = this.currentPage;
      const last = this.pages;
      const delta = 2;
      const left = current - delta;
      const right = current + delta;

      const pages: (number | string)[] = [];

      pages.push(1);
      if (left > 2) pages.push('...');

      for (let i = Math.max(2, left); i <= Math.min(last - 1, right); i++) {
        pages.push(i);
      }

      if (right < last - 1) pages.push('...');
      pages.push(last);

      return pages;
    },

    badgeClass(_variant: string): string {
      return 'bg-transparent text-content border border-line';
    },
  };
}

// ============================================
// Registration
// ============================================

export function registerMemberAreaComponents(Alpine: {
  data: (name: string, component: () => object) => void;
}): void {
  Alpine.data('memberLogin', createMemberLoginComponent);
  Alpine.data('downloadTable', createDownloadTableComponent);
}
