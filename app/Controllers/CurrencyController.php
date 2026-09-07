<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class CurrencyController extends BaseController
{
    public function getBCVRates()
    {
        $rates = ['USD' => 0.0, 'EUR' => 0.0];
        $source = 'BCV Oficial';

        try {
            $context = stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
                    'timeout' => 6,
                ],
            ]);
            $html = @file_get_contents('https://www.bcv.org.ve/', false, $context);
            if ($html) {
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);
                $rates['USD'] = $this->readOfficialRate($xpath, 'dolar');
                $rates['EUR'] = $this->readOfficialRate($xpath, 'euro');
            }
        } catch (\Throwable $e) {
            // Cached values below keep the calculator usable when BCV is unavailable.
        }

        if ($rates['USD'] <= 0) {
            try {
                $json = @file_get_contents('https://ve.dolarapi.com/v1/dolares/oficial', false, stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    'http' => ['header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 6],
                ]));
                $data = $json ? json_decode($json, true) : null;
                if (!empty($data['promedio'])) {
                    $rates['USD'] = round((float) $data['promedio'], 4);
                    $source = 'BCV / respaldo oficial';
                }
            } catch (\Throwable $e) {
                // Use cache below.
            }
        }

        try {
            $db = \Config\Database::connect();
            foreach (['USD' => 'bcv_usd_rate', 'EUR' => 'bcv_eur_rate'] as $currency => $key) {
                if ($rates[$currency] > 0) {
                    $builder = $db->table('settings');
                    if ($builder->where('key', $key)->countAllResults() > 0) {
                        $db->table('settings')->where('key', $key)->update(['value' => (string) $rates[$currency]]);
                    } else {
                        $db->table('settings')->insert(['key' => $key, 'value' => (string) $rates[$currency]]);
                    }
                } else {
                    $row = $db->table('settings')->select('value')->where('key', $key)->get()->getRowArray();
                    $rates[$currency] = max(0, (float) ($row['value'] ?? 0));
                    if ($rates[$currency] > 0) {
                        $source = 'Caché local BCV';
                    }
                }
            }
        } catch (\Throwable $e) {
            // Rates already fetched remain available even if persistence fails.
        }

        if ($rates['USD'] <= 0 && $rates['EUR'] <= 0) {
            return $this->response->setStatusCode(503)->setJSON([
                'status' => 'error',
                'message' => 'No se pudieron obtener las tasas BCV. Puedes ingresarlas manualmente en la calculadora.',
                'rates' => $rates,
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'rates' => $rates,
            'source' => $source,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

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

    private function readOfficialRate(\DOMXPath $xpath, string $id): float
    {
        $nodes = $xpath->query('//div[@id="' . $id . '"]//strong');
        if (!$nodes || $nodes->length === 0) {
            return 0.0;
        }

        $value = preg_replace('/\s+/', '', trim((string) $nodes->item(0)->nodeValue));
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return round(max(0, (float) $value), 4);
    }
}
