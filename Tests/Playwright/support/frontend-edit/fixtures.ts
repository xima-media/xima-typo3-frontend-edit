import { execSync } from 'node:child_process';

const TYPO3_VERSION = process.env.TYPO3_VERSION || '13';
const DATABASE = `database_${TYPO3_VERSION}`;

/**
 * Un-hides a tt_content record via a direct DB write, restoring
 * Tests/Acceptance/Fixtures/demo-content.sql's baseline state after a hide-action
 * test. Cheaper than re-importing the whole SQL fixture between tests.
 */
export function restoreHiddenContentElement(uid: number): void {
  if (!Number.isInteger(uid) || uid <= 0) {
    throw new Error(`Invalid content element uid: ${uid}`);
  }

  execSync(`mysql -h db -u root -proot ${DATABASE} -e "UPDATE tt_content SET hidden = 0 WHERE uid = ${uid};"`);
}
