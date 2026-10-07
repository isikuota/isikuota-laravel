<?php

namespace IsiKuota\Laravel\Tests;

use IsiKuota\Laravel\Events\DepositUpdated;
use IsiKuota\Laravel\Events\TransactionUpdated;
use Illuminate\Support\Facades\Event;

class WebhookTest extends TestCase
{
    private function transactionPayload(string $eventId = 'evt_1'): array
    {
        return [
            'event' => 'transaction.updated',
            'event_id' => $eventId,
            'timestamp' => '2026-10-07T10:00:00+07:00',
            'data' => ['ref_id' => 'TRX1', 'status' => 'success', 'sn' => 'SN123'],
        ];
    }

    public function test_valid_transaction_event_is_dispatched(): void
    {
        Event::fake([TransactionUpdated::class, DepositUpdated::class]);

        $this->postWebhook($this->transactionPayload())
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        Event::assertDispatched(TransactionUpdated::class, function ($e) {
            return $e->eventId === 'evt_1' && $e->refId() === 'TRX1' && $e->isSuccess();
        });
        Event::assertNotDispatched(DepositUpdated::class);
    }

    public function test_deposit_event_is_dispatched(): void
    {
        Event::fake([TransactionUpdated::class, DepositUpdated::class]);

        $this->postWebhook([
            'event' => 'deposit.updated',
            'event_id' => 'evt_2',
            'timestamp' => '2026-10-07T10:00:00+07:00',
            'data' => ['ref_id' => 'DP1', 'status' => 'success'],
        ])->assertOk();

        Event::assertDispatched(DepositUpdated::class, fn ($e) => $e->refId() === 'DP1' && $e->isSuccess());
        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        Event::fake([TransactionUpdated::class]);

        $this->postWebhook($this->transactionPayload(), 'sha256=salah')->assertStatus(401);
        $this->postWebhook($this->transactionPayload(), '')->assertStatus(401);

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_duplicate_event_id_is_processed_once(): void
    {
        Event::fake([TransactionUpdated::class]);

        $this->postWebhook($this->transactionPayload('evt_dup'))->assertJson(['status' => 'ok']);
        $this->postWebhook($this->transactionPayload('evt_dup'))->assertJson(['status' => 'already_processed']);

        Event::assertDispatchedTimes(TransactionUpdated::class, 1);
    }

    public function test_failed_listener_releases_dedupe_so_retry_is_processed(): void
    {
        $calls = 0;
        $this->app['events']->listen(TransactionUpdated::class, function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw new \RuntimeException('boom');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->postWebhook($this->transactionPayload('evt_retry'));
            $this->fail('Exception seharusnya dilempar.');
        } catch (\RuntimeException $e) {
            // listener gagal pada percobaan pertama
        }

        $this->postWebhook($this->transactionPayload('evt_retry'))->assertJson(['status' => 'ok']);
        $this->assertSame(2, $calls);
    }

    public function test_unknown_event_is_acknowledged(): void
    {
        $this->postWebhook([
            'event' => 'something.new',
            'event_id' => 'evt_3',
            'data' => ['x' => 1],
        ])->assertOk();
    }

    public function test_malformed_payload_returns_400(): void
    {
        $raw = 'bukan json';
        $sig = 'sha256=' . hash_hmac('sha256', $raw, self::SECRET);

        $this->call('POST', '/webhook/isikuota', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ISIKUOTA_SIGNATURE' => $sig,
        ], $raw)->assertStatus(400);
    }
}
