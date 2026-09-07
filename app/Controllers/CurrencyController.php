<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class CurrencyController extends BaseController
{
    public function getBCVRate()
    {
        libxml_use_internal_errors(true);
        $rate = 0.0;
        $source = '';

        // Source 1: Direct scrape from BCV official site
        try {
            $url = 'https://www.bcv.org.ve/';
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
                    'timeout' => 4
                ]
            ]);

            $html = @file_get_contents($url, false, $context);

            if ($html) {
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query('//div[@id="dolar"]//strong');

                if ($nodes->length > 0) {
                    $rateStr = trim($nodes->item(0)->nodeValue);
                    $rateStr = str_replace([' ', "\r", "\n", "\t"], '', $rateStr);
                    $rateStr = str_replace(',', '.', $rateStr);
                    $parsed = floatval($rateStr);
                    if ($parsed > 0) {
                        $rate = round($parsed, 2);
                        $source = 'BCV Oficial';
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through to fallback API
        }

        // Source 2: High-speed Fallback API (ve.dolarapi.com)
        if ($rate <= 0) {
            try {
                $altUrl = 'https://ve.dolarapi.com/v1/dolares/oficial';
                $altContext = stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    'http' => ['header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 6]
                ]);
                $jsonStr = @file_get_contents($altUrl, false, $altContext);
                if ($jsonStr) {
                    $altData = json_decode($jsonStr, true);
                    if (!empty($altData['promedio']) && (float)$altData['promedio'] > 0) {
                        $rate = round((float)$altData['promedio'], 2);
                        $source = 'DolarAPI Oficial';
                    }
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        if ($rate > 0) {
            // Persist the updated rate to settings table
            try {
                $db = \Config\Database::connect();
                $builder = $db->table('settings');
                if ($builder->where('key', 'bcv_usd_rate')->countAllResults() > 0) {
                    $builder->where('key', 'bcv_usd_rate')->update([
                        'value' => (string) $rate,
                    ]);
                } else {
                    $builder->insert([
                        'key' => 'bcv_usd_rate',
                        'value' => (string) $rate,
                    ]);
                }
            } catch (\Throwable $e) {
                // Log or ignore if DB temporarily locked
            }

            return $this->response->setJSON([
                'status' => 'success',
                'rate' => $rate,
                'source' => $source,
                'formatted' => number_format($rate, 2, ',', '.')
            ]);
        }

        // If both failed, fetch last stored rate from settings table
        try {
            $db = \Config\Database::connect();
            $row = $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray();
            if (!empty($row['value']) && (float)$row['value'] > 0) {
                $cachedRate = (float)$row['value'];
                return $this->response->setJSON([
                    'status' => 'success',
                    'rate' => $cachedRate,
                    'source' => 'Caché local',
                    'formatted' => number_format($cachedRate, 2, ',', '.')
                ]);
            }
        } catch (\Throwable $e) {}

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'No se pudo obtener la tasa oficial del dólar.'
        ]);
    }
}
