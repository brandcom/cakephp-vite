<?php

return [
    'VitePlugin' => [
        'forceProductionMode' => false,
        // deprecated, use devHostNeedles and pass a list of strings.
        'devHostNeedle' => null,
        'devHostNeedles' => [
            '.test',
            'localhost',
            '127.0.0.1',
        ],
        // for Cookies or URL-params to force production mode
        'productionHint' => 'vprod',
        'devPort' => 3000,
        'jsSrcDirectory' => 'webroot_src' . DS,
        'mainJs' => 'main.js',
        'manifestDir' => 'manifest.json',
    ],
];
