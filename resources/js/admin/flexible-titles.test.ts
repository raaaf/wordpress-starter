import { describe, expect, it } from 'vitest';
import { stripTags } from './flexible-titles';

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
});
