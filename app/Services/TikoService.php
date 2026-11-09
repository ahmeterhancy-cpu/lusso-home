<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tiko sanal POS (3D Secure). Akış Kıbrıs Biletcim ile aynı:
 *  1. Kart formu müşterinin tarayıcısından DOĞRUDAN Tiko pay3d adresine gönderilir
 *     (kart bilgisi bizim sunucumuza hiç uğramaz).
 *  2. Tiko UrlOk / UrlFail adresine döner; dönüşe güvenilmez, her zaman
 *     payment/status sorgusuyla (imzalı yanıt) doğrulanır.
 *  3. Tiko ayrıca sunucudan sunucuya callback gönderebilir; o da imzayla doğrulanır.
 */
class TikoService
{
    public function merchantId(): string
    {
        return (string) (setting('tiko_merchant_id') ?: config('services.tiko.merchant_id'));
    }

    public function isTest(): bool
    {
        $v = setting('tiko_test_mode');

        return $v !== null ? $v === '1' : (bool) config('services.tiko.test_mode');
    }

    public function configured(): bool
    {
        return $this->merchantId() !== '' && $this->secret() !== '';
    }

    /** Kimlik bilgisi yokken yerelde sahte ödeme ekranı açılabilir (canlıda kapalı olmalı). */
    public function simulation(): bool
    {
        return ! $this->configured() && (bool) config('services.tiko.allow_simulation');
    }

    private function secret(): string
    {
        return (string) (setting('tiko_secret_key') ?: config('services.tiko.secret_key'));
    }

    private function password(): string
    {
        return (string) (setting('tiko_password') ?: config('services.tiko.password'));
    }

    public function baseUrl(): string
    {
        return $this->isTest()
            ? 'https://www.tikokart.com/api-sanalpos-sandbox'
            : 'https://www.tikokart.com/api-sanalpos';
    }

    public function hash(string $str): string
    {
        return base64_encode(hash_hmac('sha256', $str.$this->password(), $this->secret(), true));
    }

    /** pay3d formunun gizli alanları (kart alanları formda müşteri tarafından doldurulur). */
    public function formFields(Order $order, string $userIp): array
    {
        $amount = number_format((float) $order->total, 2, '.', '');
        $currency = 'TRY';
        $installment = '0';
        $isTest = $this->isTest() ? '1' : '0';
        $urlOk = route('tiko.return', ['order' => $order->number, 'status' => 'ok']);
        $urlFail = route('tiko.return', ['order' => $order->number, 'status' => 'fail']);
        $merchant = $this->merchantId();

        return [
            'MerchantId' => $merchant,
            'OrderId' => $order->number,
            'Currency' => $currency,
            'Amount' => $amount,
            'Installment' => $installment,
            'UrlOk' => $urlOk,
            'UrlFail' => $urlFail,
            'CardType' => '',
            'UserIp' => $userIp,
            'UserName' => $order->first_name.' '.$order->last_name,
            'UserEmail' => $order->email,
            'UserPhone' => $order->phone,
            'UserLang' => 'tr',
            'Description' => 'Lusso Home Concept - '.$order->number,
            'IsTest' => $isTest,
            'Hash' => $this->hash($merchant.$userIp.$order->number.$urlOk.$urlFail.$amount.$currency.$installment.$isTest),
        ];
    }

    public function payUrl(): string
    {
        return $this->baseUrl().'/gateway/pay3d';
    }

    /**
     * Ödeme durumunu Tiko'ya sorar.
     *
     * @return array{state: 'paid'|'pending'|'failed'|'error', trans_id?: string, amount?: float, message?: string}
     */
    public function queryStatus(string $orderNumber): array
    {
        $merchant = $this->merchantId();
        try {
            $res = Http::asForm()->timeout(15)->post($this->baseUrl().'/payment/status', [
                'MerchantId' => $merchant,
                'OrderId' => $orderNumber,
                'Hash' => $this->hash($merchant.$orderNumber),
            ])->json();
        } catch (\Throwable $e) {
            Log::warning('[Tiko] status sorgusu başarısız', ['order' => $orderNumber, 'e' => $e->getMessage()]);

            return ['state' => 'error', 'message' => 'Ödeme sağlayıcısına ulaşılamadı.'];
        }

        if (($res['Status'] ?? null) !== '200' || empty($res['Result'])) {
            return ['state' => 'error', 'message' => $res['Description'] ?? 'Bilinmeyen yanıt'];
        }
        $r = $res['Result'];
        if (($r['Status'] ?? '') === '100') {
            return ['state' => 'pending'];
        }
        if (! empty($r['Hash'])) {
            $expected = $this->hash(($r['MerchantId'] ?? $merchant).($r['OrderId'] ?? $orderNumber).($r['Amount'] ?? '')
                .($r['Currency'] ?? 'TRY').($r['Installment'] ?? '0').($r['Status'] ?? '').($r['TransId'] ?? ''));
            if (! hash_equals($expected, (string) $r['Hash'])) {
                Log::error('[Tiko] status yanıtı imzası tutmadı', ['order' => $orderNumber]);

                return ['state' => 'error', 'message' => 'İmza doğrulanamadı.'];
            }
        }

        return ($r['Status'] ?? '') === '200'
            ? ['state' => 'paid', 'trans_id' => (string) ($r['TransId'] ?? ''), 'amount' => (float) ($r['Amount'] ?? 0)]
            : ['state' => 'failed', 'message' => $r['ErrorMsg'] ?? 'Ödeme onaylanmadı.'];
    }

    /** Sunucudan sunucuya gelen bildirimin imzası. */
    public function verifyCallback(array $p): bool
    {
        if (empty($p['Hash'])) {
            return false;
        }
        $expected = $this->hash(($p['MerchantId'] ?? '').($p['OrderId'] ?? '').($p['Amount'] ?? '').($p['Currency'] ?? '')
            .($p['Installment'] ?? '').($p['Status'] ?? '').($p['TransId'] ?? ''));

        return hash_equals($expected, (string) $p['Hash']);
    }
}
