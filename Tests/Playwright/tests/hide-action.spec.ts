import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';
import { restoreHiddenContentElement } from '../support/frontend-edit/fixtures';

// "Quick Links" bullets element on the Home page — dedicated to this spec so it
// doesn't collide with the uid=2 element used by edit-menu.spec.ts / edit-action.spec.ts.
const CONTENT_ELEMENT_UID = 5;

test.describe('hide action', () => {
  test.afterEach(() => {
    restoreHiddenContentElement(CONTENT_ELEMENT_UID);
  });

  test('hides the content element and redirects back to the page', async ({ page }) => {
    // See edit-menu.spec.ts: the listener must be registered before goto(), not after.
    const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
    await page.goto('/');
    await editInfoResponse;

    const hoverMenu = new HoverMenu(page);
    await hoverMenu.openDropdown(CONTENT_ELEMENT_UID);

    await Promise.all([
      page.waitForURL('**/'),
      hoverMenu.dropdown(CONTENT_ELEMENT_UID).locator('.hide').click(),
    ]);

    await expect(page.locator(`#c${CONTENT_ELEMENT_UID}`)).toHaveCount(0);
  });
});
