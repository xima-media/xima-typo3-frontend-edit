import { test as setup } from '@playwright/test';
import { BackendPage } from './backend.page';

const AUTH_FILE = '.auth/state.json';

setup('authenticate as backend admin', async ({ page }) => {
  const backend = new BackendPage(page);
  await backend.login('admin', 'Password1!');
  await page.context().storageState({ path: AUTH_FILE });
});
