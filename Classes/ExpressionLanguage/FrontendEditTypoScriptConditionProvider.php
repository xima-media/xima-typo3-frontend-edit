<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\ExpressionLanguage;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;
use Xima\XimaTypo3FrontendEdit\TypoScript\FrontendEditConditionFunctionsProvider;

class FrontendEditTypoScriptConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            FrontendEditConditionFunctionsProvider::class,
        ];
    }
}
