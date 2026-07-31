import { test, expect } from '@playwright/test';
import { HoverMenu } from '../support/frontend-edit/hover-menu';

// "About This Demo" text element on the Home page (Tests/Acceptance/Fixtures/demo-content.sql).
const CONTENT_ELEMENT_UID = 2;

test('editInformation response deduplicates icon markup into a shared icons map', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  const response = await editInfoResponse;
  const payload = await response.json();

  expect(payload).toHaveProperty('icons');
  const iconKeys = Object.keys(payload.icons);
  expect(iconKeys.length).toBeGreaterThan(0);

  // element.ctypeIcon and menu button .icon fields carry a short key into the
  // icons map now, not the raw SVG markup itself (see IconDeduplicationService).
  const element = payload.contentElements[CONTENT_ELEMENT_UID];
  expect(element.element.ctypeIcon).not.toContain('<svg');
  expect(payload.icons[element.element.ctypeIcon]).toContain('<svg');

  const editButton = element.menu.children.edit;
  expect(editButton.icon).not.toContain('<svg');
  expect(payload.icons[editButton.icon]).toContain('<svg');

  // The same icon used by multiple elements/buttons resolves to one shared key.
  const kebabChildren = Object.values(element.menu.children) as Array<{ icon?: string }>;
  const infoIconKey = kebabChildren.find((child) => child.icon)?.icon;
  expect(iconKeys).toContain(infoIconKey);
});

test('the frontend still renders real icon markup, resolved client-side from the icons map', async ({ page }) => {
  const editInfoResponse = page.waitForResponse((response) => response.url().includes('/ajax/xima-frontend-edit/edit-information'));
  await page.goto('/');
  await editInfoResponse;

  const hoverMenu = new HoverMenu(page);
  await hoverMenu.hover(CONTENT_ELEMENT_UID);

  // Toolbar label icon - scoped to this element's own toolbar, since every
  // content element on the page renders one (just hover-gated via opacity).
  await expect(hoverMenu.toolbar(CONTENT_ELEMENT_UID).locator('.frontend-edit__toolbar-icon svg')).toHaveCount(1);

  // Dropdown item icons
  await hoverMenu.kebabButton(CONTENT_ELEMENT_UID).click();
  const dropdown = hoverMenu.dropdown(CONTENT_ELEMENT_UID);
  await expect(dropdown).toBeVisible();
  const iconCount = await dropdown.locator('svg').count();
  expect(iconCount).toBeGreaterThan(0);
});
