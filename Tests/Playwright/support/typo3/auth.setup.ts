import { test as setup } from '@playwright/test';
import { BackendPage } from './backend.page';
import { typo3AuthStateFile } from './environment';

// Matches this repo's .ddev/.setup admin bootstrap
// (.ddev/docker-compose.typo3-setup.yaml's TYPO3_SETUP_ADMIN_PASSWORD).
const USERNAME = 'admin';
const PASSWORD = 'Password1!';

setup('authenticate as backend admin', async ({ page }) => {
  const backend = new BackendPage(page);
  await backend.login(USERNAME, PASSWORD);
  await page.context().storageState({ path: typo3AuthStateFile() });
});
