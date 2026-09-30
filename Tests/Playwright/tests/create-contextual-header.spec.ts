import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';
import { resolveTypo3Version } from '../support/typo3/environment';

// First text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 1;

test('new content form in the modal uses the contextual sidebar header', async ({ page }) => {
  // Only v14.2+ splits edit (contextual sidebar) and create (iframe modal),
  // see applyContextualHeader() in iframe_edit.js. On v13 the modal keeps
  // the full docheader for both flows.
  test.skip(resolveTypo3Version() === '13', 'contextual sidebar requires TYPO3 v14.2+');

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);
  await page.locator(`.frontend-edit__insert-btn--after[href*="afterUid=${CONTENT_ELEMENT_UID}"]`).first().click();

  const modal = page.frameLocator('.frontend-edit__modal iframe');
  await modal.getByRole('button', { name: /Header Only/ }).click();

  const header = modal.locator('.module-docheader-wrapper .contextual-record-edit-header');
  await expect(header).toBeVisible();
  await expect(header.locator('.contextual-record-edit-title')).toHaveText(/Create new/);
  await expect(header.locator('.contextual-record-edit-actions [name="_savedok"].btn-primary')).toBeVisible();
  await expect(header.locator('.contextual-record-edit-actions [name="_saveandclosedok"]')).toBeVisible();
  await expect(header.locator('.contextual-record-edit-actions .t3js-editform-close')).toBeVisible();

  await expect(header.locator('.contextual-record-edit-actions [name="_saveandclosedok"]')).toHaveText('Save & Close');

  await expect(modal.locator('.t3js-module-docheader-navigation')).toBeHidden();
  await expect(modal.locator('#EditDocumentController h1')).toHaveCount(0);
  // The expand action moves next to the title, as in the sidebar.
  await expect(page.locator('.frontend-edit__modal-expand')).toBeHidden();

  await header.locator('.contextual-record-edit-title-group button').click();
  await page.waitForURL((url) => url.pathname.includes('/typo3/record/edit'));
});
