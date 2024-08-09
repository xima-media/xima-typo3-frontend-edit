<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\TypoScript;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class FrontendEditConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{

    public function getFunctions(): array
    {
        return [
            $this->getWebserviceFunction(),
        ];
    }

    protected function getWebserviceFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'isFrontendEditEnabled',
            static fn () => null,
            static function () {
                return (bool)$GLOBALS['BE_USER'];
            }
        );
    }
}
