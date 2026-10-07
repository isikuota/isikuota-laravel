<?php

namespace IsiKuota\Laravel\Tests;

use IsiKuota\Laravel\IsiKuotaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected const SECRET = 'secret_test';

    protected function getPackageProviders($app): array
    {
        return [IsiKuotaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('isikuota.api_id', 'id_test');
        $app['config']->set('isikuota.api_key', 'key_test');
        $app['config']->set('isikuota.secret_key', self::SECRET);
        $app['config']->set('cache.default', 'array');
    }

    /** Kirim webhook dengan signature yang dihitung seperti IsiKuota. */
    protected function postWebhook(array $payload, ?string $signature = null)
    {
        $raw = json_encode($payload);
        $signature = $signature ?? 'sha256=' . hash_hmac('sha256', $raw, self::SECRET);

        return $this->call('POST', '/webhook/isikuota', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ISIKUOTA_SIGNATURE' => $signature,
        ], $raw);
    }
}
