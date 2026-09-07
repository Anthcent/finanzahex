<?php

namespace App\Libraries;

final class PrintProductConfigurator
{
    public static function calculate(array $product, array $requested, float $rate): array
    {
        if ($rate <= 0) {
            throw new \InvalidArgumentException('La tasa de cambio debe ser mayor que cero.');
        }
        $definitions = json_decode((string) ($product['characteristics_json'] ?? '[]'), true);
        if (!is_array($definitions)) {
            $definitions = [];
        }

        $labels = [];
        $safe = [];
        $adjustmentBs = 0.0;
        $adjustmentUsd = 0.0;
        foreach ($definitions as $definition) {
            $name = trim((string) ($definition['name'] ?? ''));
            if ($name === '') continue;
            $value = trim((string) ($requested[$name] ?? ''));
            if (!empty($definition['required']) && $value === '') {
                throw new \InvalidArgumentException("Selecciona {$name} para {$product['name']}.");
            }
            if ($value === '') continue;

            if (($definition['type'] ?? 'select') === 'text') {
                $value = mb_substr($value, 0, 80);
            } else {
                $selectedOption = null;
                foreach (($definition['options'] ?? []) as $option) {
                    if ((string) ($option['label'] ?? '') === $value) {
                        $selectedOption = $option;
                        break;
                    }
                }
                if ($selectedOption === null) {
                    throw new \InvalidArgumentException("La opción de {$name} ya no está disponible.");
                }
                $optionBs = max(0.0, (float) ($selectedOption['price_bs'] ?? 0));
                $optionUsd = max(0.0, (float) ($selectedOption['price_usd'] ?? 0));
                if ($optionBs > 0) {
                    $adjustmentBs += $optionBs;
                    $adjustmentUsd += $optionBs / $rate;
                } elseif ($optionUsd > 0) {
                    $adjustmentUsd += $optionUsd;
                    $adjustmentBs += $optionUsd * $rate;
                }
            }
            $safe[$name] = $value;
            $labels[] = "{$name}: {$value}";
        }

        return [
            'labels' => $labels,
            'selections' => $safe,
            'adjustment_bs' => round($adjustmentBs, 2),
            'adjustment_usd' => round($adjustmentUsd, 4),
        ];
    }
}
