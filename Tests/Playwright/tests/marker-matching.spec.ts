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

// "Two Column Container" and its two children on the /container page. The
// children have no anchor on the Home page.
const CONTAINER_UID = 30;
const CONTAINER_CHILD_UID = 31;
const CONTAINER_SECOND_CHILD_UID = 32;

const EDIT_INFORMATION_URL = '/ajax/xima-frontend-edit/edit-information';

const NESTED = /frontend-edit__overlay--nested/;

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

/**
 * Collects uncaught page errors. A malformed marker structure must never break
 * the bootstrap.
 */
function collectPageErrors(page: Page): string[] {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  return errors;
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

// Without a single root element the range cannot be mapped to one element.
// Reaching for a wrapper would put the toolbar on the surrounding column. Only
// the element's own empty anchor is ignored, a foreign one keeps the range
// ambiguous.
for (const [name, html] of [
  ['more than one root element', '<h2>Marker test heading</h2><p>Marker test text</p>'],
  ['a foreign empty anchor', '<a id="c99"></a><div>Marker test element</div>'],
]) {
  test(`a marker range with ${name} receives no overlay`, async ({ page }) => {
    await injectHtml(page, marked(MARKER_ONLY_UID, html));

    const requestedUids = await gotoAndCollectUids(page);

    const hoverMenu = new HoverMenu(page);
    expect(requestedUids).not.toContain(MARKER_ONLY_UID);
    await expect(hoverMenu.toolbar(MARKER_ONLY_UID)).toHaveCount(0);
    await expect(hoverMenu.toolbar(ANCHOR_UID)).toHaveCount(1);
  });
}

test('an unbalanced marker is ignored and leaves the rest of the page working', async ({ page }) => {
  // A begin marker whose end never arrives, which is what an HTML minifier or
  // table foster-parenting produces. It must not swallow the document or throw.
  // A real uid without anchor or attribute, so only the marker could have added
  // it to the request.
  await injectHtml(page, `<!--xfe:b:tt_content:${MARKER_ONLY_UID}-->`);

  const pageErrors = collectPageErrors(page);

  const requestedUids = await gotoAndCollectUids(page);

  expect(requestedUids).toContain(ANCHOR_UID);
  expect(requestedUids).not.toContain(MARKER_ONLY_UID);
  await expect(new HoverMenu(page).toolbar(ANCHOR_UID)).toHaveCount(1);
  expect(pageErrors).toEqual([]);
});

// The outer element has no anchor at all, so the id="c{uid}" walk cannot see
// the nesting: only the marker tree reports the inner element as nested.
test('an element in the empty-anchor pattern takes its nesting depth from the markers', async ({ page }) => {
  const inner = marked(MARKER_ONLY_UID, `<a id="c${MARKER_ONLY_UID}"></a><div id="xfe-test-inner">Inner element</div>`);
  await injectHtml(page, marked(CONTAINER_CHILD_UID, `<div>Outer element${inner}</div>`));

  await gotoAndCollectUids(page);

  const hoverMenu = new HoverMenu(page);
  await expect(hoverMenu.overlay(MARKER_ONLY_UID)).toHaveClass(NESTED);
  await expect(hoverMenu.overlay(CONTAINER_CHILD_UID)).not.toHaveClass(NESTED);

  // The toolbar sits on the element next to the anchor, not on the anchor.
  await page.hover('#xfe-test-inner', { force: true });
  await expect(hoverMenu.toolbar(MARKER_ONLY_UID)).toHaveCSS('opacity', '1');
});

test('an anchor with content but without href stays the element and keeps its depth', async ({ page }) => {
  const inner = marked(MARKER_ONLY_UID, `<a id="c${MARKER_ONLY_UID}">Teaser without href</a>`);
  await injectHtml(page, marked(CONTAINER_CHILD_UID, `<div>Outer element${inner}</div>`));

  await gotoAndCollectUids(page);

  await expect(new HoverMenu(page).overlay(MARKER_ONLY_UID)).toHaveClass(NESTED);
});

test('container children on the demo page are nested, the container is not', async ({ page }) => {
  await gotoAndCollectUids(page, '/container');

  const hoverMenu = new HoverMenu(page);
  await expect(hoverMenu.overlay(CONTAINER_UID)).not.toHaveClass(NESTED);
  await expect(hoverMenu.overlay(CONTAINER_CHILD_UID)).toHaveClass(NESTED);
  await expect(hoverMenu.overlay(CONTAINER_SECOND_CHILD_UID)).toHaveClass(NESTED);
});

test('a uid rendered twice gets one overlay, on its first instance', async ({ page }) => {
  await injectHtml(page, marked(MARKER_ONLY_UID, '<div id="xfe-test-first">First instance</div>')
    + marked(MARKER_ONLY_UID, '<div id="xfe-test-second">Second instance</div>'));
  const pageErrors = collectPageErrors(page);

  const requestedUids = await gotoAndCollectUids(page);

  const toolbar = new HoverMenu(page).toolbar(MARKER_ONLY_UID);
  expect(requestedUids.filter((uid) => uid === MARKER_ONLY_UID)).toHaveLength(1);
  await expect(toolbar).toHaveCount(1);
  await page.hover('#xfe-test-first', { force: true });
  await expect(toolbar).toHaveCSS('opacity', '1');
  expect(pageErrors).toEqual([]);
});

test('without markers, as after a comment-stripping minifier, anchor and data attribute still resolve', async ({ page }) => {
  await injectHtml(page, `<div id="xfe-test-attribute" data-frontend-edit="tt_content:${MARKER_ONLY_UID}">Data attribute element</div>`);
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT);
      const markers: Comment[] = [];
      while (walker.nextNode()) {
        if ((walker.currentNode.nodeValue || '').startsWith('xfe:')) markers.push(walker.currentNode as Comment);
      }
      markers.forEach((marker) => marker.remove());
    });
  });

  const requestedUids = await gotoAndCollectUids(page);

  const hoverMenu = new HoverMenu(page);
  expect(await page.content()).not.toContain('<!--xfe:');
  expect(requestedUids).toEqual(expect.arrayContaining([ANCHOR_UID, MARKER_ONLY_UID]));
  await expect(hoverMenu.toolbar(ANCHOR_UID)).toHaveCount(1);
  await expect(hoverMenu.toolbar(MARKER_ONLY_UID)).toHaveCount(1);
});

