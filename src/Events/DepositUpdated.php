<?php

namespace IsiKuota\Laravel\Events;

/**
 * Webhook deposit.updated. Hanya dikirim untuk status final
 * (success | failed | expired | cancelled), tidak untuk pending.
 */
class DepositUpdated
{
    /** @var string */
    public $eventId;

    /** @var array */
    public $data;

    /** @var array Payload lengkap: event, event_id, timestamp, data */
    public $payload;

    public function __construct(string $eventId, array $data, array $payload)
    {
        $this->eventId = $eventId;
        $this->data = $data;
        $this->payload = $payload;
    }

    public function refId(): ?string
    {
        return $this->data['ref_id'] ?? null;
    }

    public function status(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function isSuccess(): bool
    {
        return $this->status() === 'success';
    }
}
