<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\TypoScript;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

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
                return $GLOBALS['BE_USER'];
            }
        );
    }
}
