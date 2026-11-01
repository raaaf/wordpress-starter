// Alpine component of the public token generator (templates/styleguide/generator.blade.php).
// The token logic lives in token-generator.ts; this file only wires it to the page:
// it loads the site's own token files, writes the generated colours inline on
// <html> (the page follows) and hands out the files as downloads.
import type { AlpineMagics } from '../../src/types/alpine';
import {
  PALETTE_COLORS,
  STATUS_COLORS,
  currentColor,
  generateFiles,
  generatePalette,
  hexToRgb,
  pageVariables,
  previewVariables,
  semanticOverrides,
  type ButtonStyle,
  toJson,
  type TokenFiles,
} from './token-generator';
import { inkFor } from './token-playground';
import { createZip } from './zip';

export const TOKEN_FILES = ['primitives', 'light', 'dark'] as const;
export type TokenFileName = (typeof TOKEN_FILES)[number];
export type DownloadKind = TokenFileName | 'css' | 'zip';

const COLOR_NAMES = [...PALETTE_COLORS, ...STATUS_COLORS];

/** "#abc" or "abc123" to "#AABBCC"; null for anything else. */
export function normalizeHex(input: string): string | null {
  const rgb = hexToRgb(input.trim());
  if (!rgb) return null;
  return (
    '#' +
    [rgb.r, rgb.g, rgb.b]
      .map((n) => n.toString(16).padStart(2, '0'))
      .join('')
      .toUpperCase()
  );
}

export interface TokenGeneratorConfig {
  /** REST base URL (DesignTokenRestProvider::endpoint()); the file name is appended. */
  endpoint: string;
  /** Start colours read on the server, so pickers and palettes show before any fetch. */
  initial?: Record<string, string>;
  labels?: Record<string, string>;
}

export interface TokenGeneratorComponent extends AlpineMagics {
  state: 'idle' | 'loading' | 'ready' | 'error';
  message: string;
  colors: Record<string, string>;
  initial: Record<string, string>;
  preview: { light: Record<string, string>; dark: Record<string, string> };
  buttons: ButtonStyle;
  readonly changed: string[];
  init(): void;
  load(): Promise<boolean>;
  shades(name: string): Array<{ shade: string; hex: string }>;
  setColor(name: string, value: string, target?: HTMLInputElement): void;
  update(): void;
  reset(): void;
  download(kind: DownloadKind): Promise<void>;
}

