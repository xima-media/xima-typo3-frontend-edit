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

namespace Xima\XimaTypo3FrontendEdit\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function array_key_exists;
use function sprintf;

/**
 * DataViewHelper.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class DataViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'label',
            'string',
            'Label for data entry',
            true,
        );
        $this->registerArgument(
            'uid',
            'integer',
            'UID of data entry',
        );
        $this->registerArgument(
            'table',
            'string',
            'Table of data entry, e.g. tx_news_domain_model_news',
        );
        $this->registerArgument(
            'url',
            'string',
            'External edit url of data entry',
        );
        $this->registerArgument(
            'icon',
            'string',
            'Icon identifier for data entry',
        );
        $this->registerArgument(
            'class',
            'string',
            'Additional class for the hidden input element',
            false,
            '',
        );
    }

    public function render(): string
    {
        if (
            null === $GLOBALS['BE_USER']
            || (
                array_key_exists('tx_ximatypo3frontendedit_disable', $GLOBALS['BE_USER']->user)
                && $GLOBALS['BE_USER']->user['tx_ximatypo3frontendedit_disable']
            )
        ) {
            return '';
        }
        if (
            null === $this->arguments['uid']
            && null === $this->arguments['url']
        ) {
            return '';
        }
        $dataAttributes = [];
        $class = '';
        foreach ($this->arguments as $key => $value) {
            if ('class' === $key) {
                $class = $value;
            } else {
                $dataAttributes[$key] = $value;
            }
        }

        return sprintf(
            '<input type="hidden" class="xima-typo3-frontend-edit--data %s" value="%s" />',
            $class,
            htmlentities(false !== json_encode($dataAttributes) ? json_encode($dataAttributes) : '', \ENT_QUOTES),
        );
    }
}
