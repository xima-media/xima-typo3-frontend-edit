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

namespace Xima\XimaTypo3FrontendEdit\Event;

use InvalidArgumentException;

use function array_key_exists;
use function is_array;
use function is_scalar;
use function preg_match;
use function sprintf;

/**
 * FrontendEditDataEnrichmentEvent.
 *
 * Dispatched once per editInformationAction request, before the per-element
 * dropdown is built, with the full filtered content element list - so a
 * listener backed by a database can resolve its data in a single query
 * instead of being invoked once per element with no way to batch.
 *
 * Listeners attach namespaced, serializable data via addElementData(); it is
 * merged into the JSON payload under element['_ext'][$namespace], and from
 * there reaches the frontend in the `xfe:element-rendered` event's
 * `detail.payload.element._ext` (see Documentation/DeveloperCorner/JavaScriptApi.rst).
 * Namespacing prevents key collisions between listeners; core frontend-edit
 * keys are never exposed under `_ext`.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class FrontendEditDataEnrichmentEvent
{
    final public const NAME = 'xima_typo3_frontend_edit.frontend_edit.data.enrichment';

    /** @var array<int, array<string, mixed>> */
    private array $elementData = [];

    public function __construct(
        /** @var array<int, array<string, mixed>> content element rows, keyed by uid */
        protected readonly array $contentElements,
        protected readonly int $pageId,
        protected readonly int $languageUid,
        protected readonly string $returnUrl,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContentElements(): array
    {
        return $this->contentElements;
    }

    /**
     * @return list<int>
     */
    public function getContentElementUids(): array
    {
        return array_map(intval(...), array_keys($this->contentElements));
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getLanguageUid(): int
    {
        return $this->languageUid;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * @param array<string, mixed> $data must be serializable: scalar, null, or arrays thereof
     *
     * @throws InvalidArgumentException if $uid is not part of this request, $namespace
     *                                  is not a lowercase identifier, or $data is not serializable
     */
    public function addElementData(int $uid, string $namespace, array $data): void
    {
        if (!array_key_exists($uid, $this->contentElements)) {
            throw new InvalidArgumentException(sprintf('Unknown content element uid %d for this request', $uid), 1732000001);
        }
        if (1 !== preg_match('/^[a-z][a-z0-9_]*$/', $namespace)) {
            throw new InvalidArgumentException(sprintf('Invalid data namespace "%s" - expected lowercase letters, digits and underscores, starting with a letter', $namespace), 1732000002);
        }
        $this->assertSerializable($data);

        $this->elementData[$uid][$namespace] = $data;
    }

    /**
     * @return array<string, mixed> namespace => data, empty when no listener attached anything for this uid
     */
    public function getElementData(int $uid): array
    {
        return $this->elementData[$uid] ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertSerializable(array $data): void
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                $this->assertSerializable($value);
                continue;
            }
            if (!is_scalar($value) && null !== $value) {
                throw new InvalidArgumentException('Data enrichment values must be scalar, null, or arrays thereof', 1732000003);
            }
        }
    }
}
