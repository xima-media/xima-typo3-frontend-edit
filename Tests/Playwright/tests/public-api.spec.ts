import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

type XfeLogEntry = {
  name: string;
  uid?: number;
  hasElement?: boolean;
  payloadUid?: number;
  elementUids?: string[];
};

// Registered via addInitScript so the listeners exist before frontend_edit.js's
// own script tag executes on navigation. Captures only serializable fields -
// DOM elements/CustomEvent detail objects don't survive page.evaluate()'s
// structured-clone round trip back to the test process.
async function installXfeLogger(page: import('@playwright/test').Page): Promise<void> {
  await page.addInitScript(() => {
    (window as any).__xfeLog = [];
    const capture = (name: string) => (e: Event) => {
      const detail = (e as CustomEvent).detail || {};
      (window as any).__xfeLog.push({
        name,
        uid: detail.uid,
        hasElement: detail.element instanceof Element,
        payloadUid: detail.payload?.element?.uid,
        elementUids: detail.elements ? Object.keys(detail.elements) : undefined,
      });
    };
    ['xfe:ready', 'xfe:element-rendered', 'xfe:dropdown-open', 'xfe:dropdown-close'].forEach((name) => {
      document.addEventListener(name, capture(name));
    });
  });
}

test.beforeEach(async ({ page }) => {
  await installXfeLogger(page);
});

test('xfe:ready fires once with an element map, xfe:element-rendered fires per element', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await expect(hoverMenu.toolbar(CONTENT_ELEMENT_UID)).toHaveCount(1);

  const log = await page.evaluate(() => (window as any).__xfeLog as XfeLogEntry[]);

  const readyEvents = log.filter((e) => e.name === 'xfe:ready');
  expect(readyEvents).toHaveLength(1);
  expect(readyEvents[0].elementUids).toContain(String(CONTENT_ELEMENT_UID));

  const renderedForUid = log.filter((e) => e.name === 'xfe:element-rendered' && e.uid === CONTENT_ELEMENT_UID);
  expect(renderedForUid).toHaveLength(1);
  expect(renderedForUid[0].hasElement).toBe(true);
  expect(renderedForUid[0].payloadUid).toBe(CONTENT_ELEMENT_UID);
});

test('getElementInfo resolves the DOM element and payload for a uid', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const info = await page.evaluate((uid) => {
    const result = (window as any).XimaFrontendEdit.getElementInfo(uid);
    return result
      ? { uid: result.uid, hasElement: result.element instanceof Element, ctype: result.payload?.element?.CType }
      : null;
  }, CONTENT_ELEMENT_UID);

  expect(info?.uid).toBe(CONTENT_ELEMENT_UID);
  expect(info?.hasElement).toBe(true);
  expect(info?.ctype).toBeTruthy();

  const unknown = await page.evaluate(() => (window as any).XimaFrontendEdit.getElementInfo(999999));
  expect(unknown).toBeNull();
});

test('registerToolbarItem adds a clickable button to the hover toolbar', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await page.evaluate((uid) => {
    (window as any).__customClicked = false;
    (window as any).XimaFrontendEdit.registerToolbarItem(uid, {
      html: '<span>X</span>',
      label: 'Custom action',
      onClick: () => {
        (window as any).__customClicked = true;
      },
    });
  }, CONTENT_ELEMENT_UID);

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);

  const customBtn = hoverMenu.toolbar(CONTENT_ELEMENT_UID).locator('.frontend-edit__btn--custom');
  await expect(customBtn).toBeVisible();
  await expect(customBtn).toHaveAttribute('aria-label', 'Custom action');

  await customBtn.click();
  expect(await page.evaluate(() => (window as any).__customClicked)).toBe(true);
});

test('registerBadge renders a persistent indicator independent of hover', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await page.evaluate((uid) => {
    (window as any).XimaFrontendEdit.registerBadge(uid, {
      // Explicit size: an empty/unstyled inline span has a zero-area bounding
      // box, which Playwright's toBeVisible() treats as not visible.
      html: '<span class="dot" style="display:inline-block;width:8px;height:8px;background:red;border-radius:50%;"></span>',
      position: 'top-left',
    });
  }, CONTENT_ELEMENT_UID);

  const badge = page.locator(`.frontend-edit__overlay[data-cid="${CONTENT_ELEMENT_UID}"] .frontend-edit__badge`);
  await expect(badge).toHaveCount(1);
  await expect(badge).toHaveClass(/frontend-edit__badge--top-left/);

  // Not hovering the element: the badge must stay visible, unlike the
  // hover-gated toolbar/outline (opacity 0 until OverlayManager activates it).
  await expect(badge).toBeVisible();
});

test('notify shows a toast notification', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  await page.evaluate(() => {
    (window as any).XimaFrontendEdit.notify({ title: 'Hello from a third party', message: 'World', severity: 'ok' });
  });

  const toast = page.locator('.frontend-edit__notification');
  await expect(toast).toBeVisible();
  await expect(toast.locator('.frontend-edit__notification-title')).toHaveText('Hello from a third party');
});

test('xfe:dropdown-open/close fire exactly once per interaction, not on every hover switch', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.openDropdown(CONTENT_ELEMENT_UID);
  await expect(hoverMenu.dropdown(CONTENT_ELEMENT_UID)).toBeVisible();

  // Hover away and back - exercises OverlayManager's pointer-driven
  // Dropdown.closeAll() path, which must not re-fire the close event once
  // the dropdown is already closed.
  await page.mouse.move(0, 0);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);

  const log = await page.evaluate(() => (window as any).__xfeLog as XfeLogEntry[]);
  const opens = log.filter((e) => e.name === 'xfe:dropdown-open' && e.uid === CONTENT_ELEMENT_UID);
  const closes = log.filter((e) => e.name === 'xfe:dropdown-close' && e.uid === CONTENT_ELEMENT_UID);

  expect(opens).toHaveLength(1);
  expect(closes).toHaveLength(1);
});
