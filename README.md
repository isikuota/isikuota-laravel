# IsiKuota untuk Laravel

Integrasi Laravel untuk [IsiKuota API](https://isikuota.com/docs): facade, config, route webhook dengan verifikasi signature dan anti-ganda, serta event Laravel.

Paket ini membungkus [isikuota-php](../isikuota-php); seluruh method client tersedia lewat facade.

- PHP 8.1+, Laravel 10 / 11 / 12

## Instalasi

```bash
composer require isikuota/laravel
php artisan vendor:publish --tag=isikuota-config   # opsional
```

Isi `.env` (Dashboard IsiKuota -> Pengaturan -> API):

```env
ISIKUOTA_API_ID=
ISIKUOTA_API_KEY=
ISIKUOTA_SECRET_KEY=
ISIKUOTA_TESTING=true   # simulasi, saldo tidak terpotong. Hapus / false di production.
```

Uji kredensial, jam server, dan whitelist IP:

```bash
php artisan isikuota:balance
```

## Pemakaian

```php
use IsiKuota\Laravel\Facades\IsiKuota;

$saldo = IsiKuota::balance();
$produk = IsiKuota::productsPrepaid();

// Simpan ref_id ke database SEBELUM order dikirim.
$refId = IsiKuota::generateRefId('INV');
$res = IsiKuota::orderPrepaid('TSEL5', '08123456789', $refId);

if ($res['success']) {
    $res['data']['status']; // success | pending | failed  <- baca ini, bukan 'code'
}
```

Atau lewat dependency injection:

```php
public function store(\IsiKuota $isikuota) { ... }
```

Daftar method lengkap (pascabayar, cek nama, deposit, penanganan timeout, dll.) ada di [README isikuota-php](../isikuota-php/README.md). Aturan pentingnya berlaku sama: timeout bukan gagal (`IsiKuotaException::isTimeout()` -> cek `status($refId)`, jangan order ulang) dan jangan polling status lebih sering dari 10 detik.

## Webhook

Route `POST /webhook/isikuota` terdaftar otomatis (nama route: `isikuota.webhook`). Daftarkan URL lengkapnya di Dashboard -> Pengaturan -> API -> Webhook (HTTPS di production). Route ini bebas CSRF.

Controller bawaan akan:

1. Memverifikasi `X-Isikuota-Signature` terhadap raw body (salah -> HTTP 401).
2. Membuang pengiriman ganda berdasarkan `event_id` (disimpan di cache).
3. Memanggil event Laravel, lalu membalas HTTP 200.

Dengarkan event-nya:

```php
use IsiKuota\Laravel\Events\DepositUpdated;
use IsiKuota\Laravel\Events\TransactionUpdated;
use Illuminate\Support\Facades\Event;

Event::listen(TransactionUpdated::class, function (TransactionUpdated $e) {
    $order = Order::where('ref_id', $e->refId())->first();

    if ($e->isSuccess()) {
        $order->update(['status' => 'success', 'sn' => $e->data['sn'] ?? null]);
    } elseif ($e->isFailed()) {
        $order->update(['status' => 'failed']); // saldo sudah dikembalikan IsiKuota
    }
});

Event::listen(DepositUpdated::class, function (DepositUpdated $e) {
    // status: success | failed | expired | cancelled (tidak dikirim untuk pending)
});
```

Untuk pekerjaan berat, buat listener `ShouldQueue` supaya webhook dibalas cepat (batas 15 detik; IsiKuota mengulang 3x bila tidak dibalas 2xx). Bila listener melempar exception, kunci anti-ganda dilepas sehingga pengulangan dari IsiKuota tetap diproses. Event dengan nama yang belum dikenal tetap dibalas 200.

> Dedup memakai cache. Di production pakai store yang dibagi antar server (Redis/database), bukan `array`/`file` bila ada lebih dari satu server. Atur lewat `ISIKUOTA_WEBHOOK_CACHE_STORE`.

## Konfigurasi

Semua opsi ada di `config/isikuota.php`.

| Env | Default | Keterangan |
|---|---|---|
| `ISIKUOTA_API_ID` / `_API_KEY` / `_SECRET_KEY` | | Wajib |
| `ISIKUOTA_TESTING` | `false` | Mode simulasi |
| `ISIKUOTA_TIMEOUT` | `30` | Detik |
| `ISIKUOTA_CONNECT_TIMEOUT` | `10` | Detik |
| `ISIKUOTA_VERIFY_SSL` | `true` | Jangan dimatikan di production |
| `ISIKUOTA_THROW_ON_ERROR` | `false` | `success: false` dilempar sebagai `IsiKuotaException` |
| `ISIKUOTA_WEBHOOK_ENABLED` | `true` | Matikan bila ingin route sendiri |
| `ISIKUOTA_WEBHOOK_PATH` | `webhook/isikuota` | |
| `ISIKUOTA_WEBHOOK_CACHE_STORE` | cache default | Store untuk dedup |
| `ISIKUOTA_WEBHOOK_DEDUPE_TTL` | `86400` | Detik |

Middleware webhook (mis. throttle) diatur lewat `isikuota.webhook.middleware` di file config.

## Catatan keamanan

- IP server harus sudah di-whitelist di Dashboard, dan jam server sinkron (NTP, toleransi +-30 detik).
- Jangan commit `.env`. `api_key` dan `secret_key` tidak boleh ada di repositori.

## Pengembangan

```bash
composer install
composer test
```

`composer.json` memakai path repository `../isikuota-php` untuk pengembangan lokal. Hapus bagian `repositories` setelah `isikuota-php` dipublikasikan ke Packagist.
