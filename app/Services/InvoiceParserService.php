<?php

namespace App\Services;

class InvoiceParserService
{
    /**
     * Parse raw OCR text into structured data.
     * Supports single receipts or multi-receipt images.
     *
     * @param string $rawText
     * @param float $defaultExchangeRate
     * @return array Array of parsed invoices
     */
    public function parseMulti(string $rawText, float $defaultExchangeRate = 50.0): array
    {
        $normalized = $this->normalizeText($rawText);
        $blocks = $this->splitIntoReceiptBlocks($normalized);

        $results = [];
        foreach ($blocks as $block) {
            $parsed = $this->parseSingleBlock($block, $defaultExchangeRate);
            if ($parsed && ($parsed['total_bs'] > 0 || !empty($parsed['items']) || !empty($parsed['rif']))) {
                $results[] = $parsed;
            }
        }

        return !empty($results) ? $results : [$this->parseSingleBlock($normalized, $defaultExchangeRate)];
    }

    /**
     * Single invoice parse compatibility wrapper.
     */
    public function parse(string $rawText, float $defaultExchangeRate = 50.0): array
    {
        $multi = $this->parseMulti($rawText, $defaultExchangeRate);
        return !empty($multi) ? $multi[0] : [];
    }

    /**
     * Clean and normalize OCR string typos specific to Venezuelan thermal and digital invoices.
     */
    protected function normalizeText(string $text): string
    {
        $replacements = [
            '/\bIOTAL\b/i' => 'TOTAL',
            '/\b1OTAL\b/i' => 'TOTAL',
            '/\bTOTAI\b/i' => 'TOTAL',
            '/\bTOIAL\b/i' => 'TOTAL',
            '/\bTOTL\b/i' => 'TOTAL',
            '/\bT0TAL\b/i' => 'TOTAL',
            '/\bSUBTTL\b/i' => 'SUBTOTAL',
            '/\bSUBTIL\b/i' => 'SUBTOTAL',
            '/\bSUB-TOTAL\b/i' => 'SUBTOTAL',
            '/\bSEMIAT\b/i' => 'SENIAT',
            '/\bSENIAI\b/i' => 'SENIAT',
            '/\bSENIAL\b/i' => 'SENIAT',
            '/\bSENAT\b/i' => 'SENIAT',
            '/\bPI\s+G\b/i' => 'BI G',
            '/\bP\.I\.\s*G\b/i' => 'BI G',
            '/\bVA\s+G\b/i' => 'IVA G',
            '/\bLUA\s+G\b/i' => 'IVA G',
            '/\bMONTO\s+REP\b/i' => 'MONTO REF',
            '/\bLONTO\s+REP\b/i' => 'MONTO REF',
            '/\bLONTO\b/i' => 'MONTO',
            '/\bNONTO\b/i' => 'MONTO',
            '/\bFAC\s+URA\b/i' => 'FACTURA',
            '/\bFACT\s+URA\b/i' => 'FACTURA',
            '/\bEACTURA\b/i' => 'FACTURA',
            '/\bEXENIO\b/i' => 'EXENTO',
            '/\bEXEMTO\b/i' => 'EXENTO',
            '/\bC0MPRA\b/i' => 'COMPRA',
            '/\bAPROBAD0\b/i' => 'APROBADO',
            '/\bTARJEIA\b/i' => 'TARJETA',
            '/\b1GTF\b/i' => 'IGTF',
            '/\bIGIE\b/i' => 'IGTF',
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Segments text into individual receipt blocks if photo contains multiple receipts.
     */
    protected function splitIntoReceiptBlocks(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), function ($l) {
            return $l !== '';
        }));

        if (count($lines) < 8) {
            return [$text];
        }

        $splitIndices = [];
        $headerPatterns = [
            '/^SENIAT\b/i',
            '/^(?:BNC|BANESCO|MERCANTIL|PROVINCIAL|BANCAMIGA|BDV)\b.*?(?:COMPRA|VENTA|POS)/i',
            '/^COMPRA\b/i',
            '/^PAGO\s*MOVIL/i',
            '/^FACTURA\s*ELECTRONICA/i',
            '/^[A-Z0-9\s,\.\-]{4,}\b(?:C\.?A\.?|S\.?A\.?|S\.?R\.?L\.?)\b/i'
        ];

        for ($i = 5; $i < count($lines); $i++) {
            $line = $lines[$i];

            $isHeader = false;
            foreach ($headerPatterns as $pat) {
                if (preg_match($pat, $line)) {
                    $isHeader = true;
                    break;
                }
            }

            if ($isHeader) {
                // Verify preceding block ended (TOTAL, APROBADO, Z1F, printer serial, etc.)
                $prevHasEnd = false;
                for ($j = max(0, $i - 10); $j < $i; $j++) {
                    if (preg_match('/(?:TOTAL|APROBADO|GRACIAS|Z1F|TIU\d+|MAESTRO|DEBITO|EFECTIVO)/i', $lines[$j])) {
                        $prevHasEnd = true;
                        break;
                    }
                }

                if ($prevHasEnd) {
                    $splitIndices[] = $i;
                }
            }
        }

        if (empty($splitIndices)) {
            return [$text];
        }

        $blocks = [];
        $startIndex = 0;
        foreach (array_unique($splitIndices) as $idx) {
            if ($idx > $startIndex + 3) {
                $blockLines = array_slice($lines, $startIndex, $idx - $startIndex);
                if (!empty($blockLines)) {
                    $blocks[] = implode("\n", $blockLines);
                }
                $startIndex = $idx;
            }
        }
        $lastBlock = array_slice($lines, $startIndex);
        if (!empty($lastBlock) && count($lastBlock) >= 3) {
            $blocks[] = implode("\n", $lastBlock);
        }

