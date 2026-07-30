import { defineConfig, devices } from '@playwright/test';

const TYPO3_VERSION = process.env.TYPO3_VERSION || '13';
const BASE_URL = `https://${TYPO3_VERSION}.xima-typo3-frontend-edit.ddev.site`;
// Namespaced by version: switching TYPO3_VERSION must not clobber the other
// version's saved backend session (13.* and 14.* are different origins/cookies).
const AUTH_FILE = `.auth/state-${TYPO3_VERSION}.json`;

export default defineConfig({
  testDir: '.',
  fullyParallel: false,
  workers: 1,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['html', { open: 'never' }], ['list']] : 'list',
  use: {
    baseURL: BASE_URL,
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'setup',
      testMatch: 'support/typo3/auth.setup.ts',
    },
    {
      name: 'chromium',
      testMatch: 'tests/**/*.spec.ts',
      use: { ...devices['Desktop Chrome'], storageState: AUTH_FILE },
      dependencies: ['setup'],
    },
  ],
});
