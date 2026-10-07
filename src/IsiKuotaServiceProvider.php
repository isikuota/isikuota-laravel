<?php

namespace IsiKuota\Laravel;

use IsiKuota;
use IsiKuota\Laravel\Console\BalanceCommand;
use IsiKuota\Laravel\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class IsiKuotaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/isikuota.php', 'isikuota');

        // Singleton: client menyimpan signature yang sudah dipakai di detik berjalan,
        // jadi instance yang sama harus dipakai untuk semua request dalam satu proses.
        $this->app->singleton(IsiKuota::class, function ($app) {
            $config = $app['config']->get('isikuota');

            foreach (['api_id', 'api_key', 'secret_key'] as $key) {
                if (empty($config[$key])) {
                    throw new InvalidArgumentException(
                        'Config isikuota.' . $key . ' belum diisi. Set ISIKUOTA_' . strtoupper($key) . ' di .env.'
                    );
                }
            }

            return new IsiKuota($config['api_id'], $config['api_key'], $config['secret_key'], [
                'base_url' => $config['base_url'],
                'testing' => $config['testing'],
                'timeout' => $config['timeout'],
                'connect_timeout' => $config['connect_timeout'],
                'verify_ssl' => $config['verify_ssl'],
                'throw_on_error' => $config['throw_on_error'],
            ]);
        });

        $this->app->alias(IsiKuota::class, 'isikuota');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/isikuota.php' => config_path('isikuota.php'),
            ], 'isikuota-config');

            $this->commands([BalanceCommand::class]);
        }

        $webhook = $this->app['config']->get('isikuota.webhook', []);
        if (!empty($webhook['enabled'])) {
            Route::post($webhook['path'], WebhookController::class)
                ->middleware($webhook['middleware'] ?? [])
                ->name('isikuota.webhook');
        }
    }
}
