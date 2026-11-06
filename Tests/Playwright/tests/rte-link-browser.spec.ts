import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';
import { resolveTypo3Version } from '../support/typo3/environment';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

test('link browser applies a page link to the RTE selection', async ({ page }) => {
  const pageErrors: string[] = [];
  page.on('pageerror', (error) => pageErrors.push(error.message));

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);
  await hoverMenu.editButton(CONTENT_ELEMENT_UID).click();

  // v13 loads the iframe modal, v14 the contextual sidebar. Both host the plain FormEngine in an iframe.
  const editFrame = page.frameLocator('iframe[src*="/typo3/record/edit"]');
  const editor = editFrame.locator('.ck-editor__editable').first();
  await editor.click();
  await page.keyboard.press('ControlOrMeta+a');
  await editFrame.locator('.ck-toolbar button:has-text("Link")').first().click();

  const linkBrowser = editFrame.frameLocator('iframe[src*="/typo3/rte/wizard/browselinks"]');
  const homeNode = linkBrowser.locator('.node', { has: page.locator('.node-name', { hasText: /^Home/ }) }).first();
  await homeNode.hover();
  await homeNode.locator('.node-action').click();

  await expect(editFrame.locator('.ck-editor__editable a[href="t3://page?uid=1"]').first()).toBeVisible();
  // TYPO3 13.4 keeps the modal element after applying a link, also in the plain backend.
  if (resolveTypo3Version() !== '13') {
    await expect(editFrame.locator('iframe[src*="/typo3/rte/wizard/browselinks"]')).toBeHidden();
  }
  expect(pageErrors).toEqual([]);
});
