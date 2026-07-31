import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// Onepager page (uid=12) seeded by Tests/Acceptance/Fixtures/demo-content.sql:
// a native "Insert Records" (shortcut) content element embeds tt_content
// uid=7 ("Our Mission"), which actually lives on the About Us page (pid=2).
// fluid_styled_content renders the referenced record through the normal
// tt_content pipeline, so it keeps its own id="c7" anchor here — exercising
// DataService.collectDataItems()'s whole-DOM id="c{uid}" scan (not pid-based)
// against a real foreign-pid element.
const FOREIGN_PID_CONTENT_ELEMENT_UID = 7;

test('builds a working edit menu for a content element embedded from another page', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/onepager');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(FOREIGN_PID_CONTENT_ELEMENT_UID);

  await expect(hoverMenu.toolbar(FOREIGN_PID_CONTENT_ELEMENT_UID)).toHaveCSS('opacity', '1');

  // Edit action must target the foreign-pid record itself (uid 7), not the
  // shortcut element that embeds it (uid 38) or the wrong page's content.
  const editHref = await hoverMenu.editButton(FOREIGN_PID_CONTENT_ELEMENT_UID).getAttribute('href');
  expect(decodeURIComponent(editHref ?? '')).toContain(`edit[tt_content][${FOREIGN_PID_CONTENT_ELEMENT_UID}]=edit`);
});
