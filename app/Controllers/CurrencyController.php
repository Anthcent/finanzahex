<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class CurrencyController extends BaseController
{
    public function getBCVRate()
    {
        // Suppress errors for DOMDocument HTML parsing
        libxml_use_internal_errors(true);

        try {
            $url = 'https://www.bcv.org.ve/';
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
                    'timeout' => 10
                ]
            ]);

            $html = file_get_contents($url, false, $context);

            if (!$html) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Could not fetch BCV']);
            }

            $dom = new \DOMDocument();
            $dom->loadHTML($html);
            
            // The BCV structure typically contains the rate inside specific IDs/Classes
            // We'll look for the specific structure for USD
            // Usually it's in a div id="dolar" -> div class="col-sm-6 col-xs-6 centrado" -> strong
            
            $rate = 0;
            $xpath = new \DOMXPath($dom);
            
            // Try to find the specific container for USD
            // Note: BCV structure might change, but typically looks like this.
            $nodes = $xpath->query('//div[@id="dolar"]//strong');

            if ($nodes->length > 0) {
                $rateStr = $nodes->item(0)->nodeValue;
                // Convert VE format "45,23" to "45.23"
                $rateStr = str_replace(',', '.', $rateStr);
                $rate = floatval($rateStr);
            }

            if ($rate > 0) {
                return $this->response->setJSON(['status' => 'success', 'rate' => $rate]);
            }

            return $this->response->setJSON(['status' => 'error', 'message' => 'Rate not found in HTML']);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
