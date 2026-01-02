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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use JsonException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

use function is_array;

/**
 * FlashMessageService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class FlashMessageService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

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
    public function collectFromSession(): array
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

            if (!is_array($sessionData) || [] === $sessionData) {
                continue;
            }

            // Process all messages first, only clear session after successful processing
            foreach ($sessionData as $messageData) {
                try {
                    $data = json_decode((string) $messageData, true, 512, \JSON_THROW_ON_ERROR);
                    if (is_array($data)) {
                        $severityValue = $data['severity'] ?? ContextualFeedbackSeverity::OK->value;
                        $severity = ContextualFeedbackSeverity::tryFrom($severityValue) ?? ContextualFeedbackSeverity::OK;

                        $result[] = [
                            'title' => $data['title'] ?? '',
                            'message' => $data['message'] ?? '',
                            'severity' => $severity->name,
                        ];
                    }
                } catch (JsonException $e) {
                    $this->logger->warning('Failed to decode flash message from session', [
                        'queue' => $queueIdentifier,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clear the session data only after processing all messages
            $GLOBALS['BE_USER']->setAndSaveSessionData($queueIdentifier, null);
        }

        return $result;
    }
}
