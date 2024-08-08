<?php

return [
    'editable_content_elements' => [
        'path' => '/editable-content-elements',
        'access' => 'public',
        'target' => \Xima\XimaTypo3FrontendEdit\Controller\EditController::class . '::getContentElementsByPage',
    ],
];
