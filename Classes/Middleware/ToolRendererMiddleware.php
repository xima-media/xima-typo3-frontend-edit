<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Xima\XimaTypo3FrontendEdit\Controller\EditController;

class ToolRendererMiddleware implements MiddlewareInterface
{
    public function __construct(protected readonly EditController $editController)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && $GLOBALS['BE_USER']
            && (!array_key_exists('tx_ximatypo3frontendedit_disable', $GLOBALS['BE_USER']->user) || !$GLOBALS['BE_USER']->user['tx_ximatypo3frontendedit_disable'])
        ) {
            // Using an ugly workaround here for accessing full typoscript on cached pages
            // @see https://forge.typo3.org/issues/99417
            $request->getAttribute('frontend.controller')->config['INTincScript'][] = [];
            $response = $handler->handle($request);

            $body = $response->getBody();
            $body->rewind();
            $contents = $response->getBody()->getContents();
            $content = str_ireplace(
                '</body>',
                $this->editController->render() . '</body>',
                $contents
            );
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        } else {
            $response = $handler->handle($request);
        }
        return $response;
    }
}
