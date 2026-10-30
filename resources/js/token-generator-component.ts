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
  toJson,
  type TokenFiles,
} from './token-generator';
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
  baseUrl: string;
  labels?: Record<string, string>;
}

export interface TokenGeneratorComponent extends AlpineMagics {
  state: 'idle' | 'loading' | 'ready' | 'error';
  message: string;
  colors: Record<string, string>;
  initial: Record<string, string>;
  preview: { light: Record<string, string>; dark: Record<string, string> };
  readonly changed: string[];
  init(): void;
  load(): Promise<void>;
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

export function createTokenGeneratorComponent(
  config: TokenGeneratorConfig
): TokenGeneratorComponent {
  const t = (key: string, fallback: string) => config.labels?.[key] ?? fallback;
  // The token files are large; kept out of Alpine's reactive proxies on purpose.
  let base: TokenFiles | null = null;
  let applied: string[] = [];
  let frame = 0;

  return {
    ...({} as AlpineMagics),
    state: 'idle',
    message: '',
    colors: {},
    initial: {},
    preview: { light: {}, dark: {} },

    get changed() {
      return COLOR_NAMES.filter(
        (name) => this.colors[name] && this.colors[name] !== this.initial[name]
      );
    },

    init() {
      // The files only load when the section comes near the viewport.
      if (!('IntersectionObserver' in window)) {
        void this.load();
        return;
      }
      const observer = new IntersectionObserver(
        (entries) => {
          if (entries.some((entry) => entry.isIntersecting)) {
            observer.disconnect();
            void this.load();
          }
        },
        { rootMargin: '400px 0px' }
      );
      observer.observe(this.$el);
    },

    async load() {
      if (this.state === 'loading' || this.state === 'ready') return;
      this.state = 'loading';
      try {
        const [primitives, light, dark] = await Promise.all(
          TOKEN_FILES.map(async (file) => {
            const response = await fetch(`${config.baseUrl}${file}.tokens.json`);
            if (!response.ok) throw new Error(`${file}: HTTP ${response.status}`);
            return response.json();
          })
        );
        base = { primitives, light, dark };
        const initial: Record<string, string> = {};
        for (const name of COLOR_NAMES) initial[name] = currentColor(base, name) ?? '#000000';
        this.initial = initial;
        this.colors = { ...initial };
        this.preview = {
          light: previewVariables(base, 'light'),
          dark: previewVariables(base, 'dark'),
        };
        this.state = 'ready';
      } catch {
        this.state = 'error';
        this.message = t('loadError', 'The token files could not be loaded.');
      }
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
        if (!base) return;
        const generated = generateFiles(base, this.colors, this.initial);
        const vars = pageVariables(base, generated);
        const root = document.documentElement;
        root.classList.add('token-playground-live');
        for (const name of applied) if (!(name in vars)) root.style.removeProperty(name);
        for (const [name, value] of Object.entries(vars)) root.style.setProperty(name, value);
        applied = Object.keys(vars);
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
      this.colors = { ...this.initial };
      if (base) {
        this.preview = {
          light: previewVariables(base, 'light'),
          dark: previewVariables(base, 'dark'),
        };
      }
      this.message = t('resetDone', 'Colours reset.');
    },

    async download(kind) {
      if (!base) return;
      const files = generateFiles(base, this.colors, this.initial);
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
