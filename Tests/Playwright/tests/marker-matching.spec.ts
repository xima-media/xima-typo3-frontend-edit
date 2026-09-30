import { test, expect, type Page } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page, with its usual id="c2"
// anchor (Tests/Acceptance/Fixtures/demo-content.sql).
const ANCHOR_UID = 2;

// "Our Mission" lives on the About Us page and has no id="c7" anchor on the
// Home page, so it is a real, fetchable tt_content record with nothing else
// already competing for the marker matching under test. Same rationale as in
// data-attribute-matching.spec.ts.
const MARKER_ONLY_UID = 7;

const EDIT_INFORMATION_URL = '/ajax/xima-frontend-edit/edit-information';

/**
 * Wraps markup in a marker pair, mirroring what
 * ContentElementMarkerEventListener emits during rendering.
 */
const marked = (uid: number, html: string): string => `<!--xfe:b:tt_content:${uid}-->${html}<!--xfe:e:tt_content:${uid}-->`;

/**
 * Prepends markup to the body. Registered before navigation and driven by
 * DOMContentLoaded, which fires before frontend_edit.js's own bootstrap, so the
 * nodes exist in time for MarkerIndex.build() inside
 * DataService.collectDataItems(). Prepended rather than appended because a
 * fixed cookie-consent banner sits at the bottom of the page and would
 * intercept hover hit-testing.
 */
async function injectHtml(page: Page, html: string): Promise<void> {
  await page.addInitScript((markup) => {
    document.addEventListener('DOMContentLoaded', () => {
      document.body.prepend(document.createRange().createContextualFragment(markup));
    });
  }, html);
}

/**
 * Navigates and waits for the edit information response, after which the
 * overlays exist. Returns the uids the page requested.
 */
async function gotoAndCollectUids(page: Page, path = '/'): Promise<number[]> {
  const request = page.waitForRequest((candidate) => candidate.url().includes(EDIT_INFORMATION_URL));
  const response = page.waitForResponse((candidate) => candidate.url().includes(EDIT_INFORMATION_URL));
  await page.goto(path);
  const uids: number[] = (await request).postDataJSON()._uids;
  await response;
  return uids;
}

test('the rendered page carries marker pairs for a logged-in backend user', async ({ page }) => {
  await page.goto('/');
  const html = await page.content();

  // Proves the whole server-side chain: the TypoScript condition set the sentinel
  // key, and the listener wrapped the element. The demo site renders content
  // through bootstrap-package's lib.dynamicContent, which sets
  // renderObj.stdWrap.dataWrap itself, so this simultaneously guards against the
  // collision that made a dataWrap-based implementation silently emit nothing.
  const beginMarkers = html.match(/<!--xfe:b:tt_content:\d+-->/g) ?? [];
  const endMarkers = html.match(/<!--xfe:e:tt_content:\d+-->/g) ?? [];

  expect(beginMarkers.length).toBeGreaterThan(0);
  expect(endMarkers.length).toBe(beginMarkers.length);
});

test('an element with only marker comments receives an overlay/menu, without an id anchor or data attribute', async ({ page }) => {
  await injectHtml(page, marked(MARKER_ONLY_UID, '<div id="xfe-test-marker-target">Marker test element</div>'));

  await gotoAndCollectUids(page);

  const toolbar = new HoverMenu(page).toolbar(MARKER_ONLY_UID);
  await expect(toolbar).toHaveCount(1);
  await expect(toolbar).toHaveCSS('opacity', '0');

  // Hovering the marked element activates its toolbar, proving the marker range
  // resolved to THAT element rather than to some ancestor wrapper.
  await page.hover('#xfe-test-marker-target', { force: true });
  await expect(toolbar).toHaveCSS('opacity', '1');
});

test('a marker range with more than one root element receives no overlay', async ({ page }) => {
  // Without a single root element the range cannot be mapped to one element.
  // Reaching for a wrapper would put the toolbar on the surrounding column.
  await injectHtml(page, marked(MARKER_ONLY_UID, '<h2>Marker test heading</h2><p>Marker test text</p>'));

  const requestedUids = await gotoAndCollectUids(page);

  const hoverMenu = new HoverMenu(page);
  expect(requestedUids).not.toContain(MARKER_ONLY_UID);
  await expect(hoverMenu.toolbar(MARKER_ONLY_UID)).toHaveCount(0);
  await expect(hoverMenu.toolbar(ANCHOR_UID)).toHaveCount(1);
});

test('an unbalanced marker is ignored and leaves the rest of the page working', async ({ page }) => {
  // A begin marker whose end never arrives, which is what an HTML minifier or
  // table foster-parenting produces. It must not swallow the document or throw.
  // A real uid without anchor or attribute, so only the marker could have added
  // it to the request.
  await injectHtml(page, `<!--xfe:b:tt_content:${MARKER_ONLY_UID}-->`);

  const consoleErrors: string[] = [];
  page.on('pageerror', (error) => consoleErrors.push(error.message));

  const requestedUids = await gotoAndCollectUids(page);

  expect(requestedUids).toContain(ANCHOR_UID);
  expect(requestedUids).not.toContain(MARKER_ONLY_UID);
  await expect(new HoverMenu(page).toolbar(ANCHOR_UID)).toHaveCount(1);
  expect(consoleErrors).toEqual([]);
});
