<?php

namespace IsiKuota\Laravel\Tests;

use IsiKuota;
use IsiKuota\Laravel\Facades\IsiKuota as IsiKuotaFacade;
use InvalidArgumentException;

class ServiceProviderTest extends TestCase
{
    public function test_client_is_bound_as_singleton_with_alias(): void
    {
        $client = $this->app->make(IsiKuota::class);

        $this->assertInstanceOf(IsiKuota::class, $client);
        $this->assertSame($client, $this->app->make(IsiKuota::class));
        $this->assertSame($client, $this->app->make('isikuota'));
    }

    public function test_facade_resolves_client(): void
    {
        $this->assertInstanceOf(IsiKuota::class, IsiKuotaFacade::getFacadeRoot());
        $this->assertMatchesRegularExpression('/^TRX\d{14}[0-9A-F]{6}$/', IsiKuotaFacade::generateRefId());
    }

    public function test_signature_matches_documented_formula(): void
    {
        // HMAC-SHA256( api_id|api_key|timestamp|sha256(json terurut A-Z), secret )
        $params = ['customer_no' => '08123456789', 'cmd' => 'prepaid', 'code' => 'TSEL5'];
        $body = '{"cmd":"prepaid","code":"TSEL5","customer_no":"08123456789"}';
        $expected = hash_hmac('sha256', 'id_test|key_test|1748678400|' . hash('sha256', $body), self::SECRET);

        $this->assertSame($expected, $this->app->make(IsiKuota::class)->sign($params, 1748678400));
    }

    public function test_missing_credentials_throw_helpful_error(): void
    {
        $this->app['config']->set('isikuota.secret_key', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ISIKUOTA_SECRET_KEY');

        $this->app->make(IsiKuota::class);
    }

    public function test_webhook_route_is_registered_by_default(): void
    {
        $this->assertTrue($this->app['router']->has('isikuota.webhook'));
    }
}
