<?php

return [
    'project.path' => __DIR__ . '/../application',
    'cache.temporary' => __DIR__ . '/../../cache',

    'routing.regex.wsdl' => [
        '/\/soap-rpc\/([\w\-]+)\/([\w\-]+)\-([\d\.]+)\.wsdl$/' => ['endpoint', 'endpoint', 'version']
    ],

    'routing.regex.php' => [
        '/\/v([\d\.]+)\/([\w]+)\/API.class.php$/' => ['version', 'endpoint']
    ],

    'routing.transform.endpoint' => ([
        ['_full', '']
    ])
];
