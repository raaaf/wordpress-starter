#!/usr/bin/env node
/**
 * Check that every var(--name) inside the @theme block of app.css resolves.
 *
 * Run: node scripts/check-theme-vars.js
 */

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const APP_CSS = resolve(ROOT, 'resources/css/app.css');
const TOKENS_CSS = resolve(ROOT, 'resources/css/tokens.css');

const appCss = readFileSync(APP_CSS, 'utf8');
const tokensCss = readFileSync(TOKENS_CSS, 'utf8');

const themeMatch = /@theme\s*\{/.exec(appCss);
const themeStart = themeMatch.index + themeMatch[0].length;
const themeEnd = appCss.indexOf('\n}', themeStart);
const themeBlock = appCss.slice(themeStart, themeEnd);

const declared = new Set();
for (const m of (tokensCss + appCss.slice(0, themeStart)).matchAll(/--([a-zA-Z0-9-]+)\s*:/g)) {
  declared.add(m[1]);
}

const unresolved = [];
for (const m of themeBlock.matchAll(/var\(--([a-zA-Z0-9-]+)\)/g)) {
  if (!declared.has(m[1])) unresolved.push(`--${m[1]}`);
}

const uniqueUnresolved = [...new Set(unresolved)];
console.log(`${uniqueUnresolved.length} unresolved`);
if (uniqueUnresolved.length > 0) {
  uniqueUnresolved.forEach((name) => console.log(`  ${name}`));
  process.exit(1);
}
