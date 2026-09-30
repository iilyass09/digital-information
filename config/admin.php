<?php

$seedDefaultsEnabled = in_array(env('APP_ENV', 'production'), ['local', 'testing'], true);

return [
    'seed' => [
        'name' => env('ADMIN_NAME') ?: ($seedDefaultsEnabled ? 'Administrator' : null),
        'username' => env('ADMIN_USERNAME') ?: ($seedDefaultsEnabled ? 'admin' : null),
        'email' => env('ADMIN_EMAIL') ?: ($seedDefaultsEnabled ? 'admin@example.com' : null),
        'password' => env('ADMIN_PASSWORD') ?: ($seedDefaultsEnabled ? 'admin' : null),
    ],
];
