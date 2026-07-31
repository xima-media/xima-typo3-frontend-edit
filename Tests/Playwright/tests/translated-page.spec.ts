import { test } from '@playwright/test';

test.describe('translated page (connected mode)', () => {
  test.fixme(
    'needs a second site language in the ddev setup — see follow-up issue filed after #220 merges',
    async () => {
      // Regression test for issue #212 (connected-mode translated content elements
      // got no edit menu). The fix — matching via l18n_parent, see the anchorUid
      // fallback in frontend_edit.js Renderer.render() — is already on main, but
      // there is no second site language configured yet to exercise it end-to-end.
      // Once added: navigate to a translated page, hover a translated content
      // element, and assert the edit action's URL targets the translation uid
      // (edit[tt_content][<translationUid>]=edit), not the L0 uid rendered in the
      // id="c{l0uid}" DOM anchor.
    },
  );
});
