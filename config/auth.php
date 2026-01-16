<?php

return [
    
    'defaults' => [
        'guard' => 'admin',
        'passwords' => 'admins',
    ],

    'guards' => [
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],

        'customer' => [
            'driver' => 'jwt',
            'provider' => 'customers',
        ],

        'driver' => [
            'driver' => 'jwt',
            'provider' => 'drivers',
        ],

        'merchant' => [
            'driver' => 'jwt',
            'provider' => 'merchants',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\CustomerModel\Customer::class,
        ],
        'merchants' => [
            'driver' => 'eloquent',
            'model' => App\Models\MerchantModels\Merchant::class,
        ],
        'drivers' => [
            'driver' => 'eloquent',
            'model' => App\Models\DriverModels\Driver::class,
        ],
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'customers' => [
            'provider' => 'customers',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'merchants' => [
            'provider' => 'merchants',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'drivers' => [
            'provider' => 'drivers',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];