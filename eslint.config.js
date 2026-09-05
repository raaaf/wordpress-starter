import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import prettierConfig from 'eslint-config-prettier';
import prettierPlugin from 'eslint-plugin-prettier';

export default tseslint.config(
  eslint.configs.recommended,
  ...tseslint.configs.recommended,
  prettierConfig,
  {
    plugins: {
      prettier: prettierPlugin,
    },
    languageOptions: {
      parserOptions: {
        project: './tsconfig.json',
      },
      globals: {
        wp: 'readonly',
        Alpine: 'readonly',
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        setInterval: 'readonly',
        clearInterval: 'readonly',
        requestAnimationFrame: 'readonly',
        cancelAnimationFrame: 'readonly',
        fetch: 'readonly',
        URL: 'readonly',
        URLSearchParams: 'readonly',
        FormData: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        navigator: 'readonly',
        location: 'readonly',
        history: 'readonly',
        Event: 'readonly',
        CustomEvent: 'readonly',
        HTMLElement: 'readonly',
        Element: 'readonly',
        Node: 'readonly',
        NodeList: 'readonly',
        DOMParser: 'readonly',
        MutationObserver: 'readonly',
        IntersectionObserver: 'readonly',
        ResizeObserver: 'readonly',
      },
    },
    rules: {
      'prettier/prettier': 'error',
      'no-console': 'warn',
      'no-debugger': 'error',
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      '@typescript-eslint/no-explicit-any': 'warn',
      'prefer-const': 'error',
      'no-var': 'error',
    },
  },
  {
    files: ['**/*.js'],
    rules: {
      '@typescript-eslint/no-require-imports': 'off',
    },
  },
  {
    // E2E specs log axe violations to the console when a check fails, as a
    // deliberate diagnostic right before the assertion. Allow console here.
    files: ['tests/e2e/**/*.ts'],
    rules: {
      'no-console': 'off',
    },
  },
  {
    // Node-side build/CLI scripts, run outside the browser: no type-aware
    // parserOptions.project (they aren't part of tsconfig.json). Flat config
    // merges `languageOptions.globals` across matching blocks rather than
    // replacing it, so these Node globals are added on top of the browser
    // globals declared above, not instead of them.
    files: ['scripts/**/*.js'],
    languageOptions: {
      parserOptions: {
        project: null,
      },
      globals: {
        process: 'readonly',
        console: 'readonly',
        __dirname: 'readonly',
        __filename: 'readonly',
        Buffer: 'readonly',
      },
    },
  },
  {
    // vite.config.js and eslint.config.js are plain Node config files, not
    // part of tsconfig.json's `include`: no type-aware parserOptions.project.
    files: ['vite.config.js', 'eslint.config.js'],
    languageOptions: {
      parserOptions: {
        project: null,
      },
      globals: {
        process: 'readonly',
        console: 'readonly',
        __dirname: 'readonly',
        __filename: 'readonly',
        Buffer: 'readonly',
      },
    },
  },
  {
    // playwright.config.ts and vitest.config.ts are part of tsconfig.json's
    // `include`, so they are type-checked by `tsc --noEmit`; lint them too so
    // they don't get type-checked but never linted. They run under Node, not
    // the browser, so they need Node globals rather than the browser set above.
    files: ['playwright.config.ts', 'vitest.config.ts'],
    languageOptions: {
      globals: {
        process: 'readonly',
      },
    },
  },
  {
    ignores: ['dist/**', 'compiled/**', 'node_modules/**', 'vendor/**'],
  }
);
