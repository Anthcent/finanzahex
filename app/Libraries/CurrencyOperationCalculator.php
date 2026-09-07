<?php

namespace App\Libraries;

use InvalidArgumentException;

final class CurrencyOperationCalculator
{
    public static function purchase(float $subtotalBs, float $commissionPercent, float $amountUsd): array
    {
        if ($subtotalBs <= 0 || $amountUsd <= 0) {
            throw new InvalidArgumentException('Los montos pagado y recibido deben ser mayores que cero.');
        }
        if ($commissionPercent < 0 || $commissionPercent > 100) {
            throw new InvalidArgumentException('La comisión debe estar entre 0% y 100%.');
        }

        $subtotalBs = round($subtotalBs, 2);
        $commissionBs = round($subtotalBs * $commissionPercent / 100, 2);
        $totalBs = round($subtotalBs + $commissionBs, 2);

        return [
            'subtotal_bs' => $subtotalBs,
            'commission_percent' => round($commissionPercent, 4),
            'commission_bs' => $commissionBs,
            'total_bs' => $totalBs,
            'amount_usd' => round($amountUsd, 2),
            'effective_rate' => round($totalBs / $amountUsd, 4),
        ];
    }
}
