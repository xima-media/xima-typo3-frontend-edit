<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;
use Xima\XimaTypo3FrontendEdit\Utility\ResourceRenderer;

class ToolRendererMiddleware implements MiddlewareInterface
{
    public function __construct(protected readonly ResourceRenderer $resourceRenderer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (
            $GLOBALS['BE_USER']
            && (!array_key_exists('tx_ximatypo3frontendedit_disable', $GLOBALS['BE_USER']->user) || !$GLOBALS['BE_USER']->user['tx_ximatypo3frontendedit_disable'])
        ) {
            $body = $response->getBody();
            $body->rewind();
            $contents = $response->getBody()->getContents();
            $content = str_ireplace(
                '</body>',
                $this->resourceRenderer->render() . '</body>',
                $contents
            );
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }
        return $response;
    }
}
