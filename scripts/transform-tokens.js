/**
 * Figma Tokens to CSS Custom Properties Transformer
 *
 * Converts native Figma Variables export format to CSS custom properties
 * for use with TailwindCSS v4's @theme directive.
 *
 * Usage: node scripts/transform-tokens.js
 *
 * Input:  config/design-tokens/{primitives,light,dark}.tokens.json
 * Output: resources/css/tokens.css (auto-generated)
 */

import { readFileSync, writeFileSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');

// Input/output paths
const TOKENS_DIR = join(ROOT, 'config/design-tokens');
const OUTPUT_FILE = join(ROOT, 'resources/css/tokens.css');

/**
 * Extract hex color value from Figma token $value object
 * Handles both simple values and complex color objects with alpha
 */
function extractColorValue(token) {
  if (!token || token.$type !== 'color') return null;

  const value = token.$value;

  // Handle alias references like "{color.accent.500}"
  if (typeof value === 'string' && value.startsWith('{')) {
    return null; // Skip aliases, we use resolved values
  }

  // Handle complex color object with components
  if (typeof value === 'object' && value.hex) {
    const hex = value.hex;
    const alpha = value.alpha ?? 1;

    if (alpha < 1) {
      // Convert hex + alpha to rgba
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha.toFixed(2)})`;
    }

    return hex;
  }

  return null;
}

/**
 * Extract numeric value (spacing, radius, fontSize, etc.)
 */
function extractNumericValue(token, unit = 'px') {
  if (!token) return null;

  const value = token.$value;

  if (typeof value === 'number') {
    return unit ? `${value}${unit}` : value;
  }

  return null;
}

/**
 * Extract string value (fontFamily)
 */
function extractStringValue(token) {
  if (!token || token.$type !== 'string') return null;
  return token.$value;
}

/**
 * Recursively process nested token structure
 * Returns flat object with CSS variable names as keys
 */
function flattenTokens(obj, prefix = '', processor = extractColorValue) {
  const result = {};

  for (const [key, value] of Object.entries(obj)) {
    // Skip metadata keys
    if (key.startsWith('$')) continue;

    // Handle $root special case (Figma uses this for base values in nested objects)
    if (key === '$root') {
      const processed = processor(value);
      if (processed !== null) {
        result[prefix.replace(/-$/, '')] = processed;
      }
      continue;
    }

    const newPrefix = prefix ? `${prefix}-${key}` : key;

    if (value && typeof value === 'object' && !value.$type) {
      // Nested object, recurse
      Object.assign(result, flattenTokens(value, newPrefix, processor));
    } else if (value && value.$type) {
      // Token node
      const processed = processor(value);
      if (processed !== null) {
        result[newPrefix] = processed;
      }
    }
  }

  return result;
}

/**
 * Convert token name to CSS variable name
 * e.g., "gray-50" -> "--color-gray-50"
 */
function toCssVarName(name, prefix = '') {
  const varName = name.toLowerCase().replace(/\s+/g, '-');
  return prefix ? `--${prefix}-${varName}` : `--${varName}`;
}

/**
 * Generate CSS from flattened tokens
 */
function generateCss(tokens, prefix = '') {
  return Object.entries(tokens)
    .map(([name, value]) => `  ${toCssVarName(name, prefix)}: ${value};`)
    .join('\n');
}

/**
 * Main transformation function
 */
function transform() {
  console.log('Reading Figma token files...');

  // Read token files
  const primitives = JSON.parse(
    readFileSync(join(TOKENS_DIR, 'primitives.tokens.json'), 'utf8')
  );
  const lightTokens = JSON.parse(
    readFileSync(join(TOKENS_DIR, 'light.tokens.json'), 'utf8')
  );
  const darkTokens = JSON.parse(
    readFileSync(join(TOKENS_DIR, 'dark.tokens.json'), 'utf8')
  );

  console.log('Processing primitives...');

  // Process primitives - colors
  const primitiveColors = flattenTokens(primitives.color || {}, '', extractColorValue);

  // Process primitives - spacing (convert to rem)
  const primitiveSpacing = flattenTokens(
    primitives.spacing || {},
    '',
    (token) => extractNumericValue(token, 'px')
  );

  // Process primitives - radius
  const primitiveRadius = flattenTokens(
    primitives.radius || {},
    '',
    (token) => extractNumericValue(token, 'px')
  );

  // Process primitives - fontSize (convert to rem for better accessibility)
  const primitiveFontSize = flattenTokens(
    primitives.fontSize || {},
    '',
    (token) => {
      const px = token?.$value;
      if (typeof px === 'number') {
        return `${(px / 16).toFixed(4).replace(/\.?0+$/, '')}rem`;
      }
      return null;
    }
  );

  // Process primitives - fontWeight
  const primitiveFontWeight = flattenTokens(
    primitives.fontWeight || {},
    '',
    (token) => extractNumericValue(token, '')
  );

  // Process primitives - fontFamily
  const primitiveFontFamily = {};
  if (primitives.fontFamily) {
    for (const [key, value] of Object.entries(primitives.fontFamily)) {
      if (key.startsWith('$')) continue;
      const fontValue = extractStringValue(value);
      if (fontValue) {
        primitiveFontFamily[key] = fontValue;
      }
    }
  }

  console.log('Processing semantic tokens (light mode)...');

  // Process semantic tokens - light mode
  const lightBg = flattenTokens(lightTokens.bg || {}, '', extractColorValue);
  const lightText = flattenTokens(lightTokens.text || {}, '', extractColorValue);
  const lightBorder = flattenTokens(lightTokens.border || {}, '', extractColorValue);
  const lightIcon = flattenTokens(lightTokens.icon || {}, '', extractColorValue);

  console.log('Processing semantic tokens (dark mode)...');

  // Process semantic tokens - dark mode
  const darkBg = flattenTokens(darkTokens.bg || {}, '', extractColorValue);
  const darkText = flattenTokens(darkTokens.text || {}, '', extractColorValue);
  const darkBorder = flattenTokens(darkTokens.border || {}, '', extractColorValue);
  const darkIcon = flattenTokens(darkTokens.icon || {}, '', extractColorValue);

  console.log('Generating CSS...');

  // Generate CSS output
  const css = `/**
 * Design Tokens - Auto-generated from Figma
 *
 * DO NOT EDIT THIS FILE DIRECTLY!
 * Run: node scripts/transform-tokens.js
 *
 * Source: config/design-tokens/*.tokens.json
 * Generated: ${new Date().toISOString()}
 */

/* ============================================
   PRIMITIVE TOKENS
   Base values that semantic tokens reference
   ============================================ */

:root {
  /* Colors - Primitives */
${generateCss(primitiveColors, 'color')}

  /* Spacing */
${generateCss(primitiveSpacing, 'spacing')}

  /* Border Radius */
${generateCss(primitiveRadius, 'radius')}

  /* Font Size */
${generateCss(primitiveFontSize, 'font-size')}

  /* Font Weight */
${generateCss(primitiveFontWeight, 'font-weight')}

  /* Font Family */
${generateCss(primitiveFontFamily, 'font-family')}
}

/* ============================================
   SEMANTIC TOKENS - Light Mode (Default)
   Use these for theming components
   ============================================ */

:root,
[data-theme="light"] {
  /* Background */
${generateCss(lightBg, 'bg')}

  /* Text */
${generateCss(lightText, 'text')}

  /* Border */
${generateCss(lightBorder, 'border')}

  /* Icon */
${generateCss(lightIcon, 'icon')}
}

/* ============================================
   SEMANTIC TOKENS - Dark Mode
   Activated via data-theme or prefers-color-scheme
   ============================================ */

[data-theme="dark"] {
  /* Background */
${generateCss(darkBg, 'bg')}

  /* Text */
${generateCss(darkText, 'text')}

  /* Border */
${generateCss(darkBorder, 'border')}

  /* Icon */
${generateCss(darkIcon, 'icon')}
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    /* Background */
${generateCss(darkBg, 'bg')}

    /* Text */
${generateCss(darkText, 'text')}

    /* Border */
${generateCss(darkBorder, 'border')}

    /* Icon */
${generateCss(darkIcon, 'icon')}
  }
}
`;

  // Write output
  writeFileSync(OUTPUT_FILE, css, 'utf8');

  // Stats
  const stats = {
    primitiveColors: Object.keys(primitiveColors).length,
    spacing: Object.keys(primitiveSpacing).length,
    radius: Object.keys(primitiveRadius).length,
    fontSize: Object.keys(primitiveFontSize).length,
    fontWeight: Object.keys(primitiveFontWeight).length,
    fontFamily: Object.keys(primitiveFontFamily).length,
    lightSemanticColors:
      Object.keys(lightBg).length +
      Object.keys(lightText).length +
      Object.keys(lightBorder).length +
      Object.keys(lightIcon).length,
    darkSemanticColors:
      Object.keys(darkBg).length +
      Object.keys(darkText).length +
      Object.keys(darkBorder).length +
      Object.keys(darkIcon).length,
  };

  console.log('\nToken Statistics:');
  console.log(`  Primitive colors: ${stats.primitiveColors}`);
  console.log(`  Spacing:          ${stats.spacing}`);
  console.log(`  Border radius:    ${stats.radius}`);
  console.log(`  Font sizes:       ${stats.fontSize}`);
  console.log(`  Font weights:     ${stats.fontWeight}`);
  console.log(`  Font families:    ${stats.fontFamily}`);
  console.log(`  Light semantic:   ${stats.lightSemanticColors}`);
  console.log(`  Dark semantic:    ${stats.darkSemanticColors}`);
  console.log(`\nOutput written to: ${OUTPUT_FILE}`);
}

// Run transformation
transform();
