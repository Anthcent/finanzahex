<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\CategoryModel;
use App\Models\TransactionModel;
use App\Models\TransactionItemModel;
use App\Models\AuditLogModel;
use App\Services\InvoiceParserService;

class OcrController extends BaseController
{
    /**
     * Display OCR Scanner module view.
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();

        $accounts = $accountModel->where('status', 'active')->orderBy('name', 'ASC')->findAll();
        $categories = $categoryModel->orderBy('name', 'ASC')->findAll();

        $rateRow = $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $exchangeRate = (float) ($rateRow['value'] ?? 50.0);

        $apiKeyRow = $db->table('settings')->where('key', 'ocr_space_api_key')->get()->getRowArray();
        $ocrApiKey = $apiKeyRow['value'] ?? '';

        return view('ocr/index', [
            'accounts' => $accounts,
            'categories' => $categories,
            'exchangeRate' => $exchangeRate,
            'ocrApiKey' => $ocrApiKey,
        ]);
    }

    /**
     * Process one or more images via OCR.space and parse invoice details.
     */
    public function process()
    {
        $db = \Config\Database::connect();
        $rateRow = $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $exchangeRate = (float) ($rateRow['value'] ?? 50.0);

        $apiKeyRow = $db->table('settings')->where('key', 'ocr_space_api_key')->get()->getRowArray();
        $apiKey = !empty($apiKeyRow['value']) ? trim($apiKeyRow['value']) : 'helloworld';

        $parser = new InvoiceParserService();
        $results = [];

        // Check for JSON base64 images payload
        $json = $this->request->getJSON();
        $base64Images = [];

        if ($json && !empty($json->images) && is_array($json->images)) {
            $base64Images = $json->images;
        }

        // Check for multipart file uploads
        $files = $this->request->getFiles();
        if (!empty($files['images'])) {
            $uploadedFiles = is_array($files['images']) ? $files['images'] : [$files['images']];
            foreach ($uploadedFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $mime = $file->getMimeType();
                    $data = file_get_contents($file->getTempName());
                    $base64Images[] = 'data:' . $mime . ';base64,' . base64_encode($data);
                }
            }
        }

