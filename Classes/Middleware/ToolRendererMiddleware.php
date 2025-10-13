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

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Stream;
use Xima\XimaTypo3FrontendEdit\Service\Ui\ResourceRendererService;

use function array_key_exists;
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
    ) {}

    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $typoScriptConfig = $GLOBALS['TSFE']->config['config'];

        if (
            array_key_exists('tx_ximatypo3frontendedit_enable', $typoScriptConfig)
            && $typoScriptConfig['tx_ximatypo3frontendedit_enable']
            && null !== $GLOBALS['BE_USER']
            && is_array($GLOBALS['BE_USER']->user)
            && (!array_key_exists('tx_ximatypo3frontendedit_disable', $GLOBALS['BE_USER']->user) || !$GLOBALS['BE_USER']->user['tx_ximatypo3frontendedit_disable'])
        ) {
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
            $response = $response->withBody($body);
        }

        return $response;
    }
}
