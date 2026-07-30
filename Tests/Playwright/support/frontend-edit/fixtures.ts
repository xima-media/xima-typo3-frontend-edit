import { execSync } from 'node:child_process';

const TYPO3_VERSION = process.env.TYPO3_VERSION || '13';
const DATABASE = `database_${TYPO3_VERSION}`;
const TYPO3_BIN = `/var/www/html/.Build/${TYPO3_VERSION}/vendor/bin/typo3`;

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

  execSync(`mysql -h db -u root -proot ${DATABASE} -e "UPDATE tt_content SET hidden = 0 WHERE uid = ${uid};"`);
  execSync(`${TYPO3_BIN} cache:flush`);
}
