<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected $cacheKey = 'exchange_rate_ugx_usd';
    protected $cacheTtlMinutes = 360; // 6 hours

    /**
     * Return rate expressed as 1 UGX = X USD (eg 0.00027).
     */
    public function getUgxToUsdRate(): float
    {
        return Cache::remember($this->cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () {
            try {
                // Using exchangerate.host which offers free JSON endpoints
                $res = Http::timeout(5)->get('https://api.exchangerate.host/latest', [
                    'base' => 'UGX',
                    'symbols' => 'USD',
                ]);
                if ($res->ok()) {
                    $data = $res->json();
                    $rate = data_get($data, 'rates.USD');
                    if (is_numeric($rate) && $rate > 0) {
                        return (float) $rate;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and fallback
            }

            // fallback: 1 UGX ≈ 0.0002796 USD (approx). If you prefer use inverse (USD->UGX) adjust accordingly.
            return 0.0002739726;
        });
    }

    /**
     * Convert UGX -> USD
     */
    public function ugxToUsd(float $ugx): float
    {
        $rate = $this->getUgxToUsdRate();
        return $ugx * $rate;
    }

    /**
     * Convert USD -> UGX (uses the inverse of UGX->USD)
     */
    public function usdToUgx(float $usd): float
    {
        $rate = $this->getUgxToUsdRate();
        if ($rate <= 0) return 0.0;
        return $usd / $rate;
    }
}