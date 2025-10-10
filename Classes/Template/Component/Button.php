<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Template\Component;

use TYPO3\CMS\Core\Imaging\Icon;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;

use function array_key_exists;
use function array_slice;

/**
 * Button.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
class Button
{
    protected string $label;
    protected ButtonType $type;
    protected ?string $url;
    protected ?Icon $icon;
    protected array $children;
    protected bool $targetBlank = false;

    public function __construct(string $label, ButtonType $type, ?string $url = null, ?Icon $icon = null, bool $targetBlank = false)
    {
        $this->label = $label;
        $this->type = $type;
        $this->url = $url;
        $this->icon = $icon;
        $this->children = [];
        $this->targetBlank = $targetBlank;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getType(): ButtonType
    {
        return $this->type;
    }

    public function setType(ButtonType $type): void
    {
        $this->type = $type;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): void
    {
        $this->icon = $icon;
    }

    public function isTargetBlank(): bool
    {
        return $this->targetBlank;
    }

    public function setTargetBlank(bool $targetBlank): void
    {
        $this->targetBlank = $targetBlank;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function appendChild(self $button, string|int $key): void
    {
        $this->children[$key] = $button;
    }

    public function appendAfterChild(self $button, string|int $appendAfterKey, string|int $key): void
    {
        if (!array_key_exists($appendAfterKey, $this->children)) {
            $this->children[$key] = $button;

            return;
        }

        $keys = array_keys($this->children);
        $keyPositions = array_flip($keys);
        $offset = $keyPositions[$appendAfterKey] + 1;

        $this->children = array_slice($this->children, 0, $offset, true) +
            [$key => $button] +
            array_slice($this->children, $offset, null, true);
    }

    public function removeChild(string|int $key): void
    {
        unset($this->children[$key]);
    }

    public function render(): array
    {
        $result = [
            'label' => $GLOBALS['LANG']->sL($this->label),
            'type' => $this->type->value,
        ];

        if (null !== $this->url && '' !== $this->url) {
            $result['url'] = $this->url;
            $result['targetBlank'] = $this->targetBlank;
        }

        if ($this->icon instanceof Icon) {
            $result['icon'] = $this->icon->getAlternativeMarkup('inline');
        }

        if ([] !== $this->children) {
            $result['children'] = array_map(static fn (Button $button) => $button->render(), $this->children);
        }

        return $result;
    }
}
