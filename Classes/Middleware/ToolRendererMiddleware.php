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

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Stream;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{FlashMessageService, ResourceRendererService};

use function is_array;
use function strlen;

/**
 * ToolRendererMiddleware.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class ToolRendererMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResourceRendererService $resourceRendererService,
        private readonly SettingsService $settingsService,
        private readonly FlashMessageService $flashMessageService,
        private readonly BackendUserService $backendUserService,
    ) {}

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Cheapest gate first: bail out immediately for anonymous frontend traffic
        // before resolving any site settings.
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (
            !$backendUser instanceof BackendUserAuthentication
            || !is_array($backendUser->user)
        ) {
            return $response;
        }

        // When disabled via UserTSconfig, hide everything including the sticky toolbar
        if (!$this->backendUserService->isFrontendEditAllowed()) {
            return $response;
        }

        // Resolving the site settings is the most expensive check, so it runs last.
        if (!$this->settingsService->isEnabled($request)) {
            return $response;
        }

        // Collect flash messages from backend session before rendering (if enabled).
        //
        // The iframe modal editor appends ?tx_ximatypo3frontendedit_iframe=1 to the
        // save returnUrl so this follow-up request doesn't consume the flash queue —
        // messages are left in the session for the parent page reload to pick up.
        $isIframeRequest = isset($request->getQueryParams()['tx_ximatypo3frontendedit_iframe']);
        $flashMessages = !$isIframeRequest && $this->settingsService->isEnableFlashMessages($request)
            ? $this->flashMessageService->collectFromSession()
            : [];

        $responseBody = $response->getBody();
        $responseBody->rewind();
        $contents = $responseBody->getContents();

        // Inject the asset bundle right before the last closing body tag. Using the
        // last occurrence (instead of str_ireplace, which rewrites every match and
        // scans the whole document case-insensitively) keeps a stray "</body>" in
        // page content from receiving a second, misplaced injection.
        $position = strripos($contents, '</body>');
        if (false === $position) {
            return $response;
        }

        $injection = $this->resourceRendererService->render(request: $request, flashMessages: $flashMessages);
        $content = substr_replace($contents, $injection.'</body>', $position, strlen('</body>'));

        $body = new Stream('php://temp', 'rw');
        $body->write($content);

        return $response->withBody($body);
    }
}
