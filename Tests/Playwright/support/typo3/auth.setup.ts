import { test as setup } from '@playwright/test';
import { BackendPage } from './backend.page';

const TYPO3_VERSION = process.env.TYPO3_VERSION || '13';
// Must match playwright.config.ts's AUTH_FILE — namespaced by version so
// switching TYPO3_VERSION doesn't clobber the other version's saved session.
const AUTH_FILE = `.auth/state-${TYPO3_VERSION}.json`;

setup('authenticate as backend admin', async ({ page }) => {
  const backend = new BackendPage(page);
  await backend.login('admin', 'Password1!');
  await page.context().storageState({ path: AUTH_FILE });
});
