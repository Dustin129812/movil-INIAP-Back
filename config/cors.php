<?php

return [
    'paths' => ['api/*', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://simpagi.online',
        'https://denunciatory-mundane-cannon.ngrok-free.dev',
        'exp://',
        'metro://',

    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*', 'X-Guest-ID'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
