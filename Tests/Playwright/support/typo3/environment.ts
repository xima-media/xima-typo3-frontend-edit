import { execSync } from 'node:child_process';

/**
 * TYPO3-generic ddev environment helpers. Nothing here knows about any
 * specific extension — this is the extraction candidate for a future
 * shared typo3-playwright package. Assumes the `.ddev/.setup` conventions
 * this comes from: per-version instances under `.Build/${version}`, a
 * `database_${version}` MariaDB schema, reachable via the ddev-provided
 * `mysql` client from inside the web container.
 */

export function resolveTypo3Version(): string {
  return process.env.TYPO3_VERSION || '13';
}

export function typo3DatabaseName(version = resolveTypo3Version()): string {
  return `database_${version}`;
}

export function typo3BinPath(version = resolveTypo3Version()): string {
  return `/var/www/html/.Build/${version}/vendor/bin/typo3`;
}

export function typo3BaseUrl(hostname: string, version = resolveTypo3Version()): string {
  return `https://${version}.${hostname}`;
}

export function typo3AuthStateFile(version = resolveTypo3Version()): string {
  return `.auth/state-${version}.json`;
}

/**
 * Runs a raw SQL statement against this TYPO3 version's database via the
 * ddev-provided mysql client. Bypasses DataHandler entirely — pair with
 * flushCache() if the write affects anything TYPO3 might have cached.
 */
export function runSql(query: string, version = resolveTypo3Version()): void {
  execSync(`mysql -h db -u root -proot ${typo3DatabaseName(version)} -e "${query}"`);
}

/**
 * Flushes TYPO3's cache. Needed after any direct DB write that bypasses
 * DataHandler — DataHandler normally clears the affected caches itself,
 * a raw SQL write never does, and a page can keep serving a stale cached
 * render otherwise (confirmed live: a second run of a test that un-hides
 * a record via runSql() timed out until this was added).
 */
export function flushCache(version = resolveTypo3Version()): void {
  execSync(`${typo3BinPath(version)} cache:flush`);
}
