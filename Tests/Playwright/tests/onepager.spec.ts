import { test } from '@playwright/test';

test.describe('onepager foreign-pid menus', () => {
  test.fixme(
    'needs an onepager fixture page — see follow-up issue filed after #220 merges',
    async () => {
      // Tests/Acceptance/Fixtures/demo-content.sql has no onepager page yet (a page
      // rendering tt_content records whose pid differs from the page being viewed).
      // Once added: navigate to that page, wait for the editInformation response,
      // and assert the foreign-pid content elements still receive a working edit
      // menu — DataService.collectDataItems() in frontend_edit.js scans the whole
      // DOM by id="c{uid}", not by pid, so this should already work; the point of
      // this test is to catch a regression in that assumption.
    },
  );
});
