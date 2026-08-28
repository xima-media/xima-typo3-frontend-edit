import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';
import { resolveTypo3Version } from '../support/typo3/environment';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

// The ddev demo site sets frontendEdit.enableContextualEditing: true, so a
// real backend edit URL (reused here purely as "some real backend URL" -
// openBackendView doesn't care what it opens) is available via the edit
// button. On v13 this must resolve to the iframe modal (see the
// FRONTEND_EDIT_SIDEBAR_EDIT gate fix in PublicApi.openBackendView); on
// v14.2+ it resolves to the contextual sidebar.
async function getBackendUrl(page: import('@playwright/test').Page): Promise<string> {
  const hoverMenu = new HoverMenu(page);
  const href = await hoverMenu.editButton(CONTENT_ELEMENT_UID).getAttribute('href');
  if (!href) throw new Error('edit button has no href');
  return href;
}

test.beforeEach(async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;
});

test('openBackendView opens the v13 modal, not the sidebar, with a custom title and width', async ({ page }) => {
  test.skip(resolveTypo3Version() !== '13', 'asserts the v13 iframe-modal path specifically');

  const url = await getBackendUrl(page);
  await page.evaluate((backendUrl) => {
    (window as any).XimaFrontendEdit.openBackendView(backendUrl, { title: 'Custom title', width: '500px' });
  }, url);

  const modal = page.locator('.frontend-edit__modal');
  await expect(modal).toHaveClass(/frontend-edit__modal--open/);
  // Proves the FRONTEND_EDIT_SIDEBAR_EDIT gate fix: contextual editing is
  // enabled in this environment, but on v13 the sidebar must never open.
  await expect(page.locator('.frontend-edit__sidebar')).not.toHaveClass(/frontend-edit__sidebar--open/);

  const title = page.locator('.frontend-edit__modal-title');
  await expect(title).toHaveText('Custom title');
  // Not asserting toBeVisible(): .frontend-edit__modal-header is currently
  // display:none on main regardless of this element's own style (a
  // pre-existing gap, already fixed on the separate, not-yet-merged
  // feature/iframe-modal-expand-button branch). Assert our own contract -
  // Modal.open() does not itself force the title hidden - instead.
  expect(await title.evaluate((el) => (el as HTMLElement).style.display)).not.toBe('none');
  await expect(page.locator('.frontend-edit__modal-panel')).toHaveCSS('width', '500px');
});

test('openBackendView: onClose fires and reloadOnClose:false does not reload the parent', async ({ page }) => {
  test.skip(resolveTypo3Version() !== '13', 'exercises the v13 iframe-modal close path');

  const url = await getBackendUrl(page);
  await page.evaluate((backendUrl) => {
    (window as any).__closeReasons = [];
    (window as any).__marker = 'still-here';
    (window as any).XimaFrontendEdit.openBackendView(backendUrl, {
      title: 'X',
      reloadOnClose: false,
      onClose: (detail: { reason: string }) => (window as any).__closeReasons.push(detail.reason),
    });
  }, url);

  await expect(page.locator('.frontend-edit__modal')).toHaveClass(/frontend-edit__modal--open/);
  // Escape rather than clicking .frontend-edit__modal-close: that button
  // lives inside .frontend-edit__modal-header, currently display:none on
  // main regardless of this test (see the title assertion above) - Escape
  // is a real, always-available close path (global keydown listener in
  // iframe_edit.js's getOrCreate()).
  await page.keyboard.press('Escape');

  // Modal.close()'s cleanup (where onClose fires) runs after the CSS
  // transition (ANIMATION_DURATION_MS = 300ms in iframe_edit.js).
  await page.waitForTimeout(500);

  expect(await page.evaluate(() => (window as any).__closeReasons)).toEqual(['close']);
  // If a reload had happened, this fresh page load would not have the marker.
  expect(await page.evaluate(() => (window as any).__marker)).toBe('still-here');
});

test('openBackendView: reloadOnClose defaults to true', async ({ page }) => {
  test.skip(resolveTypo3Version() !== '13', 'exercises the v13 iframe-modal close path');

  const url = await getBackendUrl(page);
  await page.evaluate((backendUrl) => {
    (window as any).__marker = 'still-here';
    (window as any).XimaFrontendEdit.openBackendView(backendUrl, { title: 'X' });
  }, url);

  await expect(page.locator('.frontend-edit__modal')).toHaveClass(/frontend-edit__modal--open/);

  // See the previous test for why Escape, not a click on the (currently
  // hidden) close button.
  await Promise.all([
    page.waitForEvent('load'),
    page.keyboard.press('Escape'),
  ]);

  // A fresh page load has no marker - proves the parent actually reloaded.
  expect(await page.evaluate(() => (window as any).__marker)).toBeUndefined();
});

test('openBackendView: linkPolicy governs link clicks inside the embedded document', async ({ page }) => {
  test.skip(resolveTypo3Version() !== '13', 'exercises the v13 iframe-modal link-policy hook');

  const url = await getBackendUrl(page);
  await page.evaluate((backendUrl) => {
    (window as any).__marker = 'still-here';
    (window as any).XimaFrontendEdit.openBackendView(backendUrl, {
      title: 'X',
      linkPolicy: [
        { match: '#close-me', action: 'close' },
        { match: '#external', action: 'external' },
      ],
    });
  }, url);

  const frame = page.frameLocator('.frontend-edit__modal iframe');

  // Wait for the actual edit form to finish loading before touching its
  // document - injecting into the transient about:blank/loading state would
  // get wiped the moment the real navigation completes.
  await expect(frame.locator('[name="_savedok"]')).toBeVisible();

  // Inject deterministic test links instead of depending on which incidental
  // links a real edit form happens to render.
  await frame.locator('body').evaluate((body) => {
    const close = document.createElement('a');
    close.href = '#close-me';
    close.textContent = 'close-me';
    close.id = 'xfe-test-close-link';
    body.appendChild(close);
  });

  // A native .click() call, not a synthetic pointer click: CKEditor's
  // inspector panel (auto-attached whenever BE/debug is on, as it is in this
  // dev environment) docks over the form and can occlude the injected link
  // at its screen coordinates - even Playwright's `force: true` still
  // dispatches at those coordinates and would hit the inspector instead.
  // Calling .click() on the element directly sidesteps hit-testing entirely.
  await Promise.all([
    page.waitForEvent('load'),
    frame.locator('#xfe-test-close-link').evaluate((el: HTMLElement) => el.click()),
  ]);

  // 'close' action closed the modal and (default reloadOnClose) reloaded -
  // proven the same way as the reloadOnClose test above.
  expect(await page.evaluate(() => (window as any).__marker)).toBeUndefined();
});

test('openBackendView opens the v14.2+ contextual sidebar, not the modal', async ({ page }) => {
  test.skip(resolveTypo3Version() !== '14', 'asserts the v14.2+ sidebar path specifically');

  const url = await getBackendUrl(page);
  await page.evaluate((backendUrl) => {
    (window as any).XimaFrontendEdit.openBackendView(backendUrl, { title: 'Custom title', width: '500px' });
  }, url);

  await expect(page.locator('.frontend-edit__sidebar')).toHaveClass(/frontend-edit__sidebar--open/);
  // iframe_edit.js (the v13 modal) isn't even loaded on v14.2+ - the modal
  // element never exists, let alone opens.
  await expect(page.locator('.frontend-edit__modal')).toHaveCount(0);
  await expect(page.locator('.frontend-edit__sidebar')).toHaveCSS('width', '500px');
});
