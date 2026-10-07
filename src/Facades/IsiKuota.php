<?php

namespace IsiKuota\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array balance()
 * @method static array products(string $type)
 * @method static array productsPrepaid()
 * @method static array productsGame()
 * @method static array productsPostpaid()
 * @method static array orderPrepaid(string $code, string $customerNo, ?string $refId = null, ?int $nominal = null, ?string $voucherCode = null)
 * @method static array orderGame(string $code, string $customerNo, ?string $refId = null, ?string $voucherCode = null)
 * @method static array inquiryBill(string $code, string $customerNo, ?string $refId = null)
 * @method static array payBill(string $code, string $customerNo, string $refId, ?string $voucherCode = null)
 * @method static array status(string $refId)
 * @method static array waitForFinalStatus(string $refId, int $maxSeconds = 120, int $interval = 10)
 * @method static array accountInquiry(string $code, string $customerNo)
 * @method static array depositMethods()
 * @method static array depositCreate(string $code, int $amount)
 * @method static array depositStatus(string $refId)
 * @method static array depositCancel(string $refId)
 * @method static bool verifyWebhookSignature(string $rawBody, ?string $signatureHeader)
 * @method static string generateRefId(string $prefix = 'TRX')
 * @method static \IsiKuota setTesting(bool $testing)
 * @method static bool isFinalStatus(?string $status)
 *
 * @see \IsiKuota
 */
class IsiKuota extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IsiKuota::class;
    }
}
