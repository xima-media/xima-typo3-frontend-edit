import { flushCache, runSql } from '../typo3/environment';

/**
 * Un-hides a tt_content record via a direct DB write, restoring
 * Tests/Acceptance/Fixtures/demo-content.sql's baseline state after a hide-action
 * test. Cheaper than re-importing the whole SQL fixture between tests.
 *
 * The direct SQL write bypasses DataHandler, so it never clears TYPO3's page
 * cache the way a real backend edit would — the next page load would still
 * serve the stale "hidden" render (missing #c{uid}) even though the DB is
 * correct again. Flushing the cache after the write closes that gap; verified
 * live: without it, a second hide-action run times out waiting for #c5.
 */
export function restoreHiddenContentElement(uid: number): void {
  if (!Number.isInteger(uid) || uid <= 0) {
    throw new Error(`Invalid content element uid: ${uid}`);
  }

  runSql(`UPDATE tt_content SET hidden = 0 WHERE uid = ${uid};`);
  flushCache();
}
