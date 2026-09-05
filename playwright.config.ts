import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E Test Configuration
 *
 * @see https://playwright.dev/docs/test-configuration
 */
let rawBaseURL = process.env.PLAYWRIGHT_BASE_URL || 'https://wordpress.local';

let baseURL: URL;
try {
  baseURL = new URL(rawBaseURL);
} catch {
  // eslint-disable-next-line no-console -- deliberate config-load diagnostic
  console.warn(
    `[playwright.config] Invalid PLAYWRIGHT_BASE_URL "${rawBaseURL}", falling back to https://wordpress.local`
  );
  rawBaseURL = 'https://wordpress.local';
  baseURL = new URL(rawBaseURL);
}

// Local by Flywheel uses a self-signed certificate for the local site;
// only ignore HTTPS errors against a known-local host, never against a real one.
const isLocalHost =
  baseURL.hostname.endsWith('.local') ||
  baseURL.hostname === 'localhost' ||
  baseURL.hostname === '127.0.0.1';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['html', { outputFolder: 'playwright-report' }], ['list']],

  use: {
    // Base URL for the local WordPress site (Local by Flywheel default)
    baseURL: rawBaseURL,

    ignoreHTTPSErrors: isLocalHost,

    // Collect trace when retrying the failed test
    trace: 'on-first-retry',

    // Take screenshot on failure
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    // Mobile viewports
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  // Run local dev server before starting tests (optional)
  // webServer: {
  //   command: 'npm run dev',
  //   url: 'http://localhost:5180',
  //   reuseExistingServer: !process.env.CI,
  // },
});
