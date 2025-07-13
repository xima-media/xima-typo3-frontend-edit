<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;
use Xima\XimaTypo3FrontendEdit\Service\Ui\ResourceRendererService;

class ToolRendererMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResourceRendererService $resourceRendererService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $typoScriptConfig = $GLOBALS['TSFE']->config['config'];

        if (
            array_key_exists('tx_ximatypo3frontendedit_enable', $typoScriptConfig)
            && $typoScriptConfig['tx_ximatypo3frontendedit_enable']
            && $GLOBALS['BE_USER'] !== null
            && (!array_key_exists('tx_ximatypo3frontendedit_disable', $GLOBALS['BE_USER']->user) || !$GLOBALS['BE_USER']->user['tx_ximatypo3frontendedit_disable'])
        ) {
            $body = $response->getBody();
            $body->rewind();
            $contents = $response->getBody()->getContents();
            $content = str_ireplace(
                '</body>',
                $this->resourceRendererService->render(request: $request) . '</body>',
                $contents
            );
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }
        return $response;
    }
}
