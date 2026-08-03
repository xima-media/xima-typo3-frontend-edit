import { test, expect } from '@playwright/test';
import { runSql } from '../support/typo3/environment';

// sys_category: a real, always-available core table with TCA (ctrl.label,
// ctrl.languageField/transOrigPointerField) - used here purely as "some
// foreign table with no EXT:news dependency", not because categories are a
// realistic frontend-edit use case. uid 9001 is well outside the fixture
// range used elsewhere so it can't collide with anything else on the page.
const CATEGORY_UID = 9001;
const CATEGORY_TRANSLATION_UID = 9002;
const CATEGORY_TABLE = 'sys_category';
const CATEGORY_KEY = `${CATEGORY_TABLE}:${CATEGORY_UID}`;

test.beforeAll(() => {
  runSql(`INSERT INTO sys_category (uid, pid, title, sys_language_uid, l10n_parent) VALUES (${CATEGORY_UID}, 1, 'Playwright Test Category', 0, 0);`);
  runSql(`INSERT INTO sys_category (uid, pid, title, sys_language_uid, l10n_parent) VALUES (${CATEGORY_TRANSLATION_UID}, 1, 'Playwright Test Category (translated)', 1, ${CATEGORY_UID});`);
});

test.afterAll(() => {
  runSql(`DELETE FROM sys_category WHERE uid IN (${CATEGORY_UID}, ${CATEGORY_TRANSLATION_UID});`);
});

async function injectRecordAttributeElement(page: import('@playwright/test').Page): Promise<void> {
  await page.addInitScript(
    ({ elementId, key }) => {
      document.addEventListener('DOMContentLoaded', () => {
        const el = document.createElement('div');
        el.id = elementId;
        el.setAttribute('data-frontend-edit', key);
        el.textContent = 'Foreign record test element';
        document.body.prepend(el);
      });
    },
    { elementId: 'xfe-test-record-target', key: CATEGORY_KEY },
  );
}

test('a foreign record gets a thin edit+info+history menu, no hide/delete/move', async ({ page }) => {
  await injectRecordAttributeElement(page);

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const toolbar = page.locator(`.frontend-edit__toolbar[data-cid="${CATEGORY_KEY}"]`);
  await expect(toolbar).toHaveCount(1);
  // Not gated by hover, but not shown either until interacted with - same
  // hover-driven opacity mechanism as tt_content.
  await expect(toolbar).toHaveCSS('opacity', '0');

  await page.hover('#xfe-test-record-target', { force: true });
  await expect(toolbar).toHaveCSS('opacity', '1');

  await toolbar.locator('.frontend-edit__btn--kebab').click();
  const dropdown = page.locator(`.frontend-edit__dropdown[data-cid="${CATEGORY_KEY}"]`);
  await expect(dropdown).toBeVisible();

  await expect(dropdown.locator('.edit')).toHaveCount(1);
  await expect(dropdown.locator('.info')).toHaveCount(1);
  await expect(dropdown.locator('.history')).toHaveCount(1);
  await expect(dropdown.locator('.hide')).toHaveCount(0);
  await expect(dropdown.locator('.delete')).toHaveCount(0);
  await expect(dropdown.locator('.move')).toHaveCount(0);

  const editHref = await dropdown.locator('.edit').getAttribute('href');
  expect(decodeURIComponent(editHref ?? '')).toContain(`edit[${CATEGORY_TABLE}][${CATEGORY_UID}]=edit`);
});

test('a foreign record has no drag handle (composite keys never collide with tt_content drag & drop)', async ({ page }) => {
  await injectRecordAttributeElement(page);

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await expect(page.locator(`.frontend-edit__toolbar[data-cid="${CATEGORY_KEY}"]`)).toHaveCount(1);
  await expect(page.locator(`.frontend-edit__toolbar[data-cid="${CATEGORY_KEY}"] .frontend-edit__btn--drag`)).toHaveCount(0);
});

test('a foreign record resolves to its translation when requested in a non-default language', async ({ page }) => {
  await injectRecordAttributeElement(page);

  // This ddev demo site has no second site language configured (see
  // translated-page.spec.ts), so a real ?L=1 navigation can't be used here.
  // AjaxController::editInformationAction() reads "language" purely as a
  // plain query parameter, with no site-routing dependency - overriding the
  // toolbar config's data-language attribute (which DataService.fetchContentElements()
  // forwards verbatim) exercises the exact same PHP-side resolution path.
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      const config = document.getElementById('frontend-edit-toolbar-config');
      if (config) config.dataset.language = '1';
    });
  });

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const toolbar = page.locator(`.frontend-edit__toolbar[data-cid="${CATEGORY_KEY}"]`);
  await expect(toolbar).toHaveCount(1);

  await page.hover('#xfe-test-record-target', { force: true });
  await toolbar.locator('.frontend-edit__btn--kebab').click();

  // The response key stays the requested "table:uid" (uid 9001, so the DOM
  // attribute lookup still matches), but the edit link targets the resolved
  // translation's own uid (9002) - analogous to tt_content's connected mode.
  const editHref = await page.locator(`.frontend-edit__dropdown[data-cid="${CATEGORY_KEY}"] .edit`).getAttribute('href');
  expect(decodeURIComponent(editHref ?? '')).toContain(`edit[${CATEGORY_TABLE}][${CATEGORY_TRANSLATION_UID}]=edit`);
});

test('an unknown table is silently omitted from the menu (no error, no crash)', async ({ page }) => {
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      const el = document.createElement('div');
      el.id = 'xfe-test-unknown-table';
      el.setAttribute('data-frontend-edit', 'tx_totally_unknown_table:1');
      el.textContent = 'Unknown table test element';
      document.body.prepend(el);
    });
  });

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await expect(page.getByText('Initialization error')).toHaveCount(0);
  await expect(page.locator('.frontend-edit__toolbar[data-cid="tx_totally_unknown_table:1"]')).toHaveCount(0);
});
