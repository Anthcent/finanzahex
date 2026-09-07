<?php

namespace App\Libraries;

final class TransactionValue
{
    private const MIN_RATE = 0.000001;

    public static function inBolivars(array $transaction, float $fallbackRate = 1.0): float
    {
        $amountBs = max(0.0, (float) ($transaction['amount'] ?? 0));
        $amountUsd = max(0.0, (float) ($transaction['amount_usd'] ?? 0));
        $rate = self::rate($transaction, $fallbackRate);

        if (self::containsEquivalentAmounts($amountBs, $amountUsd, $rate)) {
            return round($amountBs, 2);
        }

        return round($amountBs + ($amountUsd * $rate), 2);
    }

    public static function inDollars(array $transaction, float $fallbackRate = 1.0): float
    {
        $amountBs = max(0.0, (float) ($transaction['amount'] ?? 0));
        $amountUsd = max(0.0, (float) ($transaction['amount_usd'] ?? 0));
        $rate = self::rate($transaction, $fallbackRate);

        if (self::containsEquivalentAmounts($amountBs, $amountUsd, $rate)) {
            return round($amountUsd, 2);
        }

        return round($amountUsd + ($amountBs / $rate), 2);
    }

    public static function enrich(array $transaction, float $fallbackRate = 1.0): array
    {
        $transaction['display_amount_bs'] = self::inBolivars($transaction, $fallbackRate);
        $transaction['display_amount_usd'] = self::inDollars($transaction, $fallbackRate);

        return $transaction;
    }

    private static function rate(array $transaction, float $fallbackRate): float
    {
        $rate = (float) ($transaction['exchange_rate'] ?? 0);
        if (!is_finite($rate) || $rate < self::MIN_RATE) {
            $rate = $fallbackRate;
        }

        return is_finite($rate) && $rate >= self::MIN_RATE ? $rate : 1.0;
    }

    private static function containsEquivalentAmounts(float $amountBs, float $amountUsd, float $rate): bool
    {
        if ($amountBs <= 0 || $amountUsd <= 0) {
            return false;
        }

        $expectedBs = $amountUsd * $rate;
        $tolerance = max(0.05, $rate * 0.01);

        return abs($amountBs - $expectedBs) <= $tolerance;
    }
}