// An element that is not allowed inside a table is foster-parented in front of
// it, while the comments stay inside. The markers are then no siblings of the
// element, and the range must stay unresolved instead of reaching for the table.
test('a content element foster-parented out of a table receives no overlay', async ({ page }) => {
  await injectHtml(page, `<table id="xfe-test-table">${marked(MARKER_ONLY_UID, '<div>Fostered element</div>')}</table>`);
  const pageErrors = collectPageErrors(page);

  const requestedUids = await gotoAndCollectUids(page);

  expect(requestedUids).not.toContain(MARKER_ONLY_UID);
  await expect(new HoverMenu(page).toolbar(MARKER_ONLY_UID)).toHaveCount(0);
  await expect(new HoverMenu(page).toolbar(ANCHOR_UID)).toHaveCount(1);
  expect(pageErrors).toEqual([]);
});

// The cell is sized so the toolbar, which sits over the element's top edge,
// does not cover the hover point.
test('a table row wrapped in markers resolves to the row', async ({ page }) => {
  await injectHtml(page, `<table><tbody>${marked(MARKER_ONLY_UID, '<tr id="xfe-test-row"><td style="width: 600px; height: 120px">Row element</td></tr>')}</tbody></table>`);

  await gotoAndCollectUids(page);

  const toolbar = new HoverMenu(page).toolbar(MARKER_ONLY_UID);
  await page.hover('#xfe-test-row', { force: true });
  await expect(toolbar).toHaveCSS('opacity', '1');
});

// None of the three levels carries an anchor on the Home page, so the nesting
// is only visible through the marker tree.
test('three nested levels report nesting below the first level, innermost wins the hover', async ({ page }) => {
  const levelThree = marked(MARKER_ONLY_UID, '<div id="xfe-test-level-three">Level three</div>');
  const levelTwo = marked(CONTAINER_SECOND_CHILD_UID, `<div>Level two${levelThree}</div>`);
  await injectHtml(page, marked(CONTAINER_CHILD_UID, `<div>Level one${levelTwo}</div>`));

  await gotoAndCollectUids(page);

  const hoverMenu = new HoverMenu(page);
  await expect(hoverMenu.overlay(CONTAINER_CHILD_UID)).not.toHaveClass(NESTED);
  await expect(hoverMenu.overlay(CONTAINER_SECOND_CHILD_UID)).toHaveClass(NESTED);
  await expect(hoverMenu.overlay(MARKER_ONLY_UID)).toHaveClass(NESTED);

  await page.hover('#xfe-test-level-three', { force: true });
  await expect(hoverMenu.toolbar(MARKER_ONLY_UID)).toHaveCSS('opacity', '1');
});
