import { test, expect } from '@playwright/test';

const DISABLED_CLASS = /frontend-edit__sticky-toolbar--disabled/;

test('sticky toolbar renders and the toggle persists across reload', async ({ page }) => {
  await page.goto('/');

  const toolbar = page.locator('.frontend-edit__sticky-toolbar');
  await expect(toolbar).toBeVisible();
  await expect(toolbar).not.toHaveClass(DISABLED_CLASS);

  const toggleButton = toolbar.locator('.frontend-edit__sticky-btn--toggle');

  // Disable frontend editing. The client reloads the page itself on success.
  await Promise.all([
    page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/toggle')),
    toggleButton.click(),
  ]);
  await page.waitForLoadState('load');
  await expect(page.locator('.frontend-edit__sticky-toolbar')).toHaveClass(DISABLED_CLASS);

  // Prove persistence: reload again without touching the toggle.
  await page.reload();
  await expect(page.locator('.frontend-edit__sticky-toolbar')).toHaveClass(DISABLED_CLASS);

  // Restore: re-enable so later specs run with frontend edit active.
  await Promise.all([
    page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/toggle')),
    page.locator('.frontend-edit__sticky-btn--toggle').click(),
  ]);
  await page.waitForLoadState('load');
  await expect(page.locator('.frontend-edit__sticky-toolbar')).not.toHaveClass(DISABLED_CLASS);
});
