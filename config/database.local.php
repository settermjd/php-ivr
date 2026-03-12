<?php

declare(strict_types=1);

return [
    'db' => [
        'driver' => 'pdo_sqlite',
        'connection' => [
            'dsn'    => __DIR__ . '/../data/database/database.sqlite3',
        ]
    ],
];
