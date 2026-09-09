<?php

return [
    'admin' => [
        'email' => env(
            'VDB_DEV_ADMIN_EMAIL',
        ),
        'default_password' => env(
            'VDB_DEV_ADMIN_DEFAULT_PASSWORD',
        ),
        'totp_secret' => env(
            'VDB_DEV_ADMIN_TOTP_SECRET',
        ),
    ],
];
