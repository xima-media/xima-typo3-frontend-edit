<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * This ViewHelper generates an hidden input element which holds data values for the frontend edit dropdown menu.
 * You need either provide a uid and a table for the corresponding edit link or an external url.
 * The output will only be rendered if the frontend edit is enabled.
 *
 * Usages:
 * ```html
 * <xtfe:data label="News Title" uid="12" table="tx_news_domain_model_news" icon="content-news" />
 * ```
 *
 * Output:
 * ```html
 * <input type="hidden" class="xima-typo3-frontend-edit--data" value="{"label": "News Title", "uid": 12, "table": "tx_news_domain_model_news", "icon": "content-news"}" />
 * ```
 */
class DataViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

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
            true
        );
        $this->registerArgument(
            'uid',
            'integer',
            'UID of data entry',
        );
        $this->registerArgument(
            'table',
            'integer',
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
            ''
        );
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (!$GLOBALS['BE_USER'] || $GLOBALS['BE_USER']->user['tx_ximatypo3frontendedit_disable']) {
            return '';
        }

        if (empty($arguments['uid']) && empty($arguments['url'])) {
            return '';
        }

        $dataAttributes = [];
        $class = '';

        foreach ($arguments as $key => $value) {
            if ($key === 'class') {
                $class = $value;
            } else {
                $dataAttributes[$key] = $value;
            }
        }
        return sprintf('<input type="hidden" class="xima-typo3-frontend-edit--data %s" value="%s" />', $class, htmlentities(json_encode($dataAttributes), ENT_QUOTES));
    }
}