function saveFile(name: string, content: BlobPart, type: string): void {
  const url = URL.createObjectURL(new Blob([content], { type }));
  const link = document.createElement('a');
  link.href = url;
  link.download = name;
  document.body.append(link);
  link.click();
  link.remove();
  // Revoked later: Safari drops the download if the URL dies in the same task.
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

const INK_FILLS: Record<string, string> = {
  '--gp-brand-2-ink': '.token-gen-pill--brand-2',
  '--gp-success-ink': '.token-gen-pill--success',
  '--gp-warning-ink': '.token-gen-pill--warning',
  '--gp-error-ink': '.token-gen-pill--error',
};

/** Black or white ink for the preview fills, from their rendered colour. */
function inkFromDom(root: HTMLElement, mode: 'light' | 'dark'): Record<string, string> {
  const card = root.querySelector(`.token-gen-preview[data-theme="${mode}"]`);
  const vars: Record<string, string> = {};
  if (!card) return vars;
  for (const [name, selector] of Object.entries(INK_FILLS)) {
    const el = card.querySelector(selector);
    const match = el && /rgba?\((\d+),\s*(\d+),\s*(\d+)/.exec(getComputedStyle(el).backgroundColor);
    if (!match) continue;
    const hex =
      '#' +
      match
        .slice(1, 4)
        .map((n) => Number(n).toString(16).padStart(2, '0'))
        .join('');
    vars[name] = inkFor(hex).ink;
  }
  return vars;
}

const OVERRIDES_ID = 'token-generator-overrides';

/**
 * Re-pointed semantic aliases for the live page, scoped to the mode they
 * belong to (same conditions as the light and dark blocks in tokens.css).
 */
function writeOverrides(overrides: Record<'light' | 'dark', Record<string, string>>): void {
  const block = (vars: Record<string, string>) =>
    Object.entries(vars)
      .map(([name, value]) => `${name}:${value};`)
      .join('');
  const light = block(overrides.light);
  const dark = block(overrides.dark);
  let style = document.getElementById(OVERRIDES_ID);
  if (!light && !dark) {
    style?.remove();
    return;
  }
  if (!style) {
    style = document.createElement('style');
    style.id = OVERRIDES_ID;
    document.head.append(style);
  }
  style.textContent =
    (light
      ? `[data-theme="light"]{${light}}` +
        `@media not (prefers-color-scheme: dark){:root:not([data-theme="dark"]){${light}}}`
      : '') +
    (dark
      ? `[data-theme="dark"]{${dark}}` +
        `@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){${dark}}}`
      : '');
}

export function createTokenGeneratorComponent(
  config: TokenGeneratorConfig
): TokenGeneratorComponent {
  const t = (key: string, fallback: string) => config.labels?.[key] ?? fallback;
  // The token files are large; kept out of Alpine's reactive proxies on purpose.
  let base: TokenFiles | null = null;
  let applied: string[] = [];
  let frame = 0;
  let loading: Promise<boolean> | null = null;

  return {
    ...({} as AlpineMagics),
    state: 'idle',
    message: '',
    colors: { ...config.initial },
    initial: { ...config.initial },
    preview: { light: {}, dark: {} },
    buttons: 'neutral',

    get changed() {
      const colors = COLOR_NAMES.filter(
        (name) => this.colors[name] && this.colors[name] !== this.initial[name]
      );
      return this.buttons === 'neutral' ? colors : [...colors, 'buttons'];
    },

    init() {
      // Nothing loads with the page: the template calls load() once the generator
      // nears the viewport or on the first pointer or focus. Until then the
      // preview cards show the page's own tokens; only the ink on the fills
      // without a semantic text colour is worked out here from the DOM.
      this.preview = { light: inkFromDom(this.$el, 'light'), dark: inkFromDom(this.$el, 'dark') };
    },

    load() {
      loading ??= (async () => {
        this.state = 'loading';
        try {
          const [primitives, light, dark] = await Promise.all(
            TOKEN_FILES.map(async (file) => {
              const response = await fetch(`${config.endpoint}${file}`, {
                credentials: 'omit',
                headers: { Accept: 'application/json' },
              });
              if (!response.ok) throw new Error(`${file}: HTTP ${response.status}`);
              return response.json();
            })
          );
          base = { primitives, light, dark };
          // The files are the reference for "changed"; edits made while loading stay.
          const initial: Record<string, string> = {};
          for (const name of COLOR_NAMES) {
            initial[name] = currentColor(base, name) ?? this.initial[name] ?? '#000000';
          }
          for (const name of COLOR_NAMES) {
            if (!this.colors[name] || this.colors[name] === this.initial[name]) {
              this.colors[name] = initial[name];
            }
          }
          this.initial = initial;
          this.state = 'ready';
          return true;
        } catch {
          this.state = 'error';
          this.message = t('loadError', 'The token files could not be loaded.');
          return false;
        }
      })();
      return loading;
    },

    shades(name) {
      const hex = this.colors[name];
      if (!hex) return [];
      const palette = generatePalette(hex);
      const keys = (PALETTE_COLORS as readonly string[]).includes(name)
        ? Object.keys(palette)
        : ['100', '500', '700'];
      return keys.map((shade) => ({ shade, hex: palette[shade] }));
    },

    setColor(name, value, target) {
      const hex = normalizeHex(value);
      if (!hex) {
        // Invalid text input: back to the last valid colour.
        if (target) target.value = this.colors[name];
        this.message = t('invalid', 'Please enter a colour as #RRGGBB.');
        return;
      }
      this.colors[name] = hex;
      this.update();
    },

    update() {
      // Colour pickers fire on every pointer move: one update per frame.
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        if (!base) {
          // First change before the files are here: apply once they are.
          void this.load().then((ok) => ok && this.update());
          return;
        }
        const generated = generateFiles(base, this.colors, this.initial, this.buttons);
        const vars = pageVariables(base, generated);
        const root = document.documentElement;
        root.classList.add('token-playground-live');
        for (const name of applied) if (!(name in vars)) root.style.removeProperty(name);
        for (const [name, value] of Object.entries(vars)) root.style.setProperty(name, value);
        applied = Object.keys(vars);
        writeOverrides(semanticOverrides(base, generated));
        this.preview = {
          light: previewVariables(generated, 'light'),
          dark: previewVariables(generated, 'dark'),
        };
        this.message = '';
      });
    },

    reset() {
      cancelAnimationFrame(frame);
      const root = document.documentElement;
      for (const name of applied) root.style.removeProperty(name);
      applied = [];
      writeOverrides({ light: {}, dark: {} });
      this.colors = { ...this.initial };
      this.buttons = 'neutral';
      if (base) {
        this.preview = {
          light: previewVariables(base, 'light'),
          dark: previewVariables(base, 'dark'),
        };
      }
      this.message = t('resetDone', 'Colours reset.');
    },

    async download(kind) {
      if (!(await this.load()) || !base) return;
      const files = generateFiles(base, this.colors, this.initial, this.buttons);
      const json = (name: TokenFileName) => toJson(files[name]);
      try {
        if (kind !== 'css' && kind !== 'zip') {
          saveFile(`${kind}.tokens.json`, json(kind), 'application/json');
        } else {
          // The transform is only needed here, so it loads on the first CSS or ZIP download.
          const { buildTokenCss } = await import('../../scripts/tokens-core.js');
          const { css, editorCss } = buildTokenCss(files.primitives, files.light, files.dark);
          if (kind === 'css') {
            saveFile('tokens.css', css, 'text/css');
          } else {
            const zip = createZip([
              ...TOKEN_FILES.map((name) => ({
                name: `config/design-tokens/${name}.tokens.json`,
                content: json(name),
              })),
              { name: 'resources/css/tokens.css', content: css },
              { name: 'resources/css/tokens-editor.css', content: editorCss },
              {
                name: 'README.txt',
                content: `${t('readmeTitle', 'Design tokens from the token generator.')}\n\n${t(
                  'readmeBody',
                  'Copy config/ and resources/ into the theme folder and run npm run tokens, or upload the three JSON files in wp-admin under Design tokens.'
                )}\n`,
              },
            ]);
            saveFile('design-tokens.zip', zip, 'application/zip');
          }
        }
        this.message = t('downloaded', 'Download started.');
      } catch {
        this.message = t('downloadError', 'The download could not be created.');
      }
    },
  };
}
