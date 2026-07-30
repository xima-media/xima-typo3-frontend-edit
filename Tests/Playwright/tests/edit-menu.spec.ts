import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

test('edit menu opens on hover with the default actions', async ({ page }) => {
  // Start listening BEFORE navigating: the editInformation fetch fires during
  // FrontendEdit.bootstrap() (on DOMContentLoaded), which can complete before
  // page.goto()'s default 'load' wait resolves. Awaiting goto() first would
  // race past the response and hang for the full timeout.
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);

  // Before hovering, the toolbar for this element must not be visible.
  await expect(hoverMenu.editButton(CONTENT_ELEMENT_UID)).toBeHidden();

  await hoverMenu.hover(CONTENT_ELEMENT_UID);
  await expect(hoverMenu.editButton(CONTENT_ELEMENT_UID)).toBeVisible();
  await expect(hoverMenu.kebabButton(CONTENT_ELEMENT_UID)).toBeVisible();

  await hoverMenu.kebabButton(CONTENT_ELEMENT_UID).click();
  const dropdown = hoverMenu.dropdown(CONTENT_ELEMENT_UID);
  await expect(dropdown).toBeVisible();
  await expect(dropdown.locator('[role="menuitem"]').first()).toBeVisible();
  await expect(dropdown.locator('.hide')).toBeVisible();
  await expect(dropdown.locator('.delete')).toBeVisible();
});
