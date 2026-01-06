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
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 10000,
            'throttle' => 10000,
        ],

        'customers' => [
            'provider' => 'customers',
            'table' => 'password_reset_tokens',
            'expire' => 10000,
            'throttle' => 10000,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];