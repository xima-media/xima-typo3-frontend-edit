import { defineConfig, devices, type PlaywrightTestConfig } from '@playwright/test';
import { resolveTypo3Version, typo3AuthStateFile, typo3BaseUrl } from './environment';

export interface Typo3PlaywrightConfigOptions {
  /** ddev hostname suffix this suite's per-version sites are registered under, e.g. "xima-typo3-frontend-edit.ddev.site". */
  hostname: string;
}

/**
 * TYPO3-generic Playwright config factory — the extraction candidate for a
 * future shared typo3-playwright package. Wires up the version-parametrized
 * baseURL/storageState convention (see environment.ts) and the two-project
 * login-then-test structure.
 */
export function defineTypo3PlaywrightConfig(options: Typo3PlaywrightConfigOptions): PlaywrightTestConfig {
  const version = resolveTypo3Version();

  return defineConfig({
    testDir: '.',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['html', { open: 'never' }], ['list']] : 'list',
    use: {
      baseURL: typo3BaseUrl(options.hostname, version),
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
        use: { ...devices['Desktop Chrome'], storageState: typo3AuthStateFile(version) },
        dependencies: ['setup'],
      },
    ],
  });
}
