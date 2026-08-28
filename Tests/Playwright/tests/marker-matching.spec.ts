import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page, with its usual id="c2"
// anchor (Tests/Acceptance/Fixtures/demo-content.sql).
const ANCHOR_UID = 2;

// "Our Mission" - lives on the About Us page and has no id="c7" anchor on the
// Home page, so it is a real, fetchable tt_content record with nothing else
// already competing for the marker matching under test. Same rationale as in
// data-attribute-matching.spec.ts.
const MARKER_ONLY_UID = 7;

/**
 * Places a marker pair around a synthetic element, mirroring what
 * ContentElementMarkerEventListener emits during rendering. Registered before
 * navigation and driven by DOMContentLoaded, which fires before
 * frontend_edit.js's own bootstrap - so the nodes exist in time for
 * MarkerIndex.build() inside DataService.collectDataItems().
 */
async function injectMarkerPair(
  page: import('@playwright/test').Page,
  elementId: string,
  uid: number,
): Promise<void> {
  await page.addInitScript(
    ({ elementId, uid }) => {
      document.addEventListener('DOMContentLoaded', () => {
        const begin = document.createComment(`xfe:b:tt_content:${uid}`);
        const element = document.createElement('div');
        element.id = elementId;
        element.textContent = 'Marker test element';
        const end = document.createComment(`xfe:e:tt_content:${uid}`);

        // Prepended in reverse so the resulting order is begin, element, end.
        // Prepended rather than appended because a fixed cookie-consent banner
        // sits at the bottom of the page and would intercept hover hit-testing.
        document.body.prepend(end);
        document.body.prepend(element);
        document.body.prepend(begin);
      });
    },
    { elementId, uid },
  );
}

test('the rendered page carries marker pairs for a logged-in backend user', async ({ page }) => {
  await page.goto('/');
  const html = await page.content();

  // Proves the whole server-side chain: the TypoScript condition set the sentinel
  // key, and the listener wrapped the element. The demo site renders content
  // through bootstrap-package's lib.dynamicContent, which sets
  // renderObj.stdWrap.dataWrap itself - so this simultaneously guards against the
  // collision that made a dataWrap-based implementation silently emit nothing.
  const beginMarkers = html.match(/<!--xfe:b:tt_content:\d+-->/g) ?? [];
  const endMarkers = html.match(/<!--xfe:e:tt_content:\d+-->/g) ?? [];

  expect(beginMarkers.length).toBeGreaterThan(0);
  expect(endMarkers.length).toBe(beginMarkers.length);
});

test('an element with only marker comments receives an overlay/menu, without an id anchor or data attribute', async ({ page }) => {
  await injectMarkerPair(page, 'xfe-test-marker-target', MARKER_ONLY_UID);

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const toolbar = page.locator(`.frontend-edit__toolbar[data-cid="${MARKER_ONLY_UID}"]`);
  await expect(toolbar).toHaveCount(1);
  await expect(toolbar).toHaveCSS('opacity', '0');

  // Hovering the marked element activates its toolbar, proving the marker range
  // resolved to THAT element rather than to some ancestor wrapper.
  await page.hover('#xfe-test-marker-target', { force: true });
  await expect(toolbar).toHaveCSS('opacity', '1');
});

test('an unbalanced marker is ignored and leaves the rest of the page working', async ({ page }) => {
  // A begin marker whose end never arrives - what an HTML minifier or table
  // foster-parenting produces. It must not swallow the document or throw.
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      document.body.prepend(document.createComment('xfe:b:tt_content:4242'));
    });
  });

  const consoleErrors: string[] = [];
  page.on('pageerror', (error) => consoleErrors.push(error.message));

  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await expect(hoverMenu.toolbar(ANCHOR_UID)).toHaveCount(1);
  expect(consoleErrors).toEqual([]);
});
