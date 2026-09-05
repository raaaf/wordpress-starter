import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { getLayoutPreview, stripTags } from './flexible-titles';

/**
 * Die Zeilenvorschau im Editor ist das einzige, was eine eingeklappte Sektion
 * unterscheidbar macht. Sie entsteht aus dem HTML eines WYSIWYG-Feldes, und
 * `textContent` kennt keine Wortgrenzen: Blockelemente landen ohne Trennung
 * aneinander.
 */
describe('stripTags', () => {
  it('setzt ein Leerzeichen zwischen Blockelemente', () => {
    expect(stripTags('<h2>Layout &amp; Text</h2><p>Verschiedene Spalten</p>')).toBe(
      'Layout & Text Verschiedene Spalten'
    );
  });

  it('behandelt <br> als Wortgrenze', () => {
    expect(stripTags('Zeile eins<br>Zeile zwei')).toBe('Zeile eins Zeile zwei');
  });

  it('laesst Inline-Auszeichnung ohne Luecke', () => {
    expect(stripTags('<p>ein <strong>fettes</strong> Wort</p>')).toBe('ein fettes Wort');
  });

  it('raeumt mehrfache Leerzeichen und Raender auf', () => {
    expect(stripTags('<p>  viel   Luft  </p><p>  danach </p>')).toBe('viel Luft danach');
  });

  it('gibt bei leerem HTML einen leeren String zurueck', () => {
    expect(stripTags('<p></p>')).toBe('');
  });

  it('entfernt script- und style-Inhalte aus dem Vorschautext', () => {
    expect(stripTags('<p>Text</p><script>alert(1)</script><style>.x{color:red}</style>')).toBe(
      'Text'
    );
  });

  it('fuehrt kein Skript aus einem eingebetteten <img onerror> aus', () => {
    const createElementSpy = vi.spyOn(document, 'createElement');

    const result = stripTags('<img src=x onerror="window.__pwned=1">');

    expect(result).not.toContain('onerror');
    expect(result).not.toContain('<img');
    expect((window as unknown as { __pwned?: number }).__pwned).toBeUndefined();
    // Der Fix parst ueber DOMParser statt ueber document.createElement('div').innerHTML,
    // deshalb entsteht hier kein <img>-Element im Live-Dokument.
    expect(createElementSpy).not.toHaveBeenCalledWith('img');

    createElementSpy.mockRestore();
  });
});

/**
 * getLayoutPreview's WYSIWYG "visual mode" branch reads the iframe's live
 * body content. It used to read `body.textContent` directly, which — unlike
 * the other branches — never went through stripTags and so lost the
 * block-element word boundary (see the "setzt ein Leerzeichen..." test
 * above).
 */
describe('getLayoutPreview WYSIWYG visual-mode branch', () => {
  let layout: HTMLElement;

  beforeEach(() => {
    layout = document.createElement('div');
    layout.innerHTML = `
      <div data-name="content">
        <iframe></iframe>
      </div>
    `;
    document.body.appendChild(layout);
  });

  afterEach(() => {
    layout.remove();
  });

  it('inserts a space between block elements from the iframe body, like the source-mode branches', () => {
    const iframe = layout.querySelector('iframe')!;
    iframe.contentDocument!.body.innerHTML = '<p>Zeile eins</p><p>Zeile zwei</p>';

    expect(getLayoutPreview(layout)).toBe('Zeile eins Zeile zwei');
  });
});
