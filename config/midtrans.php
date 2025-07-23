<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'sanbox_url' => env('MIDTRANS_SANDBOX_BASE_URL', 'https://app.sandbox.midtrans.com'),
    'production_url' => env('MIDTRANS_PRODUCTION_BASE_URL', 'https://app.midtrans.com'),
];