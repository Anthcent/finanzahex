<?php

namespace App\Libraries;

final class PrintingPaymentCalculator
{
    public static function normalize(float $amountBs, float $amountUsd, float $rate, ?string $currency): array
    {
        if (!is_finite($rate) || $rate <= 0) {
            throw new \InvalidArgumentException('La tasa de cambio debe ser mayor que cero.');
        }
        if (!is_finite($amountBs) || !is_finite($amountUsd) || $amountBs < 0 || $amountUsd < 0) {
            throw new \InvalidArgumentException('El monto del abono no es válido.');
        }

        $amountBs = round($amountBs, 2);
        $amountUsd = round($amountUsd, 2);
        $currency = strtolower(trim((string) $currency));

        if (!in_array($currency, ['bs', 'usd'], true)) {
            if ($amountBs > 0 && $amountUsd > 0) {
                $expectedBs = round($amountUsd * $rate, 2);
                if (abs($amountBs - $expectedBs) > max(0.02, $rate * 0.01)) {
                    throw new \InvalidArgumentException('Indica si el abono fue recibido en bolívares o dólares.');
                }
                // Compatibility with the old form, which sent the same payment in both currencies.
                $currency = 'usd';
            } else {
                $currency = $amountUsd > 0 ? 'usd' : 'bs';
            }
        }

        if ($currency === 'usd') {
            if ($amountUsd <= 0) {
                throw new \InvalidArgumentException('El monto del abono debe ser mayor que cero.');
            }
            $amountBs = 0.0;
        } else {
            if ($amountBs <= 0) {
                throw new \InvalidArgumentException('El monto del abono debe ser mayor que cero.');
            }
            $amountUsd = 0.0;
        }

        return [
            'currency' => $currency,
            'amount_bs' => $amountBs,
            'amount_usd' => $amountUsd,
            'value_bs' => round($amountBs + ($amountUsd * $rate), 2),
        ];
    }

    public static function apply(array $order, array $payment, float $rate): array
    {
        $totalBs = (float) ($order['total_bs'] ?? 0);
        if ($totalBs <= 0) {
            $totalBs = (float) ($order['total_usd'] ?? 0) * $rate;
        }
        if ($totalBs <= 0) {
            throw new \InvalidArgumentException('La orden no tiene un total válido.');
        }

        $paidBs = (float) ($order['paid_bs'] ?? 0);
        $paidUsd = (float) ($order['paid_usd'] ?? 0);
        $remainingBs = max(0, $totalBs - ($paidBs + ($paidUsd * $rate)));
        if ($remainingBs <= 0.005) {
            throw new \InvalidArgumentException('La deuda ya fue pagada.');
        }

        $currency = $payment['currency'];
        $amountInCurrency = $currency === 'usd'
            ? (float) $payment['amount_usd']
            : (float) $payment['amount_bs'];
        $remainingInCurrency = round($currency === 'usd' ? $remainingBs / $rate : $remainingBs, 2);

        if ($amountInCurrency > ($remainingInCurrency + 0.001)) {
            throw new \InvalidArgumentException('El abono no puede superar el saldo pendiente.');
        }

        $isPaid = $amountInCurrency >= ($remainingInCurrency - 0.001);

        return [
            'paid_bs' => round($paidBs + (float) $payment['amount_bs'], 2),
            'paid_usd' => round($paidUsd + (float) $payment['amount_usd'], 2),
            'status' => $isPaid ? 'paid' : 'partial',
            'remaining_bs' => round(max(0, $remainingBs - (float) $payment['value_bs']), 2),
        ];
    }
}
