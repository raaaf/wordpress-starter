#!/usr/bin/env node
/**
 * Fill resources/icons/ from config/icons.json.
 *
 * The theme used to carry hand-collected SVGs from two different sources. Measured
 * before this script existed: 23 filled 16-unit icons alongside 4 stroked 24-unit
 * ones, ink coverage ranging from 0.44 to 1.00 of the box, and a `plus` whose cross
 * sat at (9,9) instead of the centre. None of that is configurable away — it is what
 * mixing icon sets looks like.
 *
 * One set, one grid, one weight. Adding an icon means adding a line to
 * config/icons.json and running this; nothing is drawn by hand.
 *
 * Run: npm run icons
 */

import {
  readFileSync,
  writeFileSync,
  readdirSync,
  unlinkSync,
  existsSync,
  mkdirSync,
} from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const CONFIG = resolve(ROOT, 'config/icons.json');
const TARGET = resolve(ROOT, 'resources/icons');

const SOURCES = {
  phosphor: (name, weight) =>
    resolve(ROOT, `node_modules/@phosphor-icons/core/assets/${weight}/${name}.svg`),
  'simple-icons': (name) => resolve(ROOT, `node_modules/simple-icons/icons/${name}.svg`),
};

/**
 * Normalise a source SVG into what the x-icon component expects.
 *
 * - width/height off: the component sizes via CSS, a fixed attribute would win.
 * - fill="currentColor" on the root: simple-icons ships no fill at all and would
 *   otherwise render black regardless of the text colour around it.
 * - <title> out: the component sets aria-hidden, a title would be read out anyway.
 */
function normalise(svg) {
  let out = svg.trim();
  out = out.replace(/<title>[\s\S]*?<\/title>/gi, '');
  out = out.replace(/\s(width|height)="[^"]*"/gi, '');
  out = out.replace(/\srole="[^"]*"/gi, '');

  if (!/<svg[^>]*\sfill="/i.test(out)) {
    out = out.replace(/<svg/i, '<svg fill="currentColor"');
  }

  return `${out}\n`;
}

const SAFE_TOKEN = /^[a-z0-9-]+$/;

function main() {
  const config = JSON.parse(readFileSync(CONFIG, 'utf8'));
  const weight = config.weight ?? 'regular';
  const entries = Object.entries(config.icons);

  if (!SAFE_TOKEN.test(weight)) {
    console.error(`FEHLER: Gewicht "${weight}" enthält unzulässige Zeichen (erlaubt: a-z 0-9 -)`);
    process.exit(1);
  }

  if (!existsSync(TARGET)) {
    mkdirSync(TARGET, { recursive: true });
  }

  // Pass 1: resolve every entry against its source before writing or deleting
  // anything. A single bad "source" or "name" must not take down icons that
  // resolved fine — and it must not delete an already-shipped, valid icon
  // either. So nothing gets written and nothing gets removed until every
  // entry in the config has been proven resolvable.
  const resolved = [];
  const missing = [];

  for (const [target, spec] of entries) {
    if (!SAFE_TOKEN.test(target) || !SAFE_TOKEN.test(spec.name)) {
      missing.push(`${target}: Schlüssel/Name enthält unzulässige Zeichen (erlaubt: a-z 0-9 -)`);
      continue;
    }

    if (!Object.keys(SOURCES).includes(spec.source)) {
      missing.push(`${target}: unbekannte Quelle "${spec.source}"`);
      continue;
    }

    const locate = SOURCES[spec.source];

    const from = locate(spec.name, weight);

    if (!existsSync(from)) {
      missing.push(`${target}: ${spec.source}/${spec.name} nicht gefunden (${from})`);
      continue;
    }

    resolved.push({ target, from });
  }

  if (missing.length) {
    console.error('FEHLER:');
    for (const m of missing) {
      console.error(`  ${m}`);
    }
    console.error('\nAbgebrochen, bevor etwas geschrieben oder aufgeräumt wurde.');
    process.exit(1);
  }

  // Pass 2: everything resolved, safe to write.
  const written = new Set();
  for (const { target, from } of resolved) {
    try {
      writeFileSync(resolve(TARGET, `${target}.svg`), normalise(readFileSync(from, 'utf8')));
      written.add(`${target}.svg`);
    } catch (error) {
      console.error('FEHLER:');
      console.error(`  ${target}: Icon konnte nicht geschrieben werden (${error.message})`);
      console.error(
        `\nAbgebrochen mitten in Phase 2, resources/icons/ enthält jetzt einen Mischbestand aus alten und neuen Icons (${written.size} von ${resolved.length} neu geschrieben).`
      );
      process.exit(1);
    }
  }

  // Pass 3: anything left over is a leftover from the hand-collected era.
  // Removing it here is what keeps "what the theme ships" equal to "what
  // the config lists" — safe now because we know every current entry wrote.
  const removed = [];
  for (const file of readdirSync(TARGET)) {
    if (file.endsWith('.svg') && !written.has(file)) {
      unlinkSync(resolve(TARGET, file));
      removed.push(file);
    }
  }

  console.log(`Icons geschrieben: ${written.size} (Gewicht: ${weight})`);
  if (removed.length) {
    console.log(`Entfernt, weil nicht mehr in der Konfiguration: ${removed.join(', ')}`);
  }
}

main();
