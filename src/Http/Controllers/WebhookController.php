<?php

namespace IsiKuota\Laravel\Http\Controllers;

use IsiKuota;
use IsiKuota\Laravel\Events\DepositUpdated;
use IsiKuota\Laravel\Events\TransactionUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebhookController
{
    public function __invoke(Request $request, IsiKuota $client): JsonResponse
    {
        // Signature dihitung dari RAW body, bukan JSON hasil decode.
        $rawBody = $request->getContent();

        if (!$client->verifyWebhookSignature($rawBody, $request->header('X-Isikuota-Signature'))) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || empty($payload['event']) || !isset($payload['data']) || !is_array($payload['data'])) {
            return response()->json(['status' => 'invalid_payload'], 400);
        }

        $eventId = (string) ($payload['event_id'] ?? '');

        // Anti-ganda. Cache::add atomik: hanya satu request yang "menang" untuk event_id yang sama.
        // Kunci dilepas lagi bila listener melempar exception, supaya retry dari IsiKuota diproses.
        $dedupeKey = null;
        if ($eventId !== '') {
            $store = config('isikuota.webhook.dedupe_store');
            $cache = $store ? Cache::store($store) : Cache::store();
            $dedupeKey = 'isikuota:webhook:' . $eventId;

            if (!$cache->add($dedupeKey, 1, (int) config('isikuota.webhook.dedupe_ttl', 86400))) {
                return response()->json(['status' => 'already_processed']);
            }
        }

        try {
            switch ($payload['event']) {
                case 'transaction.updated':
                    event(new TransactionUpdated($eventId, $payload['data'], $payload));
                    break;
                case 'deposit.updated':
                    event(new DepositUpdated($eventId, $payload['data'], $payload));
                    break;
                default:
                    // Event baru dari IsiKuota yang belum dikenal: terima saja (2xx) agar tidak diulang.
                    break;
            }
        } catch (\Throwable $e) {
            if ($dedupeKey !== null) {
                $cache->forget($dedupeKey);
            }
            throw $e;
        }

        return response()->json(['status' => 'ok']);
    }
}
