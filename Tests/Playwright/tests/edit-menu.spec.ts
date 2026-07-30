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
  const toolbar = hoverMenu.toolbar(CONTENT_ELEMENT_UID);

  // The toolbar is always in the DOM; OverlayManager toggles it via opacity/
  // pointer-events on the TOOLBAR element itself (see
  // Resources/Public/Css/FrontendEdit.css: `.frontend-edit__toolbar { opacity: 0; }`,
  // `.frontend-edit__overlay--active .frontend-edit__toolbar { opacity: 1; }`) —
  // not display/visibility, and not on the child buttons. Two consequences:
  // (1) Playwright's toBeVisible()/toBeHidden() only check display/visibility +
  // bounding box, so they can't tell the two opacity states apart — assert
  // toHaveCSS('opacity', ...) instead. (2) CSS `opacity` is NOT inherited into
  // a descendant's own computed style (verified live: the toolbar reports
  // opacity 0 while its child edit button independently reports opacity 1) —
  // so the assertion must target the toolbar element, not editButton()/kebabButton().
  await expect(toolbar).toHaveCSS('opacity', '0');

  await hoverMenu.hover(CONTENT_ELEMENT_UID);
  await expect(toolbar).toHaveCSS('opacity', '1');

  await hoverMenu.kebabButton(CONTENT_ELEMENT_UID).click();
  const dropdown = hoverMenu.dropdown(CONTENT_ELEMENT_UID);
  await expect(dropdown).toBeVisible();
  await expect(dropdown.locator('[role="menuitem"]').first()).toBeVisible();
  await expect(dropdown.locator('.hide')).toBeVisible();
  await expect(dropdown.locator('.delete')).toBeVisible();
});
