<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kredensial
    |--------------------------------------------------------------------------
    | Dashboard IsiKuota -> Pengaturan -> API. secret_key juga dipakai untuk
    | memverifikasi webhook.
    */
    'api_id' => env('ISIKUOTA_API_ID'),
    'api_key' => env('ISIKUOTA_API_KEY'),
    'secret_key' => env('ISIKUOTA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Opsi client
    |--------------------------------------------------------------------------
    | testing = true: simulasi, saldo tidak terpotong. Matikan di production.
    */
    'base_url' => env('ISIKUOTA_BASE_URL', 'https://api.isikuota.com/v1'),
    'testing' => (bool) env('ISIKUOTA_TESTING', false),
    'timeout' => (int) env('ISIKUOTA_TIMEOUT', 30),
    'connect_timeout' => (int) env('ISIKUOTA_CONNECT_TIMEOUT', 10),
    'verify_ssl' => (bool) env('ISIKUOTA_VERIFY_SSL', true),

    // true: respons success=false dilempar sebagai IsiKuotaException.
    'throw_on_error' => (bool) env('ISIKUOTA_THROW_ON_ERROR', false),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    | URL lengkapnya: APP_URL/{path}. Daftarkan di Dashboard -> Pengaturan ->
    | API -> Webhook (wajib HTTPS di production).
    |
    | Route ini sengaja tanpa middleware web, jadi bebas CSRF. Tambahkan
    | middleware sendiri lewat 'middleware' bila perlu (mis. throttle).
    |
    | Handler: dengarkan event IsiKuota\Laravel\Events\TransactionUpdated dan
    | IsiKuota\Laravel\Events\DepositUpdated. Balasan 200 dikirim sesudah event
    | dipanggil; untuk pekerjaan berat, pakai listener yang ShouldQueue
    | (IsiKuota mengulang 3x bila tidak dibalas 2xx dalam 15 detik).
    */
    'webhook' => [
        'enabled' => (bool) env('ISIKUOTA_WEBHOOK_ENABLED', true),
        'path' => env('ISIKUOTA_WEBHOOK_PATH', 'webhook/isikuota'),
        'middleware' => [],

        // Pengiriman ganda dibuang berdasarkan event_id (disimpan di cache).
        'dedupe_store' => env('ISIKUOTA_WEBHOOK_CACHE_STORE'), // null = cache default
        'dedupe_ttl' => (int) env('ISIKUOTA_WEBHOOK_DEDUPE_TTL', 86400), // detik
    ],
];