        return !empty($blocks) ? $blocks : [$text];
    }

    /**
     * Parse an individual receipt block into structured invoice data.
     */
    protected function parseSingleBlock(string $rawText, float $defaultExchangeRate): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $rawText)), function ($l) {
            return $l !== '';
        }));

        if (empty($lines)) return [];

        $model = $this->detectModel($lines, $rawText);
        $rif = $this->extractRif($lines, $model);
        $merchant = $this->extractMerchant($lines, $rif, $model);
        $invoiceNumber = $this->extractInvoiceNumber($lines, $model);
        $dateTime = $this->extractDateTime($lines);
        $currencyInfo = $this->extractCurrencyAndRate($lines, $defaultExchangeRate, $model);
        $taxes = $this->extractTaxes($lines);
        $totalBs = $this->extractTotal($lines, $taxes, $model);
        $totalUsd = $currencyInfo['amount_usd'];
        if ($totalUsd <= 0 && $totalBs > 0 && $currencyInfo['exchange_rate'] > 0) {
            $totalUsd = round($totalBs / $currencyInfo['exchange_rate'], 2);
        }
        $paymentMethods = $this->extractPaymentMethods($lines);

        $items = $this->extractItemsByModel($lines, $currencyInfo['exchange_rate'], $model, [
            'merchant' => $merchant,
            'total_bs' => $totalBs,
            'total_usd' => $totalUsd,
            'invoice_number' => $invoiceNumber
        ]);

        // Auto-reconcile subtotal and taxes from items if missing or zero
        if (!empty($items) && ($taxes['subtotal'] == 0 || $totalBs == 0)) {
            $calcSubtotal = 0.0;
            $calcExento = 0.0;
            $calcBaseImp = 0.0;

            foreach ($items as $it) {
                $p = (float) ($it['price'] ?? 0);
                $calcSubtotal += $p;
                if (($it['tax_type'] ?? 'G') === 'E') {
                    $calcExento += $p;
                } else {
                    $calcBaseImp += $p;
                }
            }

            if ($taxes['subtotal'] == 0 && $calcSubtotal > 0) {
                $taxes['subtotal'] = round($calcSubtotal, 2);
            }
            if ($taxes['exento'] == 0 && $calcExento > 0) {
                $taxes['exento'] = round($calcExento, 2);
            }
            if ($taxes['base_imponible'] == 0 && $calcBaseImp > 0) {
                $taxes['base_imponible'] = round($calcBaseImp, 2);
            }
            if ($taxes['base_imponible'] > 0) {
                if ($taxes['iva_amount'] == 0 || abs($taxes['iva_amount'] - $taxes['base_imponible']) < 0.02 || $taxes['iva_amount'] > ($taxes['base_imponible'] * 0.20)) {
                    $taxes['iva_amount'] = round($taxes['base_imponible'] * 0.16, 2);
                }
            } elseif ($totalBs > 0 && $taxes['subtotal'] > 0 && ($totalBs - $taxes['subtotal']) > 0) {
                $diff = round($totalBs - $taxes['subtotal'], 2);
                if ($taxes['iva_amount'] == 0 || $taxes['iva_amount'] > $diff) {
                    $taxes['iva_amount'] = $diff;
                }
            }
            if ($totalBs == 0 && $taxes['subtotal'] > 0) {
                $totalBs = round($taxes['subtotal'] + $taxes['iva_amount'] + ($taxes['igtf_amount'] ?? 0), 2);
            }
        }

        // Final tax reconciliation if IVA still matches base imponible or total - subtotal mismatch
        if ($taxes['base_imponible'] > 0 && ($taxes['iva_amount'] == 0 || abs($taxes['iva_amount'] - $taxes['base_imponible']) < 0.02 || $taxes['iva_amount'] > ($taxes['base_imponible'] * 0.20))) {
            $taxes['iva_amount'] = round($taxes['base_imponible'] * 0.16, 2);
        }

        // Recalculate USD total if zero
        if ($totalUsd <= 0 && $totalBs > 0 && $currencyInfo['exchange_rate'] > 0) {
            $totalUsd = round($totalBs / $currencyInfo['exchange_rate'], 2);
        }

        return [
            'model_type' => $model['type'],
            'model_label' => $model['label'],
            'merchant' => $merchant ?: 'Comercio Desconocido',
            'rif' => $rif,
            'invoice_number' => $invoiceNumber,
            'date' => $dateTime['date'] ?: date('Y-m-d'),
            'time' => $dateTime['time'] ?: date('H:i'),
            'items' => $items,
            'subtotal' => $taxes['subtotal'],
            'exento' => $taxes['exento'],
            'base_imponible' => $taxes['base_imponible'],
            'iva_amount' => $taxes['iva_amount'],
            'iva_rate' => $taxes['iva_rate'],
            'igtf_amount' => $taxes['igtf_amount'],
            'total_bs' => $totalBs,
            'total_usd' => $totalUsd,
            'exchange_rate' => $currencyInfo['exchange_rate'],
            'payment_method' => $paymentMethods['primary'] ?? null,
            'cashea' => $paymentMethods['cashea'] ?? null,
            'raw_text' => $rawText
        ];
    }

    /**
     * Classify Venezuelan invoice model.
     */
    protected function detectModel(array $lines, string $fullText): array
    {
        if (preg_match('/(?:PAGO\s*MOVIL|PAGO\s*MOVIL\s*INTERBANCARIO|¡?PAGO\s*MOVIL\s*EXITOSO!?)/i', $fullText)) {
            return ['type' => 'PAGO_MOVIL', 'label' => 'Pago Móvil / Transferencia'];
        }

        if ((preg_match('/(?:COMPRA|APROBADO|\bTER:|\bAFIL:|\bLOT:|\bAPROB:)\b/i', $fullText) &&
            preg_match('/(?:MAESTRO|VISA|MASTERCARD|DEBITO|CREDITO|BANCO|BNC|BANESCO|MERCANTIL)/i', $fullText)) &&
            !preg_match('/(?:BI\s*G|IVA\s*G|BASE\s*IMPONIBLE)/i', $fullText)) {
            return ['type' => 'POS_VOUCHER', 'label' => 'Voucher Punto de Venta'];
        }

        if (preg_match('/(?:FACTURA\s*ELECTRONICA|NRO\s*DE\s*CONTROL|NUMERO\s*DE\s*CONTROL)/i', $fullText) ||
            preg_match('/(?:ITEM\s*\|\s*CANT|CODIGO\s*\|\s*DESCRIPCION)/i', $fullText)) {
            return ['type' => 'ELECTRONIC_INVOICE', 'label' => 'Factura Electrónica / Digital'];
        }

        if (preg_match('/\b759\d{10}\b/', $fullText) || preg_match('/\b\d+\.?\d*\s*(?:UN|PZ|KG)\s*x\b/i', $fullText)) {
            return ['type' => 'RETAIL_RECEIPT', 'label' => 'Supermercado / Farmacia'];
        }

        if (preg_match('/(?:SENIAT|Z1F|TIU\d+|BI\s*G|IVA\s*G|EXENTO)/i', $fullText)) {
            return ['type' => 'FISCAL_PRINTER', 'label' => 'Impresora Fiscal SENIAT'];
        }

        return ['type' => 'GENERIC_RECEIPT', 'label' => 'Factura / Recibo General'];
    }

    /**
     * Extract Venezuelan RIF.
     */
    protected function extractRif(array $lines, array $model): ?string
    {
        if ($model['type'] === 'PAGO_MOVIL') {
            foreach ($lines as $line) {
                if (preg_match('/(?:CEDULA|RIF|C\.?I\.?)[\s:O]*([JVEGP][-\s]?\d{7,9}[-\s]?\d?)/i', $line, $m)) {
                    return $this->formatRif($m[1]);
                }
            }
        }

        // Header lines first (avoid client RIF)
        foreach (array_slice($lines, 0, 12) as $line) {
            if (preg_match('/(?:CLIENTE|DATOS\s*DEL\s*CLIENTE)/i', $line)) {
                break;
            }
            if (preg_match('/\b([JVEGP][-\s]?\d{7,9}[-\s]?\d?)\b/i', $line, $matches)) {
                return $this->formatRif($matches[1]);
            }
        }

        // Fallback
        foreach ($lines as $line) {
            if (preg_match('/\b([JVEGP][-\s]?\d{7,9}[-\s]?\d?)\b/i', $line, $matches)) {
                return $this->formatRif($matches[1]);
            }
        }

        return null;
    }

    protected function formatRif(string $raw): string
    {
        $clean = strtoupper(preg_replace('/[\s-]+/', '', $raw));
        if (strlen($clean) >= 9) {
            $letter = substr($clean, 0, 1);
            $num = substr($clean, 1);
            return $letter . '-' . $num;
        }
        return $clean;
    }

    /**
     * Extract Merchant / Store / Beneficiary Name.
     */
    protected function extractMerchant(array $lines, ?string $rif, array $model): ?string
    {
        if ($model['type'] === 'PAGO_MOVIL') {
            foreach ($lines as $line) {
                if (preg_match('/(?:BENEFICIARIO|A\s*NOMBRE\s*DE)[\s:]+(.+)$/i', $line, $m)) {
                    return trim(preg_replace('/^(?:BENEFICIARIO|A\s*NOMBRE\s*DE)[\s:]*/i', '', $m[1]));
                }
            }
            foreach ($lines as $line) {
                if (preg_match('/(?:DESTINO|DESTINATARIO)[\s:]+(.+)$/i', $line, $m)) {
                    return trim($m[1]);
                }
            }
        }

        if ($model['type'] === 'POS_VOUCHER') {
            foreach ($lines as $line) {
                $cleaned = trim($line);
                if (empty($cleaned)) continue;
                if (preg_match('/^(?:BNC|BANESCO|MERCANTIL|PROVINCIAL|BDV|BANCO|COMPRA|VENTA|TARJETA|FECHA|AFIL|TER|LOT|REF|APROB)/i', $cleaned)) {
                    continue;
                }
                if (preg_match('/[A-Z]{3,}/', $cleaned) && !preg_match('/^\d+$/', $cleaned)) {
                    return preg_replace('/^(?:RIF|TRUJILLO|VALERA)[\s:#-]*/i', '', $cleaned);
                }
            }
        }

        $ignoredKeywords = ['SENIAT', 'FACTURA', 'NUMERO', 'FECHA', 'HORA', 'CLIENTE', 'DIRECCION', 'TELEFONO', 'DATOS', 'COMPRA', 'APROBADO', 'SUBTOTAL', 'TOTAL'];
        $headerLines = array_slice($lines, 0, 10);

        foreach ($headerLines as $line) {
            $cleaned = trim($line);
            if (empty($cleaned)) continue;
            if (preg_match('/^[0-9\s:,\.\-\/]+$/', $cleaned)) continue;
            if ($rif && stripos($cleaned, $rif) !== false) continue;

            $isIgnored = false;
            foreach ($ignoredKeywords as $kw) {
                if (strcasecmp($cleaned, $kw) === 0 || stripos($cleaned, $kw . ':') === 0) {
                    $isIgnored = true;
                    break;
                }
            }
            if ($isIgnored) continue;

            if (preg_match('/\b(C\.?A\.?|S\.?A\.?|S\.?R\.?L\.?|SUPERMERCADO|BAZAR|TIENDA|FARMACIA|INVERSIONES|COMERCIAL|DISTRIBUIDORA|EMPRENDIMIENTO)\b/i', $cleaned)) {
                return preg_replace('/^(?:RIF|SUC|SUCURSAL|EDIF|AV|CALLE)[\s:#-]*/i', '', $cleaned);
            }
        }

        foreach ($headerLines as $line) {
            $cleaned = trim($line);
            if (!empty($cleaned) && !preg_match('/^(?:SENIAT|FACTURA|AV|CALLE|URB|SECTOR|EDIF|CARACAS|VALERA|TRUJILLO|PISO)\b/i', $cleaned) && strlen($cleaned) > 3) {
                return $cleaned;
            }
        }

        return null;
    }

    /**
     * Extract Invoice / Authorization Number.
     */
    protected function extractInvoiceNumber(array $lines, array $model): ?string
    {
        if ($model['type'] === 'PAGO_MOVIL') {
            foreach ($lines as $line) {
                if (preg_match('/(?:REFERENCIA|REF\.?)[\s:#]*([0-9A-Z]+)/i', $line, $m)) {
                    return $m[1];
                }
            }
            return null;
        }

        if ($model['type'] === 'POS_VOUCHER') {
            foreach ($lines as $line) {
                if (preg_match('/(?:APROB|APROBACION)[\s:#]*([0-9A-Z]+)/i', $line, $m)) {
                    return 'APROB-' . $m[1];
                }
            }
            return null;
        }

        // Prioritize explicit FACTURA: XXXXX over address lines
        foreach ($lines as $line) {
            if (preg_match('/\bFACTURA[\s:#]+([0-9A-Z\-]{3,})/i', $line, $matches)) {
                $num = trim($matches[1]);
                if (!in_array(strtoupper($num), ['DE', 'DEL', 'HORA', 'FECHA', 'CLIENTE', 'DATOS', 'ELECTRONICA', 'VENTA', 'NRO'])) {
                    return $num;
                }
            }
        }

        foreach ($lines as $line) {
            if (preg_match('/(?:LOCAL|EDIF|AV|CALLE|CASA|PISO)/i', $line)) continue;
            if (preg_match('/\b(?:NUMERO|NRO|CONTROL)[\s:#]+([0-9A-Z\-]{3,})/i', $line, $matches)) {
                $num = trim($matches[1]);
                if (!in_array(strtoupper($num), ['DE', 'DEL', 'HORA', 'FECHA', 'CLIENTE', 'DATOS'])) {
                    return $num;
                }
            }
        }

        // Pass 3: Look for standalone fiscal 8-digit or 6-8 digit sequential number (e.g. 00200743)
        foreach ($lines as $line) {
            if (preg_match('/^(00\d{5,8}|\d{7,8})$/', $line, $m)) {
                if (!preg_match('/^(?:20\d{2}|0412|0414|0416|0424|0426)/', $m[1])) {
                    return $m[1];
                }
            }
        }

        return null;
    }

    /**
     * Extract Date and Time with Spanish month names support.
     */
    protected function extractDateTime(array $lines): array
    {
        $date = null;
        $time = null;

        $monthMap = [
            'ENE' => '01', 'FEB' => '02', 'MAR' => '03', 'ABR' => '04',
            'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AGO' => '08',
            'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DIC' => '12'
        ];

        foreach ($lines as $line) {
            if (!$date) {
                // ISO YYYY-MM-DD
                if (preg_match('/\b(20\d{2})[-\/.](0[1-9]|1[0-2])[-\/.](0[1-9]|[12]\d|3[01])\b/', $line, $m)) {
                    $date = "{$m[1]}-{$m[2]}-{$m[3]}";
                }
                // DD-MM-YYYY or DD-MM-YY
                elseif (preg_match('/\b(0[1-9]|[12]\d|3[01])[-\/.](0[1-9]|1[0-2])[-\/.](20\d{2}|\d{2})\b/', $line, $m)) {
                    $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                    $date = "$year-{$m[2]}-{$m[1]}";
                }
                // DD-MES-YYYY or DD/MES/YY (e.g. 15-AGO-2026)
                elseif (preg_match('/\b(0[1-9]|[12]\d|3[01])[-\/\s](ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)[-\/\s](20\d{2}|\d{2})\b/i', $line, $m)) {
                    $mon = $monthMap[strtoupper($m[2])] ?? '01';
                    $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                    $date = "$year-$mon-{$m[1]}";
                }
            }

            if (!$time && preg_match('/\b(\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AP]M)?)\b/i', $line, $m)) {
                $time = trim($m[1]);
            }
        }

        return ['date' => $date, 'time' => $time];
    }

    /**
     * Extract Exchange Rate and Reference USD.
     */
    protected function extractCurrencyAndRate(array $lines, float $defaultRate, array $model): array
    {
        $amountUsd = 0.0;
        $rate = $defaultRate;

        if ($model['type'] === 'POS_VOUCHER' || $model['type'] === 'PAGO_MOVIL') {
            return ['amount_usd' => 0.0, 'exchange_rate' => $defaultRate];
        }

        foreach ($lines as $line) {
            // Factor / Tasa BCV
            if (preg_match('/(?:FACTOR|TASA\s*BCV|TASA)[\s:]*([0-9\s,\.]+)/i', $line, $m)) {
                $parsedRate = $this->parseMoney($m[1]);
                if ($parsedRate > 0) $rate = $parsedRate;
            }

            // Reference USD: explicitly require USD, $, or accompanied by FACTOR
            if (preg_match('/(?:MONTO\s*REF|REF\s*USD|REF\s*\$|TOTAL\s*USD|VALOR\s*REF)[\s:]*([0-9\s,\.]+)/i', $line, $m)) {
                $amountUsd = $this->parseMoney($m[1]);
            }
        }

        return [
            'amount_usd' => $amountUsd,
            'exchange_rate' => $rate > 0 ? $rate : $defaultRate
        ];
    }

    /**
     * Extract Taxes, Base Imponible, Exento, and IGTF 3%.
     */
    protected function extractTaxes(array $lines): array
    {
        $subtotal = 0.0;
        $exento = 0.0;
        $baseImponible = 0.0;
        $ivaAmount = 0.0;
        $ivaRate = 16.0;
        $igtfAmount = 0.0;

        foreach ($lines as $line) {
            if (preg_match('/(?:SUBTTL|SUBTOTAL)[\s:#]*(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                $subtotal = $this->parseMoney($m[1]);
            }
            if (preg_match('/(?:EXENTO|TOTAL\s*EXENTO)[\s:#]*(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                $exento = $this->parseMoney($m[1]);
            } elseif (preg_match('/(?:Bs\.?|BSS)?\s*([0-9\s,\.]+)\s*(?:EXENTO)/i', $line, $m)) {
                $exento = $this->parseMoney($m[1]);
            }
            if (preg_match('/(?:BASE\s*IM?P(?:ONIBLE)?|BI\s*G)[\s\d,\.%()]*[:\s]+(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                $baseImponible = $this->parseMoney($m[1]);
            } elseif (preg_match('/(?:Bs\.?|BSS)?\s*([0-9\s,\.]+)\s*(?:BASE\s*IM?P|BI\s*G)/i', $line, $m)) {
                $baseImponible = $this->parseMoney($m[1]);
            }
            if (preg_match('/(?:IVA|ALICUOTAS?\s*IVA|IVA\s*G)[\s\d,\.%()]*[:\s]+(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                $ivaAmount = $this->parseMoney($m[1]);
            } elseif (preg_match('/(?:Bs\.?|BSS)?\s*([0-9\s,\.]+)\s*(?:IVA|ALICUOTAS?\s*IVA|IVA\s*G)/i', $line, $m)) {
                $ivaAmount = $this->parseMoney($m[1]);
            }
            if (preg_match('/(?:IGTF|PERCEPCION\s*3%|IMPUESTO\s*IGTF)[\s\d,\.%()]*[:\s]+(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                $igtfAmount = $this->parseMoney($m[1]);
            }
        }

        return [
            'subtotal' => $subtotal,
            'exento' => $exento,
            'base_imponible' => $baseImponible,
            'iva_amount' => $ivaAmount,
            'iva_rate' => $ivaRate,
            'igtf_amount' => $igtfAmount
        ];
    }

    /**
     * Extract Grand Total in Bs.
     */
    protected function extractTotal(array $lines, array $taxes, array $model): float
    {
        if ($model['type'] === 'PAGO_MOVIL') {
            foreach ($lines as $line) {
                if (preg_match('/(?:MONTO\s*TOTAL|MONTO)[\s:#]*(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                    $val = $this->parseMoney($m[1]);
                    if ($val > 0) return $val;
                }
            }
        }

        if ($model['type'] === 'POS_VOUCHER') {
            foreach ($lines as $line) {
                if (preg_match('/(?:MONTO|IMPORTE)[\s:#]*(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                    $val = $this->parseMoney($m[1]);
                    if ($val > 0) return $val;
                }
            }
        }

        // Pass 1: Check for TOTAL in reversed lines to capture the bottom final summary
        $reversed = array_reverse($lines);
        foreach ($reversed as $line) {
            if (preg_match('/(?:TOTAL(?:\s+A\s+PAGAR)?(?:\s+(?:BS\.?|BSS|GENERAL|FACTURA))?|MONTO\s*A\s*PAGAR)[\s:#]*(?:BS\.?|BSS)?[\s:#]*([0-9\s,\.]+)/i', $line, $m)) {
                $val = $this->parseMoney($m[1]);
                if ($val > 0) {
                    return $val;
                }
            }
        }

        // Pass 2: If TOTAL was on a line alone, check next 3 lines
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/^TOTAL\b/i', $lines[$i])) {
                for ($j = $i + 1; $j <= min(count($lines) - 1, $i + 3); $j++) {
                    if (preg_match('/^(?:BS\.?|BSS)?\s*([0-9\s,\.]{3,})$/i', $lines[$j], $m)) {
                        $val = $this->parseMoney($m[1]);
                        if ($val > 0) return $val;
                    }
                }
            }
        }

        // Pass 3: Currency amounts cluster (columnar format)
        $priceBlock = $this->findCurrencyAmountsBlock($lines);
        if (!empty($priceBlock)) {
            $vals = array_column($priceBlock, 'val');
            for ($a = 0; $a < count($vals); $a++) {
                for ($b = $a + 1; $b < count($vals); $b++) {
                    $sum = round($vals[$a] + $vals[$b], 2);
                    if (in_array($sum, $vals)) {
                        return $sum;
                    }
                }
            }
            return max($vals);
        }

        // Calculation fallback
        if ($taxes['subtotal'] > 0) {
            return round($taxes['subtotal'] + $taxes['iva_amount'] + $taxes['igtf_amount'], 2);
        }

        return 0.0;
    }

    /**
     * Find contiguous or near-contiguous blocks of currency amounts (Bs. XXX,XX or XXX,XX)
     * which happens when OCR engines read columnar thermal receipts vertically.
     */
    protected function findCurrencyAmountsBlock(array $lines): array
    {
        $blocks = [];
        $current = [];
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            // Match numbers with currency prefix (Bs. 1.234,56) OR clean monetary decimals (1.234,56 or 45,00)
            if (preg_match('/^(?:(?:Bs\.?|BSS|\$)\s*)?([0-9]{1,3}(?:[\.\s][0-9]{3})*[\.,][0-9]{2}|[0-9]+[\.,][0-9]{2})$/i', $trimmed, $m) ||
                preg_match('/^(?:Bs\.?|BSS)\s*([0-9\s,\.]+)/i', $trimmed, $m)) {
                // Discard clock times (19:28) and dates
                if (!preg_match('/^\d{1,2}:\d{2}$/', $trimmed) && !preg_match('/^\d{2,4}[-\/]\d{2}[-\/]\d{2,4}$/', $trimmed)) {
                    $val = $this->parseMoney($m[1]);
                    if ($val > 0) {
                        $current[] = ['idx' => $idx, 'val' => $val, 'raw' => $line];
                        continue;
                    }
                }
            }
            if (!empty($current)) {
                $blocks[] = $current;
                $current = [];
            }
        }
        if (!empty($current)) {
            $blocks[] = $current;
        }

        $largest = [];
        foreach ($blocks as $b) {
            if (count($b) > count($largest)) {
                $largest = $b;
            }
        }
        if (count($largest) >= 3) {
            return $largest;
        }
        return !empty($blocks) ? $blocks[0] : [];
    }

    /**
     * Extract payment methods (BioPago, Cashea, Tarjeta de Débito, Divisas, etc.).
     */
    protected function extractPaymentMethods(array $lines): array
    {
        $detected = [];
        $cashea = null;

        foreach ($lines as $line) {
            if (preg_match('/BIOPAGO/i', $line)) {
                $detected[] = 'BioPago';
            }
            if (preg_match('/CASHEA/i', $line)) {
                $detected[] = 'Cashea';
                if (preg_match('/CASHEA.*?([0-9\s,\.]+)/i', $line, $m)) {
                    $cashea = $this->parseMoney($m[1]);
                }
            }
            if (preg_match('/(?:DEBITO|MAESTRO|TARJETA\s*DE\s*DEBITO)/i', $line)) {
                $detected[] = 'Tarjeta de Débito';
            }
            if (preg_match('/(?:CREDITO|VISA|MASTERCARD)/i', $line)) {
                $detected[] = 'Tarjeta de Crédito';
            }
            if (preg_match('/(?:EFECTIVO|DIVISAS)/i', $line)) {
                $detected[] = 'Efectivo / Divisas';
            }
            if (preg_match('/(?:TRANSFERENCIA|PAGO\s*MOVIL)/i', $line)) {
                $detected[] = 'Pago Móvil / Transferencia';
            }
        }

        return [
            'primary' => !empty($detected) ? $detected[0] : null,
            'all' => array_unique($detected),
            'cashea' => $cashea
        ];
    }

    /**
     * Multi-strategy line item extraction tailored to invoice model.
     */
    protected function extractItemsByModel(array $lines, float $exchangeRate, array $model, array $meta): array
    {
        // Strategy for POS Vouchers
        if ($model['type'] === 'POS_VOUCHER') {
            $details = [];
            foreach ($lines as $line) {
                if (preg_match('/(?:LOT|LOTE)[\s:#]*([0-9]+)/i', $line, $m)) $details[] = 'Lote: ' . $m[1];
                if (preg_match('/(?:REF)[\s:#]*([0-9]+)/i', $line, $m)) $details[] = 'Ref: ' . $m[1];
                if (preg_match('/(?:APROB)[\s:#]*([0-9]+)/i', $line, $m)) $details[] = 'Aprob: ' . $m[1];
                if (preg_match('/(?:TARJETA)[\s:#]*([0-9X\-]+)/i', $line, $m)) $details[] = 'Tarjeta: ' . $m[1];
            }
            $desc = 'Consumo Punto de Venta';
            if (!empty($details)) $desc .= ' (' . implode(', ', $details) . ')';

            return [[
                'name' => $desc,
                'description' => $meta['merchant'] ?: 'Punto de Venta',
                'quantity' => 1,
                'price' => $meta['total_bs'],
                'price_usd' => $meta['total_usd'],
                'tax_type' => 'G'
            ]];
        }

        // Strategy for Pago Móvil
        if ($model['type'] === 'PAGO_MOVIL') {
            $concept = '';
            foreach ($lines as $line) {
                if (preg_match('/(?:CONCEPTO|MOTIVO)[\s:]+(.+)$/i', $line, $m)) {
                    $concept = trim($m[1]);
                }
            }
            $desc = 'Pago Móvil a ' . ($meta['merchant'] ?: 'Beneficiario');
            if ($concept) $desc .= " - $concept";

            return [[
                'name' => $desc,
                'description' => 'Pago Móvil Ref #' . ($meta['invoice_number'] ?: 'S/N'),
                'quantity' => 1,
                'price' => $meta['total_bs'],
                'price_usd' => $meta['total_usd'],
                'tax_type' => 'E'
            ]];
        }

        // Strategies for Fiscal, Retail & Electronic Invoices
        $items = [];
        $headerDone = false;
        $inTotalsBlock = false;

        $endKeywords = ['SUBTTL', 'SUBTOTAL', 'EXENTO', 'BI G', 'BASE IMPONIBLE', 'IVA G', 'ALICUOTAS IVA', 'TOTAL', 'BIOPAGO', 'CASHEA', 'FORMAS DE PAGO', 'ARTICULOS VENDIDOS', 'IGTF'];
        $skipPrefixes = ['DATOS', 'RIF', 'CI/RIF', 'CAJERO', 'REGISTRO', 'CAJA', 'NRO', 'NUMERO', 'TELF', 'HORA', 'FECHA', 'DIRECCION', 'TELEFONO', 'CLIENTE', 'SUCURSAL', 'SUICHE', 'MONTO REF', 'ESTA:', 'CONDICION', 'ITEM |'];

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            if (!$headerDone) {
                if (preg_match('/(?:FACTURA|NUMERO|FECHA|CLIENTE|DIRECCION|CODIGO|C10|ESTA:|ITEM\s*\|)/i', $line)) {
                    $headerDone = true;
                    continue;
                }
                // If line looks like a clear item row even before header marker, start immediately
                if (preg_match('/^[\d,\.]+\s*(?:UN|PZ|KG)?\s*x\s*[\d,\.]+/i', $line) ||
                    preg_match('/^\d+\s*\|\s*[\d,\.]+\s*\|/i', $line) ||
                    preg_match('/^\[\s*\d+\s*\]/i', $line)) {
                    $headerDone = true;
                } else {
                    continue;
                }
            }

            // Check for Totals Section boundary (must match at start of line, not inside a product name)
            if (preg_match('/^[\s*#\-]*\b(?:SUBTTL|SUBTOTAL|SUB-TOTAL|BASE\s*IMPONIBLE|BI\s*G\d*|TOTAL(?:\s*(?:A\s*PAGAR|GENERAL|BS\.?))?|ALICUOTAS?|IGTF|FORMAS?\s*DE\s*PAGO|ARTICULOS\s*VENDIDOS)\b/i', $line) ||
                preg_match('/^[\s*#\-]*\b(?:TOTAL\s*EXENTO|EXENTO)\b[\s:#]*(?:BS\.?|BSS)?[\s:#]*[\d,\.]*$/i', $line) ||
                preg_match('/^[\s*#\-]*\b(?:BIOPAGO|CASHEA\s*BS)\b[\s:#]*(?:BS\.?|BSS)?[\s:#]*[\d,\.]*$/i', $line)) {
                
                // Do not trigger totals block if line is a table column header
                if (!preg_match('/(?:ITEM\s*\||CANT.*?PRECIO|PRECIO.*?TOTAL|DESCRIPCION.*?TOTAL)/i', $line)) {
                    $inTotalsBlock = true;
                    break;
                }
            }

            $skip = false;
            foreach ($skipPrefixes as $prefix) {
                if (stripos($line, $prefix) === 0 || preg_match('/^' . preg_quote($prefix, '/') . '[\s:]/i', $line)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            // Strategy A: Table Row (Item | Cant | Descripcion | Precio | Alic | Total)
            if (preg_match('/^\d+\s*\|\s*([\d,\.]+)\s*\|\s*(.+?)\s*\|\s*([\d,\.]+)\s*\|\s*.*?\|\s*([\d,\.]+)$/i', $line, $m)) {
                $qty = $this->parseMoney($m[1]);
                $name = trim($m[2]);
                $total = $this->parseMoney($m[4]);
                $items[] = [
                    'name' => $name,
                    'quantity' => $qty ?: 1,
                    'price' => $total,
                    'price_usd' => $exchangeRate > 0 ? round($total / $exchangeRate, 2) : 0,
                    'tax_type' => 'G'
                ];
                continue;
            }

            // Strategy B: Inverted pattern `[ 1 ] 986,00` then name on next line
            if (preg_match('/^\[\s*(\d+)\s*\]\s*(?:x\s*)?([\d,\.]+)(?:\s*(?:BSS|BS\.?)\s*[\d,\.]+)?$/i', $line, $m) && isset($lines[$i+1])) {
                $qty = (float) $m[1];
                $unitPrice = $this->parseMoney($m[2]);
                $name = trim(preg_replace('/(?:Bs\.?|BSS)?\s*[\d,\.]+$/i', '', $lines[$i+1]));
                $total = $unitPrice * ($qty ?: 1);

                $items[] = [
                    'name' => $name ?: 'Producto',
                    'quantity' => $qty ?: 1,
                    'price' => $total,
                    'price_usd' => $exchangeRate > 0 ? round($total / $exchangeRate, 2) : 0,
                    'tax_type' => 'G'
                ];
                $i++;
                continue;
            }

            // Strategy C: 2-Line Retail/Supermarket: Line 1: [Barcode] Name, Line 2: 1.000 UN x 155,00 (G) 155,00
            if (isset($lines[$i+1]) && preg_match('/^([\d,\.]+)\s*(?:UN|PZ|KG)?\s*x\s*([\d,\.]+)\s*(?:\(([EGR])\))?\s*([\d,\.]+)$/i', $lines[$i+1], $m2)) {
                $rawName = trim(preg_replace('/^\d{7,14}\s+/', '', $line));
                $qty = $this->parseMoney($m2[1]);
                $total = $this->parseMoney($m2[4]);
                $tax = !empty($m2[3]) ? strtoupper($m2[3]) : 'G';

                $items[] = [
                    'name' => $rawName,
                    'quantity' => $qty ?: 1,
                    'price' => $total,
                    'price_usd' => $exchangeRate > 0 ? round($total / $exchangeRate, 2) : 0,
                    'tax_type' => $tax
                ];
                $i++;
                continue;
            }

            // Strategy D: Cant: X on line 1, Name + Total on line 2 (Attica style)
            if (preg_match('/(?:Cant|CANTIDAD)[\s:#]*([\d,\.]+)/i', $line, $m) && isset($lines[$i+1])) {
                $qty = (float) str_replace(',', '.', $m[1]);
                $nextLine = $lines[$i+1];
                if (preg_match('/^(.+?)\s+([\d,\.]{3,})\s*([EGR])?$/i', $nextLine, $m2)) {
                    $name = trim($m2[1]);
                    $total = $this->parseMoney($m2[2]);
                    $tax = !empty($m2[3]) ? strtoupper($m2[3]) : 'G';

                    $items[] = [
                        'name' => $name,
                        'quantity' => $qty ?: 1,
                        'price' => $total,
                        'price_usd' => $exchangeRate > 0 ? round($total / $exchangeRate, 2) : 0,
                        'tax_type' => $tax
                    ];
                    $i++;
                    continue;
                }
            }

            // Strategy E: Single line: Qty x Price Name (Tax) Total (Sucasa style)
            if (preg_match('/^([\d,\.]+)\s*x\s*([\d,\.]+)\s+(.+?)(?:\s+\(([EGR])\))?\s+(?:Bs\.?|BSS)?\s*([\d,\.]+)$/i', $line, $m)) {
                $qty = $this->parseMoney($m[1]);
                $name = trim($m[3]);
                $tax = !empty($m[4]) ? strtoupper($m[4]) : 'G';
                $total = $this->parseMoney($m[5]);

                $items[] = [
                    'name' => $name,
                    'quantity' => $qty ?: 1,
                    'price' => $total,
                    'price_usd' => $exchangeRate > 0 ? round($total / $exchangeRate, 2) : 0,
                    'tax_type' => $tax
                ];
                continue;
            }

            // Strategy F: Code|Name (Tax) Total (Mas Telas / items without qty multiplier)
            if (preg_match('/^(?:[\d\w]+\|)?(.+?)(?:\s+\(([EGR])\))?\s+(?:Bs\.?|BSS)?\s*([\d,\.]{3,})$/i', $line, $m)) {
                $name = trim($m[1]);
                if (strlen($name) > 3 && !preg_match('/^(?:SUC|AV|CALLE|EDIF|CRUCE|VALERA|CARACAS|FACTURA|TOTAL|SUBTTL)/i', $name)) {
                    $tax = !empty($m[2]) ? strtoupper($m[2]) : 'G';
                    $total = $this->parseMoney($m[3]);

                    $items[] = [
                        'name' => $name,
                        'quantity' => 1,
                        'price' => $total,
                        'price_usd' => $exchangeRate > 0 ? round($total / $exchangeRate, 2) : 0,
                        'tax_type' => $tax
                    ];
                    continue;
                }
            }
        }

        // Columnar Split Fallback (thermal paper with items first, prices at bottom)
        if (empty($items) && $model['type'] !== 'POS_VOUCHER' && $model['type'] !== 'PAGO_MOVIL') {
            $priceBlock = $this->findCurrencyAmountsBlock($lines);
            $itemCandidates = [];
            $inItems = false;

            for ($i = 0; $i < count($lines); $i++) {
                $line = $lines[$i];
                if (preg_match('/(?:FACTURA|FECHA|REGISTRO)/i', $line)) {
                    $inItems = true;
                    continue;
                }
                if (preg_match('/(?:SUBTOTAL|SUBTTL|EXENTO|BI\s*G|TOTAL|Z1F)/i', $line)) {
                    $inItems = false;
                    break;
                }
                if ($inItems) {
                    if (preg_match('/^([\d,\.]+)$/', $line, $mQty) && isset($lines[$i+1])) {
                        $qty = (float) str_replace(',', '.', $mQty[1]);
                        $nextLine = $lines[$i+1];
                        if (($nextLine === 'X' || $nextLine === 'x') && isset($lines[$i+2], $lines[$i+3])) {
                            $unitPrice = $this->parseMoney($lines[$i+2]);
                            $desc = $lines[$i+3];
                            $tax = preg_match('/\(([EGR])\)/i', $desc, $tm) ? strtoupper($tm[1]) : 'G';
                            $name = trim(preg_replace('/\s*\([EGR]\)/i', '', $desc));
                            $itemCandidates[] = [
                                'quantity' => $qty,
                                'unit_price' => $unitPrice,
                                'name' => $name,
                                'tax_type' => $tax
                            ];
                            $i += 3;
                            continue;
                        } elseif (preg_match('/^([\d,\.]+)\s+(.+)$/', $nextLine, $m2)) {
                            $unitPrice = $this->parseMoney($m2[1]);
                            $desc = $m2[2];
                            $tax = preg_match('/\(([EGR])\)/i', $desc, $tm) ? strtoupper($tm[1]) : 'G';
                            $name = trim(preg_replace('/\s*\([EGR]\)/i', '', $desc));
                            $itemCandidates[] = [
                                'quantity' => $qty,
                                'unit_price' => $unitPrice,
                                'name' => $name,
                                'tax_type' => $tax
                            ];
                            $i++;
                            continue;
                        }
                    }
                    if (preg_match('/^(?:\*|[A-Z0-9]{3,})/', $line) && !preg_match('/^(?:AU|AV|CALLE|URB|EDIF|DATOS|REGISTRO|CAJERO|RIF|RTF|SUCURSAL)/i', $line)) {
                        $tax = preg_match('/\(([EGR])\)/i', $line, $tm) ? strtoupper($tm[1]) : 'G';
                        $name = trim(preg_replace('/\s*\([EGR]\)/i', '', $line));
                        $itemCandidates[] = [
                            'quantity' => 1.0,
                            'unit_price' => 0.0,
                            'name' => $name,
                            'tax_type' => $tax
                        ];
                    }
                }
            }

            if (!empty($itemCandidates) && !empty($priceBlock)) {
                $columnarItems = [];
                foreach ($itemCandidates as $k => $cand) {
                    $price = isset($priceBlock[$k]) ? $priceBlock[$k]['val'] : ($cand['unit_price'] > 0 ? round($cand['unit_price'] * $cand['quantity'], 2) : 0);
                    $columnarItems[] = [
                        'name' => $cand['name'],
                        'quantity' => $cand['quantity'],
                        'price' => $price,
                        'price_usd' => $exchangeRate > 0 ? round($price / $exchangeRate, 2) : 0,
                        'tax_type' => $cand['tax_type']
                    ];
                }
                if (!empty($columnarItems)) {
                    return $columnarItems;
                }
            }
        }

        // Fallback: If no items found, synthesize one from total
        if (empty($items) && $meta['total_bs'] > 0) {
            $items[] = [
                'name' => $meta['merchant'] ?: 'Consumo / Gasto',
                'description' => 'Factura #' . ($meta['invoice_number'] ?: 'S/N'),
                'quantity' => 1,
                'price' => $meta['total_bs'],
                'price_usd' => $meta['total_usd'],
                'tax_type' => 'G'
            ];
        }

        return $items;
    }

    /**
     * Safely parse Venezuelan and International numeric string representations to float.
     */
    public function parseMoney(string $str): float
    {
        $clean = trim($str);
        $clean = preg_replace('/(?<=[,\.])\s+(?=\d)/', '', $clean);
        $clean = preg_replace('/(?<=\d)\s+(?=[,\.\d])/', '', $clean);
        $clean = preg_replace('/[^\d,\.]/', '', $clean);

        if (empty($clean)) return 0.0;

        if (strpos($clean, '.') !== false && strpos($clean, ',') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (strpos($clean, ',') !== false) {
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }
}