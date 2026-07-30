import { test, expect } from '@playwright/test';

const DISABLED_CLASS = /frontend-edit__sticky-toolbar--disabled/;

test.describe('sticky toolbar', () => {
  // The toggle persists in the backend user's uc, shared across every spec
  // and every future run. If the test below throws between disabling and
  // re-enabling, frontend edit would stay disabled forever after — this
  // afterEach guarantees it ends up enabled regardless of how the test
  // finished, not just on the happy path.
  test.afterEach(async ({ page }) => {
    await page.goto('/');
    const isDisabled = (await page.locator('#frontend-edit-toolbar-config').getAttribute('data-disabled')) === 'true';
    if (!isDisabled) return;

    await Promise.all([
      page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/toggle')),
      page.locator('.frontend-edit__sticky-btn--toggle').click(),
    ]);
    await page.waitForLoadState('load');
  });

  test('renders and the toggle persists across reload', async ({ page }) => {
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

    // afterEach re-enables it — no need to duplicate that here.
  });
});
