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

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Stream;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\ResourceRendererService;

use function is_array;

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
    ) {}

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->settingsService->isEnabled($request)) {
            return $response;
        }

        // Only check if backend user is logged in - the sticky toolbar must be visible
        // even when frontend edit is disabled so the user can re-enable it
        if (
            null === $GLOBALS['BE_USER']
            || !is_array($GLOBALS['BE_USER']->user)
        ) {
            return $response;
        }

        $body = $response->getBody();
        $body->rewind();
        $contents = $response->getBody()->getContents();
        $content = str_ireplace(
            '</body>',
            $this->resourceRendererService->render(request: $request).'</body>',
            $contents,
        );
        $body = new Stream('php://temp', 'rw');
        $body->write($content);

        return $response->withBody($body);
    }
}
