<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use Xima\XimaTypo3FrontendEdit\Configuration;

/**
 * AjaxController.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[Autoconfigure(public: true)]
readonly class AjaxController
{
    /**
     * Toggle frontend edit active state.
     *
     * Uses the backend user's uc (user configuration) to persist the state.
     */
    public function toggleAction(): JsonResponse
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return new JsonResponse(['success' => false, 'error' => 'No backend user'], 403);
        }

        $currentValue = (bool) ($backendUser->uc[Configuration::UC_KEY_DISABLED] ?? false);
        $newValue = !$currentValue;

        // Update user configuration and persist
        $backendUser->uc[Configuration::UC_KEY_DISABLED] = $newValue;
        $backendUser->writeUC();

        return new JsonResponse([
            'success' => true,
            'disabled' => $newValue,
        ]);
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