        if (empty($base64Images)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se recibió ninguna imagen para procesar.'
            ]);
        }

        foreach ($base64Images as $idx => $b64Image) {
            $ocrText = $this->callOcrSpaceApi($b64Image, $apiKey);

            if ($ocrText === false) {
                // If OCR call failed, provide an empty structure so user can still manually edit
                $parsedInvoices = [[
                    'model_type' => 'GENERIC_RECEIPT',
                    'model_label' => 'Factura / Recibo General',
                    'merchant' => 'Factura #' . ($idx + 1),
                    'rif' => '',
                    'invoice_number' => '',
                    'date' => date('Y-m-d'),
                    'time' => date('H:i'),
                    'items' => [],
                    'subtotal' => 0.0,
                    'exento' => 0.0,
                    'base_imponible' => 0.0,
                    'iva_amount' => 0.0,
                    'iva_rate' => 16.0,
                    'igtf_amount' => 0.0,
                    'total_bs' => 0.0,
                    'total_usd' => 0.0,
                    'exchange_rate' => $exchangeRate,
                    'payment_method' => null,
                    'cashea' => null,
                    'raw_text' => 'Error de conexión con el servicio OCR o imagen ilegible.'
                ]];
            } else {
                $parsedInvoices = $parser->parseMulti($ocrText, $exchangeRate);
            }

            foreach ($parsedInvoices as $parsed) {
                // Include image preview and operational defaults
                $parsed['image_preview'] = $b64Image;
                $parsed['uid'] = uniqid('inv_');
                $parsed['account_id'] = '';
                $parsed['category_id'] = '';
                $parsed['owner'] = 'Negocio';
                $parsed['save_mode'] = 'consolidated'; // 'consolidated' | 'individual'

                $results[] = $parsed;
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $results
        ]);
    }

    /**
     * Save reviewed and confirmed scanned invoices into transactions.
     */
    public function save()
    {
        $json = $this->request->getJSON();

        if (!$json || empty($json->invoices) || !is_array($json->invoices)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se recibieron datos de facturas para guardar.'
            ]);
        }

        $db = \Config\Database::connect();
        $transModel = new TransactionModel();
        $itemModel = new TransactionItemModel();
        $accountModel = new AccountModel();

        $db->transStart();

        try {
            $savedCount = 0;

            foreach ($json->invoices as $inv) {
                $accountId = $inv->account_id ?? null;
                $categoryId = $inv->category_id ?? null;
                $totalBs = (float) ($inv->total_bs ?? 0);
                $totalUsd = (float) ($inv->total_usd ?? 0);
                $rate = (float) ($inv->exchange_rate ?? 1);
                $merchant = trim($inv->merchant ?? 'Gasto OCR');
                $invoiceNum = trim($inv->invoice_number ?? 'S/N');
                $rif = trim($inv->rif ?? '');
                $owner = $inv->owner ?? 'Negocio';
                $saveMode = $inv->save_mode ?? 'consolidated';
                $date = !empty($inv->date) ? $inv->date : date('Y-m-d');
                $createdAt = $date . ' ' . date('H:i:s');

                if (empty($accountId)) {
                    throw new \Exception("Debe seleccionar una cuenta bancaria para la factura de $merchant.");
                }

                $account = $accountModel->find($accountId);
                if (!$account) {
                    throw new \Exception("La cuenta bancaria seleccionada no existe.");
                }

                if (empty($categoryId)) {
                    $firstCat = (new CategoryModel())->first();
                    $categoryId = $firstCat ? $firstCat['id'] : 1;
                }

                $description = "Factura #$invoiceNum - $merchant";
                if ($rif) {
                    $description .= " ($rif)";
                }

                if ($saveMode === 'individual' && !empty($inv->items) && count($inv->items) > 1) {
                    // Save each item as an individual expense transaction
                    foreach ($inv->items as $it) {
                        $itemPrice = (float) ($it->price ?? 0);
                        $itemPriceUsd = (float) ($it->price_usd ?? 0);
                        $itemName = trim($it->name ?? 'Producto');
                        $itemQty = (float) ($it->quantity ?? 1);

                        $itemTransId = $transModel->insert([
                            'account_id' => $accountId,
                            'category_id' => $categoryId,
                            'amount' => $itemPrice,
                            'amount_usd' => $itemPriceUsd,
                            'exchange_rate' => $rate,
                            'type' => 'expense',
                            'owner' => $owner,
                            'description' => "$itemName (Fac #$invoiceNum - $merchant)",
                            'created_at' => $createdAt
                        ]);

                        $itemModel->insert([
                            'transaction_id' => $itemTransId,
                            'name' => $itemName,
                            'quantity' => $itemQty,
                            'price' => $itemPrice,
                            'price_usd' => $itemPriceUsd
                        ]);
                    }
                } else {
                    // Save as 1 consolidated expense transaction with attached items
                    $transId = $transModel->insert([
                        'account_id' => $accountId,
                        'category_id' => $categoryId,
                        'amount' => $totalBs,
                        'amount_usd' => $totalUsd,
                        'exchange_rate' => $rate,
                        'type' => 'expense',
                        'owner' => $owner,
                        'description' => $description,
                        'created_at' => $createdAt
                    ]);

                    if (!$transId) {
                        throw new \Exception("Error al registrar la transacción para $merchant.");
                    }

                    // Save line items
                    if (!empty($inv->items) && is_array($inv->items)) {
                        foreach ($inv->items as $it) {
                            $itemModel->insert([
                                'transaction_id' => $transId,
                                'name' => trim($it->name ?? 'Item'),
                                'quantity' => (float) ($it->quantity ?? 1),
                                'price' => (float) ($it->price ?? 0),
                                'price_usd' => (float) ($it->price_usd ?? 0),
                                'description' => !empty($it->tax_type) ? "IVA: {$it->tax_type}" : null
                            ]);
                        }
                    }
                }

                // Deduct from bank account balance
                $newBalance = $account['balance'] - $totalBs;
                $accountModel->update($accountId, ['balance' => $newBalance]);

                // Audit log
                AuditLogModel::log(
                    'transactions',
                    'create',
                    0,
                    null,
                    ['merchant' => $merchant, 'amount' => $totalBs],
                    ['account_id' => $accountId, 'delta' => -$totalBs],
                    "Registro de gasto escaneado OCR ($merchant)"
                );

                $savedCount++;
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('No se pudo confirmar la transacción en la base de datos.');
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => "¡$savedCount factura(s) y sus gastos fueron registrados con éxito!",
                'count' => $savedCount
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save or update OCR.space API Key.
     */
    public function saveSettings()
    {
        $json = $this->request->getJSON();
        $apiKey = trim($json->api_key ?? '');

        $db = \Config\Database::connect();
        $builder = $db->table('settings');

        if ($builder->where('key', 'ocr_space_api_key')->countAllResults() > 0) {
            $builder->where('key', 'ocr_space_api_key')->update(['value' => $apiKey]);
        } else {
            $builder->insert(['key' => 'ocr_space_api_key', 'value' => $apiKey]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'API Key de OCR.space guardada exitosamente.'
        ]);
    }

    /**
     * Call OCR.space REST API with Engine 2 and Spanish language.
     */
    protected function callOcrSpaceApi(string $base64Image, string $apiKey)
    {
        $url = 'https://api.ocr.space/parse/image';

        $postFields = [
            'apikey' => $apiKey,
            'base64Image' => $base64Image,
            'language' => 'spa',
            'isOverlayRequired' => 'false',
            'isTable' => 'true',
            'OCREngine' => '2',
            'scale' => 'true',
            'detectOrientation' => 'true'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            return false;
        }

        $resJson = json_decode($response, true);

        if (isset($resJson['ParsedResults'][0]['ParsedText'])) {
            return $resJson['ParsedResults'][0]['ParsedText'];
        }

        return false;
    }
}
