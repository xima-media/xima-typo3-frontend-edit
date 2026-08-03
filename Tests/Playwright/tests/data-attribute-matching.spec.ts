import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page, with its usual id="c2"
// anchor (Tests/Acceptance/Fixtures/demo-content.sql).
const ANCHOR_UID = 2;

// "Our Mission" - lives on the About Us page (pid=2, see onepager.spec.ts) and
// has no id="c7" anchor anywhere on the Home page. Used here purely as a
// real, fetchable tt_content record with no anchor already competing for the
// data-attribute matching under test - fetchContentElementsByUids() (the
// uid-based path collectDataItems() triggers) isn't scoped to a single page.
const DATA_ATTRIBUTE_ONLY_UID = 7;

async function injectDataAttributeElement(page: import('@playwright/test').Page, elementId: string, uid: number): Promise<void> {
  await page.addInitScript(
    ({ elementId, uid }) => {
      // Registered before navigation, listening for DOMContentLoaded - which
      // fires before frontend_edit.js's own DOMContentLoaded-driven bootstrap()
      // (FrontendEdit.init() registers its listener afterwards, since its
      // <script> tag parses later), so the element exists in time for
      // DataService.collectDataItems()'s DOM scan.
      document.addEventListener('DOMContentLoaded', () => {
        const el = document.createElement('div');
        el.id = elementId;
        el.setAttribute('data-frontend-edit', `tt_content:${uid}`);
        el.textContent = 'Data-attribute test element';
        // Prepended, not appended: a fixed-position cookie-consent banner
        // sits at the bottom of the page and would otherwise sit on top of
        // (and intercept real hit-testing/hover for) an element appended at
        // the end of body.
        document.body.prepend(el);
      });
    },
    { elementId, uid },
  );
}

test('an element with only data-frontend-edit receives an overlay/menu, without an id="c{uid}" anchor', async ({ page }) => {
  await injectDataAttributeElement(page, 'xfe-test-data-attribute-target', DATA_ATTRIBUTE_ONLY_UID);

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const toolbar = page.locator(`.frontend-edit__toolbar[data-cid="${DATA_ATTRIBUTE_ONLY_UID}"]`);
  await expect(toolbar).toHaveCount(1);
  await expect(toolbar).toHaveCSS('opacity', '0');

  // Hovering the injected element itself activates its toolbar - proving
  // OverlayManager tracks THAT element directly (no anchor-sibling
  // resolution walked it onto some other node).
  await page.hover('#xfe-test-data-attribute-target', { force: true });
  await expect(toolbar).toHaveCSS('opacity', '1');
});

test('mixed page: an id-anchor element and a data-attribute-only element both work side by side', async ({ page }) => {
  await injectDataAttributeElement(page, 'xfe-test-data-attribute-target', DATA_ATTRIBUTE_ONLY_UID);

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await expect(hoverMenu.toolbar(ANCHOR_UID)).toHaveCount(1);
  await expect(page.locator(`.frontend-edit__toolbar[data-cid="${DATA_ATTRIBUTE_ONLY_UID}"]`)).toHaveCount(1);
});

test('a stray data-frontend-edit attribute for an already-anchored uid does not create a duplicate toolbar', async ({ page }) => {
  // Same uid as the real id="c2" anchor already on the page - the id-anchor
  // must win, and this element must not also get its own registration.
  await injectDataAttributeElement(page, 'xfe-test-duplicate-target', ANCHOR_UID);

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await expect(page.locator(`.frontend-edit__toolbar[data-cid="${ANCHOR_UID}"]`)).toHaveCount(1);

  // The duplicate element itself was never registered as an overlay target -
  // OverlayManager's pointer tracking only activates a toolbar when the
  // hovered node resolves to a registered target (see
  // resolveRegisteredElement in frontend_edit.js). Hovering the stray
  // duplicate must not activate the real c2 toolbar.
  await page.hover('#xfe-test-duplicate-target', { force: true });
  await expect(page.locator(`.frontend-edit__toolbar[data-cid="${ANCHOR_UID}"]`)).toHaveCSS('opacity', '0');
});

test('a nested .frontend-edit__data element (the <xfe:data> ViewHelper) resolves via its data-attribute-only owning element', async ({ page }) => {
  // <xfe:data> attaches a hidden .frontend-edit__data input to add extra
  // menu entries for related records - previously only resolved via the
  // id="c{uid}" anchor (getClosestContentElement); must also work when the
  // owning element instead uses data-frontend-edit.
  await page.addInitScript(
    ({ elementId, uid, relatedUid }) => {
      document.addEventListener('DOMContentLoaded', () => {
        const el = document.createElement('div');
        el.id = elementId;
        el.setAttribute('data-frontend-edit', `tt_content:${uid}`);
        el.textContent = 'Data-attribute test element with nested additional data';

        const dataInput = document.createElement('input');
        dataInput.type = 'hidden';
        dataInput.className = 'frontend-edit__data';
        dataInput.value = JSON.stringify({ label: 'Related item', table: 'tt_content', uid: relatedUid });
        el.appendChild(dataInput);

        document.body.prepend(el);
      });
    },
    { elementId: 'xfe-test-data-attribute-with-additional-data', uid: DATA_ATTRIBUTE_ONLY_UID, relatedUid: ANCHOR_UID },
  );

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await page.hover('#xfe-test-data-attribute-with-additional-data', { force: true });
  const toolbar = page.locator(`.frontend-edit__toolbar[data-cid="${DATA_ATTRIBUTE_ONLY_UID}"]`);
  await toolbar.locator('.frontend-edit__btn--kebab').click();

  const dropdown = page.locator(`.frontend-edit__dropdown[data-cid="${DATA_ATTRIBUTE_ONLY_UID}"]`);
  await expect(dropdown).toBeVisible();
  await expect(dropdown.getByText('Related item', { exact: true })).toBeVisible();
});
