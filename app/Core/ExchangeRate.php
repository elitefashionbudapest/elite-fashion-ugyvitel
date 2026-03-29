<?php

namespace App\Core;

class ExchangeRate
{
    private static string $cacheDir = __DIR__ . '/../../storage/cache/';
    private static int $cacheTtl = 3600; // 1 óra

    /**
     * Árfolyam lekérése (ECB napi árfolyam, frankfurter.app)
     * Visszaadja: 1 source = ? target (pl. 1 USD = 338 HUF)
     */
    public static function getRate(string $source, string $target): ?float
    {
        $source = strtoupper($source);
        $target = strtoupper($target);
        if ($source === $target) return 1.0;

        $cacheKey = $source . '_' . $target;
        $cached = self::getCache($cacheKey);
        if ($cached !== null) return $cached;

        $rate = self::fetchRate($source, $target);
        if ($rate !== null) {
            self::setCache($cacheKey, $rate);
        }
        return $rate;
    }

    /**
     * Deviza összeg átváltása HUF-ra
     */
    public static function toHuf(float $amount, string $currency): float
    {
        if (strtoupper($currency) === 'HUF') return $amount;
        $rate = self::getRate($currency, 'HUF');
        return $rate !== null ? round($amount * $rate) : 0;
    }

    /**
     * Formázás devizában + HUF egyenérték
     * Pl: "$1 320,00 (≈ 446 208 Ft)"
     */
    public static function formatWithHuf(float $amount, string $currency): string
    {
        if (strtoupper($currency) === 'HUF') {
            return format_money($amount);
        }

        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'];
        $sym = $symbols[strtoupper($currency)] ?? strtoupper($currency) . ' ';
        $formatted = $sym . number_format($amount, 2, ',', ' ');
        $huf = self::toHuf($amount, $currency);
        if ($huf > 0) {
            $formatted .= ' (≈ ' . format_money($huf) . ')';
        }
        return $formatted;
    }

    /**
     * Aktuális árfolyam szöveg
     */
    public static function getRateText(string $currency): string
    {
        if (strtoupper($currency) === 'HUF') return '';
        $rate = self::getRate($currency, 'HUF');
        if (!$rate) return '';
        return '1 ' . strtoupper($currency) . ' = ' . number_format($rate, 2, ',', ' ') . ' Ft';
    }

    private static function fetchRate(string $source, string $target): ?float
    {
        $url = 'https://api.frankfurter.app/latest?from=' . urlencode($source) . '&to=' . urlencode($target);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'EliteFashion/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;

        $data = json_decode($response, true);
        return (float)($data['rates'][$target] ?? 0) ?: null;
    }

    private static function getCache(string $key): ?float
    {
        $file = self::$cacheDir . 'rate_' . $key . '.json';
        if (!file_exists($file)) return null;

        $data = json_decode(file_get_contents($file), true);
        if (!$data || (time() - ($data['time'] ?? 0)) > self::$cacheTtl) return null;

        return (float)$data['rate'];
    }

    private static function setCache(string $key, float $rate): void
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        file_put_contents(
            self::$cacheDir . 'rate_' . $key . '.json',
            json_encode(['rate' => $rate, 'time' => time()])
        );
    }
}
