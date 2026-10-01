<?php

$environment = env('APP_ENV', 'production');
$seedDefaultsEnabled = in_array($environment, ['local', 'testing'], true);

return [
    'seed' => [
        'name' => env('ADMIN_NAME') ?: ($seedDefaultsEnabled ? 'Administrator' : null),
        'username' => env('ADMIN_USERNAME') ?: ($seedDefaultsEnabled ? 'admin' : null),
        'email' => env('ADMIN_EMAIL') ?: ($seedDefaultsEnabled ? 'admin@example.com' : null),
        'password' => env('ADMIN_PASSWORD') ?: ($seedDefaultsEnabled ? 'admin' : null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Database Dump
    |--------------------------------------------------------------------------
    |
    | When an administrator uploads an image the database changes: the row now
    | points at a filename that must travel with the project. The board is
    | often run from different machines, so a portable SQL snapshot is refreshed
    | automatically and can be copied over and replayed with db:restore.
    |
    | Disabled while testing: the suite writes to a throwaway database, so a
    | refresh there would overwrite the real snapshot with fixture data.
    |
    */

    'auto_dump' => $environment === 'testing' ? false : (bool) env('AUTO_DB_DUMP', true),

    /*
    |--------------------------------------------------------------------------
    | Portable Dump Path
    |--------------------------------------------------------------------------
    |
    | Absolute path of the SQL snapshot used to move the board between
    | machines. Keep it outside git; only the single file needs copying.
    |
    */

    'dump_path' => env('DB_DUMP_PATH') ?: database_path('dumps'.DIRECTORY_SEPARATOR.'database.sql'),
];
