<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Service\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_values;
use function in_array;
use function parse_url;
use function strcasecmp;
use function strtolower;

/**
 * ReturnUrlValidator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ReturnUrlValidator
{
    public function __construct(
        private SiteFinder $siteFinder,
    ) {}

    /**
     * Accepts a local relative returnUrl (as the core defines it), or an http(s) one whose host matches the current request
     * or one of the resolved site's base URLs (including per-language bases).
     * Rejects malformed URLs and everything else.
     */
    public function isValid(string $returnUrl, ServerRequestInterface $request, int $pid): bool
    {
        $host = parse_url($returnUrl, \PHP_URL_HOST);
        if (false === $host) {
            return false;
        }
        if (null === $host) {
            // Covers what parse_url() reports without a host: "javascript:...", "/\\evil.com", "https:evil.com".
            return '' !== GeneralUtility::sanitizeLocalUrl($returnUrl);
        }

        if (!in_array(strtolower((string) parse_url($returnUrl, \PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }

        if (0 === strcasecmp($host, $request->getUri()->getHost())) {
            return true;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pid);
        } catch (SiteNotFoundException) {
            return false;
        }

        foreach ($this->allowedSiteHosts($site) as $allowedHost) {
            if (0 === strcasecmp($host, $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function allowedSiteHosts(Site $site): array
    {
        $hosts = [$site->getBase()->getHost()];

        foreach ($site->getLanguages() as $language) {
            $hosts[] = $language->getBase()->getHost();
        }

        return array_values(array_filter($hosts, static fn (string $host): bool => '' !== $host));
    }
}
