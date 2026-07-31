import { test, expect } from '@playwright/test';

// Page seeded by Tests/Acceptance/Fixtures/demo-content.sql: header (uid=34),
// text (uid=35 — our "did initialization actually complete" control signal),
// and an html block (uid=36) containing a DOM-clobbering form, an SVG
// element, and a "section-c123" id that must not be mistaken for content
// element 123. Regression coverage for #222 (Dom.id() hardening), per #223.
const CONTROL_ELEMENT_UID = 35;

test('initializes despite the clobbering form/SVG and does not misidentify section-c123', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/dom-hardening');
  await editInfoResponse;

  // No crash: bootstrap()'s catch block would show this notification and abort
  // the rest of init if collectDataItems()/Renderer.render() threw on the
  // clobbered form's `.id` or the SVG's `.className`.
  await expect(page.getByText('Initialization error')).toHaveCount(0);

  // Init actually completed (not just "didn't crash"): a real content element
  // on the same page still gets its toolbar, proving the hazards didn't
  // silently break scanning for everything else on the page.
  await expect(page.locator(`.frontend-edit__toolbar[data-cid="${CONTROL_ELEMENT_UID}"]`)).toHaveCount(1);

  // "section-c123" doesn't match the exact `c{digits}` id pattern the scan
  // requires ([id^="c"] narrowing + /^c(\d+)$/ exact match), so it's never
  // treated as content element 123.
  await expect(page.locator('.frontend-edit__toolbar[data-cid="123"]')).toHaveCount(0);
});
