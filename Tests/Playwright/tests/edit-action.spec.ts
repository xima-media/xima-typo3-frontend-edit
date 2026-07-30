import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

test('edit action opens FormEngine for exactly the hovered record, same tab', async ({ page, context }) => {
  // See edit-menu.spec.ts: the listener must be registered before goto(), not after.
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);

  // The ddev demo site sets frontendEdit.enableContextualEditing: true
  // (.ddev/.setup/templates/config/sites/main/settings.yaml), so the edit
  // button's click is intercepted (UI.createEditButton()'s openContextualEdit
  // call in frontend_edit.js) and loads FormEngine into an iframe modal
  // instead of navigating the top-level page. page.url() never changes —
  // assert against the iframe's src instead. This is a real .click(), not a
  // simulated navigation: it exercises the actual interception path.
  await hoverMenu.editButton(CONTENT_ELEMENT_UID).click();

  const editIframe = page.locator('iframe[src*="/typo3/record/edit"]');
  await expect(editIframe).toHaveCount(1);

  // Same tab: no popup window was opened.
  expect(context.pages()).toHaveLength(1);

  const src = await editIframe.getAttribute('src');
  const url = new URL(src ?? '', page.url());
  expect(url.pathname).toContain('/typo3/record/edit');
  // Decode so the assertion reads the logical param shape, not its URL-encoded form.
  expect(decodeURIComponent(url.search)).toContain(`edit[tt_content][${CONTENT_ELEMENT_UID}]=edit`);
});
