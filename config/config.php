<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'MEGA LANCHES ERP',
        'url' => 'http://localhost/bruno/public',
        'timezone' => 'America/Sao_Paulo',
        'env' => 'local',
        'debug' => true,
        'session_name' => 'mega_lanches_session',
    ],
    'security' => [
        'csrf_key' => '_token',
        'password_min_length' => 6,
    ],
];
