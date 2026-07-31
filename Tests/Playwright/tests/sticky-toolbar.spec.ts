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

    // waitForLoadState('load') alone can resolve against the page's current
    // (pre-reload) state rather than the upcoming one — wait for the actual
    // future 'load' event registered concurrently with the click instead.
    await Promise.all([
      page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/toggle')),
      page.waitForEvent('load'),
      page.locator('.frontend-edit__sticky-btn--toggle').click(),
    ]);
  });

  test('renders and the toggle persists across reload', async ({ page }) => {
    await page.goto('/');

    const toolbar = page.locator('.frontend-edit__sticky-toolbar');
    await expect(toolbar).toBeVisible();
    await expect(toolbar).not.toHaveClass(DISABLED_CLASS);

    const toggleButton = toolbar.locator('.frontend-edit__sticky-btn--toggle');

    // Disable frontend editing. The client reloads the page itself on success.
    // Wait for the actual future 'load' event (registered concurrently with
    // the click) rather than waitForLoadState('load') after the fact, which
    // can resolve against the page's current state instead of the reload.
    await Promise.all([
      page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/toggle')),
      page.waitForEvent('load'),
      toggleButton.click(),
    ]);
    await expect(page.locator('.frontend-edit__sticky-toolbar')).toHaveClass(DISABLED_CLASS);

    // Prove persistence: reload again without touching the toggle.
    await page.reload();
    await expect(page.locator('.frontend-edit__sticky-toolbar')).toHaveClass(DISABLED_CLASS);

    // afterEach re-enables it — no need to duplicate that here.
  });

  test('the page options menu follows the APG menu-button pattern: aria-expanded, arrow-key navigation, Escape and focus-out all work', async ({ page }) => {
    await page.goto('/');

    const menuBtn = page.locator('.frontend-edit__sticky-btn--menu');
    const dropdown = page.locator('.frontend-edit__sticky-dropdown');
    const items = dropdown.locator('[role="menuitem"]');

    await expect(menuBtn).toHaveAttribute('aria-haspopup', 'menu');
    await expect(menuBtn).toHaveAttribute('aria-expanded', 'false');
    await expect(dropdown).toHaveAttribute('role', 'menu');

    // Opening focuses the first item - the roving tabindex="-1" on every item
    // means Tab alone could never reach the menu otherwise.
    await menuBtn.click();
    await expect(menuBtn).toHaveAttribute('aria-expanded', 'true');
    await expect(items.first()).toBeFocused();

    await page.keyboard.press('ArrowDown');
    await expect(items.nth(1)).toBeFocused();

    await page.keyboard.press('ArrowUp');
    await expect(items.first()).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(dropdown).not.toHaveClass(/frontend-edit__sticky-dropdown--visible/);
    await expect(menuBtn).toHaveAttribute('aria-expanded', 'false');
    await expect(menuBtn).toBeFocused();

    // Re-open, then move focus out via keyboard (not a click) - the menu must
    // close itself once focus actually leaves it, since Tab never lands on
    // one of its own (tabindex="-1") items.
    await menuBtn.click();
    await expect(items.first()).toBeFocused();
    await page.locator('a[href="/about-us"]').first().focus();
    await expect(dropdown).not.toHaveClass(/frontend-edit__sticky-dropdown--visible/);
  });
});
