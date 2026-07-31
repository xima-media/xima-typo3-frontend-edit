import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

test('expand button opens the current edit URL in the full backend, same tab', async ({ page, context }) => {
  // See edit-menu.spec.ts: the listener must be registered before goto(), not after.
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);
  await hoverMenu.editButton(CONTENT_ELEMENT_UID).click();

  // On v13 (no contextual-edit route) this is the iframe_edit.js modal — see
  // edit-action.spec.ts for why the same demo config resolves to the modal
  // rather than the contextual sidebar.
  const editIframe = page.locator('iframe[src*="/typo3/record/edit"]');
  await expect(editIframe).toHaveCount(1);

  const expandButton = page.locator('.frontend-edit__modal-expand');
  await expect(expandButton).toBeVisible();

  await expandButton.click();

  // Same tab: no popup window was opened, and the top-level page itself now
  // shows the backend edit form (the modal's escape hatch, not a new one).
  expect(context.pages()).toHaveLength(1);
  await page.waitForURL((url) => url.pathname.includes('/typo3/record/edit'));
  expect(decodeURIComponent(page.url())).toContain(`edit[tt_content][${CONTENT_ELEMENT_UID}]=edit`);

  // The extension's own "Save & Close" marker must not leak into the full
  // backend view (see buildExpandUrl in iframe_edit.js).
  const url = new URL(page.url());
  expect(url.searchParams.has('tx_ximatypo3frontendedit')).toBe(false);
});
