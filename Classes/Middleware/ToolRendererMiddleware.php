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
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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

        // Collect flash messages from backend session before rendering
        $flashMessages = $this->collectFlashMessages();

        $body = $response->getBody();
        $body->rewind();
        $contents = $response->getBody()->getContents();
        $content = str_ireplace(
            '</body>',
            $this->resourceRendererService->render(request: $request, flashMessages: $flashMessages).'</body>',
            $contents,
        );
        $body = new Stream('php://temp', 'rw');
        $body->write($content);

        return $response->withBody($body);
    }

    /**
     * Collect flash messages from the backend user session.
     *
     * When using "Save & Close" in the backend, TYPO3 performs a direct redirect
     * without rendering the backend template. This means flash messages remain
     * in the session and can be displayed in the frontend.
     *
     * TYPO3 uses two queues:
     * - FLASHMESSAGE_QUEUE: For error/warning messages (e.g., from DataHandler)
     * - NOTIFICATION_QUEUE: For success messages (e.g., "Record saved")
     *
     * @return array<array{title: string, message: string, severity: string}>
     */
    private function collectFlashMessages(): array
    {
        if (null === $GLOBALS['BE_USER'] || !is_array($GLOBALS['BE_USER']->user)) {
            return [];
        }

        $result = [];

        // Collect from both queues - NOTIFICATION_QUEUE has success messages,
        // FLASHMESSAGE_QUEUE has error/warning messages
        $queues = [
            FlashMessageQueue::NOTIFICATION_QUEUE,
            FlashMessageQueue::FLASHMESSAGE_QUEUE,
        ];

        foreach ($queues as $queueIdentifier) {
            $sessionData = $GLOBALS['BE_USER']->getSessionData($queueIdentifier);

            if (empty($sessionData) || !is_array($sessionData)) {
                continue;
            }

            // Clear the session data after reading
            $GLOBALS['BE_USER']->setAndSaveSessionData($queueIdentifier, null);

            foreach ($sessionData as $messageData) {
                $data = json_decode($messageData, true);
                if (is_array($data)) {
                    $severityValue = $data['severity'] ?? ContextualFeedbackSeverity::OK->value;
                    $severity = ContextualFeedbackSeverity::tryFrom($severityValue) ?? ContextualFeedbackSeverity::OK;

                    $result[] = [
                        'title' => $data['title'] ?? '',
                        'message' => $data['message'] ?? '',
                        'severity' => $severity->name,
                    ];
                }
            }
        }

        return $result;
    }
}
