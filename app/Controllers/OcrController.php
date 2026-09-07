<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\CategoryModel;
use App\Models\TransactionModel;
use App\Models\TransactionItemModel;
use App\Models\AuditLogModel;
use App\Models\OcrInvoiceModel;
use App\Services\InvoiceParserService;

class OcrController extends BaseController
{
    /**
     * Display OCR Scanner module view.
     */
    public function index()
    {
        $this->ensureTableExists();

        $db = \Config\Database::connect();
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();
        $ocrInvoiceModel = new OcrInvoiceModel();

        $accounts = $accountModel->where('status', 'active')->orderBy('name', 'ASC')->findAll();
        $categories = $categoryModel->orderBy('name', 'ASC')->findAll();

        $rateRow = $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $exchangeRate = (float) ($rateRow['value'] ?? 50.0);

        $apiKeyRow = $db->table('settings')->where('key', 'ocr_space_api_key')->get()->getRowArray();
        $ocrApiKey = $apiKeyRow['value'] ?? '';

        $pendingInvoices = $ocrInvoiceModel->getPendingWithAlerts();
        $overdueCount = $ocrInvoiceModel->countOverdue72h();
        $historyInvoices = $ocrInvoiceModel->getHistory();

        return view('ocr/index', [
            'accounts' => $accounts,
            'categories' => $categories,
            'exchangeRate' => $exchangeRate,
            'ocrApiKey' => $ocrApiKey,
            'pendingInvoices' => $pendingInvoices,
            'overdueCount' => $overdueCount,
            'historyInvoices' => $historyInvoices,
        ]);
    }

    /**
     * Process one or more images via OCR.space and parse invoice details for manual review.
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

        $json = $this->request->getJSON();
        $base64Images = [];

        if ($json && !empty($json->images) && is_array($json->images)) {
            $base64Images = $json->images;
        }

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
                    'raw_text' => ''
                ]];
            } else {
                $parsedInvoices = $parser->parseMulti($ocrText, $exchangeRate);
            }

            foreach ($parsedInvoices as $subIdx => $inv) {
                $inv['image_preview'] = $b64Image;
                $inv['account_id'] = null;
                $inv['category_id'] = null;
                $inv['owner'] = 'Negocio';
                $inv['uid'] = uniqid('inv_');
                $results[] = $inv;
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $results,
            'count' => count($results)
        ]);
    }

    /**
     * Carga Rápida: Scan, parse, auto-deduct active bank account, and save as pending review.
     */
    public function quickProcess()
    {
        $this->ensureTableExists();

        $db = \Config\Database::connect();
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();
        $transModel = new TransactionModel();
        $itemModel = new TransactionItemModel();
        $ocrInvoiceModel = new OcrInvoiceModel();

        $rateRow = $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $exchangeRate = (float) ($rateRow['value'] ?? 50.0);

        $apiKeyRow = $db->table('settings')->where('key', 'ocr_space_api_key')->get()->getRowArray();
        $apiKey = !empty($apiKeyRow['value']) ? trim($apiKeyRow['value']) : 'helloworld';

        $json = $this->request->getJSON();
        $base64Images = [];
        $targetAccountId = (int) ($json->account_id ?? 0);
        $targetCategoryId = (int) ($json->category_id ?? 0);
        $owner = !empty($json->owner) && in_array($json->owner, ['Personal', 'Negocio']) ? $json->owner : 'Negocio';

        if ($json && !empty($json->images) && is_array($json->images)) {
            $base64Images = $json->images;
        }

        if (empty($base64Images)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se recibió ninguna imagen para el escaneo rápido.'
            ]);
        }

        // Determine target bank account with robust fallback
        if ($targetAccountId <= 0) {
            $defaultAcc = $accountModel->where('status', 'active')->orderBy('id', 'ASC')->first();
            if (!$defaultAcc) {
                $defaultAcc = $accountModel->orderBy('id', 'ASC')->first();
            }
            if (!$defaultAcc) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No hay cuentas bancarias disponibles para debitar el gasto.'
                ]);
            }
            $targetAccountId = (int) $defaultAcc['id'];
        }

        // Verify account exists
        $account = $accountModel->find($targetAccountId);
        if (!$account) {
            $account = $accountModel->where('status', 'active')->first() ?? $accountModel->first();
            if (!$account) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'La cuenta bancaria seleccionada no existe.'
                ]);
            }
            $targetAccountId = (int) $account['id'];
        }

        // Determine default category (guarantee valid category ID in DB for Foreign Key & NOT NULL)
        if ($targetCategoryId <= 0) {
            $defaultCat = $categoryModel->where('type', 'expense')->orderBy('id', 'ASC')->first();
            if (!$defaultCat) {
                $defaultCat = $categoryModel->orderBy('id', 'ASC')->first();
            }
            if ($defaultCat) {
                $targetCategoryId = (int) $defaultCat['id'];
            } else {
                $targetCategoryId = (int) $categoryModel->insert([
                    'name' => 'Gastos Generales',
                    'type' => 'expense',
                    'icon' => 'receipt'
                ]);
            }
        } else {
            $chosenCat = $categoryModel->find($targetCategoryId);
            if (!$chosenCat) {
                $defaultCat = $categoryModel->where('type', 'expense')->first() ?? $categoryModel->first();
                $targetCategoryId = $defaultCat ? (int) $defaultCat['id'] : 1;
            }
        }

        $parser = new InvoiceParserService();
        $allParsedInvoices = [];

        // 1. Run OCR calls FIRST, outside of DB transaction to avoid idle connection aborts
        foreach ($base64Images as $idx => $b64Image) {
            $ocrText = $this->callOcrSpaceApi($b64Image, $apiKey);

            if ($ocrText === false) {
                $parsedList = [[
                    'model_type' => 'GENERIC_RECEIPT',
                    'model_label' => 'Factura / Recibo Rápido',
                    'merchant' => 'Factura Rápida #' . ($idx + 1),
                    'rif' => '',
                    'invoice_number' => 'S/N',
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
                    'raw_text' => ''
                ]];
            } else {
                $parsedList = $parser->parseMulti($ocrText, $exchangeRate);
            }

            foreach ($parsedList as $inv) {
                $allParsedInvoices[] = $inv;
            }
        }

        if (empty($allParsedInvoices)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se pudo extraer texto legible de la factura.'
            ]);
        }

        // 2. Open DB transaction to persist transactions and pending review record
        $savedInvoices = [];
        $db->transStart();

        try {
            foreach ($allParsedInvoices as $inv) {
                $totalBs = max(0.0, (float) ($inv['total_bs'] ?? 0));
                $totalUsd = max(0.0, (float) ($inv['total_usd'] ?? 0));
                $rate = (float) ($inv['exchange_rate'] ?? $exchangeRate);
                if ($rate <= 0) $rate = $exchangeRate > 0 ? $exchangeRate : 50.0;
                if ($totalUsd <= 0 && $totalBs > 0 && $rate > 0) {
                    $totalUsd = round($totalBs / $rate, 2);
                }

                $merchant = trim($inv['merchant'] ?? 'Gasto Factura');
                if (empty($merchant)) $merchant = 'Gasto Factura';
                $invoiceNum = trim($inv['invoice_number'] ?? '');

                // Robust Date and Time normalization
                $rawDate = !empty($inv['date']) ? trim($inv['date']) : date('Y-m-d');
                $rawTime = !empty($inv['time']) ? trim($inv['time']) : date('H:i:s');
                $parsedTs = strtotime("$rawDate $rawTime");
                if ($parsedTs === false || $parsedTs <= 0) {
                    $parsedTs = strtotime($rawDate);
                }
                if ($parsedTs === false || $parsedTs <= 0) {
                    $parsedTs = time();
                }

                $createdAt = date('Y-m-d H:i:s', $parsedTs);
                $invDate = date('Y-m-d', $parsedTs);
                $invTime = date('H:i', $parsedTs);

                $desc = "Gasto Rápido: $merchant" . ($invoiceNum ? " (Fac #$invoiceNum)" : "");

                // 1. Create Transaction in ledger
                $transId = $transModel->insert([
                    'account_id' => $targetAccountId,
                    'category_id' => $targetCategoryId,
                    'amount' => $totalBs,
                    'amount_usd' => $totalUsd,
                    'exchange_rate' => $rate,
                    'type' => 'expense',
                    'owner' => $owner,
                    'description' => mb_substr($desc, 0, 250),
                    'created_at' => $createdAt
                ]);

                if (!$transId) {
                    $dbErr = $db->error();
                    throw new \Exception('No se pudo registrar la transacción: ' . ($dbErr['message'] ?? 'Error desconocido'));
                }

                // 2. Insert line items
                $itemsList = !empty($inv['items']) && is_array($inv['items']) ? $inv['items'] : [];
                if (!empty($itemsList)) {
                    foreach ($itemsList as $it) {
                        $rawQty = (float) ($it['quantity'] ?? 1);
                        $intQty = max(1, (int) round($rawQty));
                        $itemDesc = !empty($it['tax_type']) ? "IVA: {$it['tax_type']}" : "";
                        if (abs($rawQty - $intQty) > 0.001) {
                            $itemDesc = trim("$itemDesc [Cant: " . number_format($rawQty, 2, ',', '.') . "]");
                        }
                        $itemModel->insert([
                            'transaction_id' => (int) $transId,
                            'name' => mb_substr(trim($it['name'] ?? 'Producto'), 0, 250),
                            'quantity' => $intQty,
                            'price' => (float) ($it['price'] ?? 0),
                            'price_usd' => (float) ($it['price_usd'] ?? 0),
                            'description' => $itemDesc ? mb_substr($itemDesc, 0, 250) : null
                        ]);
                    }
                } elseif ($totalBs > 0) {
                    $itemModel->insert([
                        'transaction_id' => (int) $transId,
                        'name' => mb_substr($merchant, 0, 250),
                        'quantity' => 1,
                        'price' => $totalBs,
                        'price_usd' => $totalUsd,
                        'description' => 'Factura rápida escaneada'
                    ]);
                }

                // 3. Deduct from bank account balance (only if positive)
                if ($totalBs > 0) {
                    $freshAcc = $accountModel->find($targetAccountId);
                    if ($freshAcc) {
                        $curBal = (float) ($freshAcc['balance'] ?? 0);
                        $accountModel->update($targetAccountId, ['balance' => $curBal - $totalBs]);
                    }
                }

                // 4. Record in ocr_invoices as pending_review
                $ocrInvoiceId = $ocrInvoiceModel->insert([
                    'transaction_id' => (int) $transId,
                    'account_id' => $targetAccountId,
                    'category_id' => $targetCategoryId,
                    'merchant' => mb_substr($merchant, 0, 250),
                    'rif' => mb_substr($inv['rif'] ?? '', 0, 60),
                    'invoice_number' => mb_substr($invoiceNum, 0, 60),
                    'model_type' => mb_substr($inv['model_type'] ?? 'GENERIC_RECEIPT', 0, 60),
                    'model_label' => mb_substr($inv['model_label'] ?? 'Factura / Recibo General', 0, 120),
                    'invoice_date' => $invDate,
                    'invoice_time' => mb_substr($invTime, 0, 30),
                    'total_bs' => $totalBs,
                    'total_usd' => $totalUsd,
                    'exchange_rate' => $rate,
                    'subtotal' => (float) ($inv['subtotal'] ?? 0),
                    'exento' => (float) ($inv['exento'] ?? 0),
                    'base_imponible' => (float) ($inv['base_imponible'] ?? 0),
                    'iva_amount' => (float) ($inv['iva_amount'] ?? 0),
                    'iva_rate' => (float) ($inv['iva_rate'] ?? 16.0),
                    'igtf_amount' => (float) ($inv['igtf_amount'] ?? 0),
                    'payment_method' => !empty($inv['payment_method']) ? mb_substr($inv['payment_method'], 0, 60) : null,
                    'cashea_amount' => (float) ($inv['cashea'] ?? 0),
                    'owner' => $owner,
                    'items_json' => json_encode($itemsList, JSON_UNESCAPED_UNICODE),
                    'raw_text' => $inv['raw_text'] ?? '',
                    'status' => 'pending_review',
                    'quick_scan' => 1,
                    'created_at' => $createdAt,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                try {
                    $accName = $account['name'] ?? 'Cuenta';
                    AuditLogModel::log(
                        'ocr_invoices',
                        'create',
                        $ocrInvoiceId,
                        null,
                        ['merchant' => $merchant, 'total_bs' => $totalBs, 'quick_scan' => 1],
                        ['account_id' => $targetAccountId, 'delta' => -$totalBs],
                        "Carga Rápida OCR: $merchant ($totalBs Bs) descontado de $accName [Pendiente por revisar]"
                    );
                } catch (\Throwable $ignored) {}

                $savedInvoices[] = [
                    'id' => $ocrInvoiceId,
                    'merchant' => $merchant,
                    'total_bs' => $totalBs,
                    'total_usd' => $totalUsd,
                    'invoice_number' => $invoiceNum
                ];
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                $dbErr = $db->error();
                $detail = !empty($dbErr['message']) ? ': ' . $dbErr['message'] : '';
                throw new \Exception('Error al registrar en la base de datos' . $detail);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => '¡Factura registrada automáticamente en Carga Rápida y descontada de la cuenta! Ha quedado en la lista de Pendientes por Revisar.',
                'saved_count' => count($savedInvoices),
                'pending_invoices' => $ocrInvoiceModel->getPendingWithAlerts(),
                'overdue_count' => $ocrInvoiceModel->countOverdue72h()
            ]);

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Carga Rapida error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error al guardar la factura: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save confirmed invoices from manual review into transactions & ocr_invoices.
     */
    public function save()
    {
        $this->ensureTableExists();

        $json = $this->request->getJSON();
        if (!$json || empty($json->invoices) || !is_array($json->invoices)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No hay facturas para guardar.'
            ]);
        }

        $transModel = new TransactionModel();
        $itemModel = new TransactionItemModel();
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();
        $ocrInvoiceModel = new OcrInvoiceModel();
        $db = \Config\Database::connect();

        $db->transStart();

        try {
            $savedCount = 0;

            foreach ($json->invoices as $inv) {
                $accountId = (int) ($inv->account_id ?? 0);
                $categoryId = !empty($inv->category_id) ? (int) $inv->category_id : 0;
                $merchant = trim($inv->merchant ?? 'Gasto Factura');
                if (empty($merchant)) $merchant = 'Gasto Factura';
                $invoiceNum = trim($inv->invoice_number ?? '');

                // Ensure category exists
                if ($categoryId <= 0) {
                    $defaultCat = $categoryModel->where('type', 'expense')->orderBy('id', 'ASC')->first() ?? $categoryModel->first();
                    $categoryId = $defaultCat ? (int) $defaultCat['id'] : 1;
                }

                // Robust Date and Time normalization
                $rawDate = !empty($inv->date) ? trim($inv->date) : date('Y-m-d');
                $rawTime = !empty($inv->time) ? trim($inv->time) : date('H:i:s');
                $parsedTs = strtotime("$rawDate $rawTime");
                if ($parsedTs === false || $parsedTs <= 0) {
                    $parsedTs = strtotime($rawDate);
                }
                if ($parsedTs === false || $parsedTs <= 0) {
                    $parsedTs = time();
                }

                $createdAt = date('Y-m-d H:i:s', $parsedTs);
                $invDate = date('Y-m-d', $parsedTs);
                $invTime = date('H:i', $parsedTs);

                $totalBs = max(0.0, (float) ($inv->total_bs ?? 0));
                $totalUsd = max(0.0, (float) ($inv->total_usd ?? 0));
                $rate = (float) ($inv->exchange_rate ?? 50.0);
                if ($rate <= 0) $rate = 50.0;
                if ($totalUsd <= 0 && $totalBs > 0 && $rate > 0) {
                    $totalUsd = round($totalBs / $rate, 2);
                }

                $owner = !empty($inv->owner) && in_array($inv->owner, ['Personal', 'Negocio']) ? $inv->owner : 'Negocio';

                if ($accountId <= 0) {
                    $defaultAcc = $accountModel->where('status', 'active')->first() ?? $accountModel->first();
                    if ($defaultAcc) {
                        $accountId = (int) $defaultAcc['id'];
                    } else {
                        throw new \Exception("Debe seleccionar una cuenta bancaria para la factura de $merchant.");
                    }
                }

                $account = $accountModel->find($accountId);
                if (!$account) {
                    throw new \Exception("La cuenta bancaria seleccionada para $merchant no existe.");
                }

                $description = "Gasto Factura: $merchant" . ($invoiceNum ? " (Fac #$invoiceNum)" : "");

                // 1. Insert Transaction
                $transId = $transModel->insert([
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'amount' => $totalBs,
                    'amount_usd' => $totalUsd,
                    'exchange_rate' => $rate,
                    'type' => 'expense',
                    'owner' => $owner,
                    'description' => mb_substr($description, 0, 250),
                    'created_at' => $createdAt
                ]);

                if (!$transId) {
                    $dbErr = $db->error();
                    throw new \Exception('Error al registrar transacción: ' . ($dbErr['message'] ?? 'Error'));
                }

                // 2. Insert Items
                $itemsArray = [];
                if (!empty($inv->items) && is_array($inv->items)) {
                    foreach ($inv->items as $it) {
                        $p = (float) ($it->price ?? 0);
                        $pu = (float) ($it->price_usd ?? 0);
                        $rawQty = (float) ($it->quantity ?? 1);
                        $intQty = max(1, (int) round($rawQty));
                        $name = trim($it->name ?? 'Item');
                        $taxType = !empty($it->tax_type) ? $it->tax_type : 'G';
                        $itemDesc = "IVA: $taxType";
                        if (abs($rawQty - $intQty) > 0.001) {
                            $itemDesc = trim("$itemDesc [Cant: " . number_format($rawQty, 2, ',', '.') . "]");
                        }

                        $itemModel->insert([
                            'transaction_id' => (int) $transId,
                            'name' => mb_substr($name, 0, 250),
                            'quantity' => $intQty,
                            'price' => $p,
                            'price_usd' => $pu,
                            'description' => mb_substr($itemDesc, 0, 250)
                        ]);

                        $itemsArray[] = [
                            'name' => $name,
                            'quantity' => $intQty,
                            'price' => $p,
                            'price_usd' => $pu,
                            'tax_type' => $taxType
                        ];
                    }
                } elseif ($totalBs > 0) {
                    $itemModel->insert([
                        'transaction_id' => (int) $transId,
                        'name' => mb_substr($merchant, 0, 250),
                        'quantity' => 1,
                        'price' => $totalBs,
                        'price_usd' => $totalUsd,
                        'description' => 'Factura escaneada'
                    ]);
                }

                // 3. Deduct from account (only if positive)
                if ($totalBs > 0) {
                    $newBalance = (float)$account['balance'] - $totalBs;
                    $accountModel->update($accountId, ['balance' => $newBalance]);
                }

                // 4. Save into ocr_invoices as approved
                $ocrInvoiceModel->insert([
                    'transaction_id' => (int) $transId,
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'merchant' => mb_substr($merchant, 0, 250),
                    'rif' => mb_substr($inv->rif ?? '', 0, 60),
                    'invoice_number' => mb_substr($invoiceNum, 0, 60),
                    'model_type' => mb_substr($inv->model_type ?? 'GENERIC_RECEIPT', 0, 60),
                    'model_label' => mb_substr($inv->model_label ?? 'Factura / Recibo General', 0, 120),
                    'invoice_date' => $invDate,
                    'invoice_time' => mb_substr($invTime, 0, 30),
                    'total_bs' => $totalBs,
                    'total_usd' => $totalUsd,
                    'exchange_rate' => $rate,
                    'subtotal' => (float) ($inv->subtotal ?? 0),
                    'exento' => (float) ($inv->exento ?? 0),
                    'base_imponible' => (float) ($inv->base_imponible ?? 0),
                    'iva_amount' => (float) ($inv->iva_amount ?? 0),
                    'iva_rate' => (float) ($inv->iva_rate ?? 16.0),
                    'igtf_amount' => (float) ($inv->igtf_amount ?? 0),
                    'payment_method' => !empty($inv->payment_method) ? mb_substr($inv->payment_method, 0, 60) : null,
                    'cashea_amount' => (float) ($inv->cashea ?? 0),
                    'owner' => $owner,
                    'items_json' => json_encode($itemsArray, JSON_UNESCAPED_UNICODE),
                    'raw_text' => $inv->raw_text ?? '',
                    'status' => 'approved',
                    'quick_scan' => 0,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'created_at' => $createdAt,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                try {
                    AuditLogModel::log(
                        'transactions',
                        'create',
                        $transId,
                        null,
                        ['merchant' => $merchant, 'amount' => $totalBs],
                        ['account_id' => $accountId, 'delta' => -$totalBs],
                        "Registro de gasto escaneado OCR ($merchant)"
                    );
                } catch (\Throwable $ignored) {}

                $savedCount++;
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                $dbErr = $db->error();
                $detail = !empty($dbErr['message']) ? ': ' . $dbErr['message'] : '';
                throw new \Exception('No se pudo confirmar la transacción en la base de datos' . $detail);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => "¡$savedCount factura(s) y sus gastos fueron registrados con éxito!",
                'count' => $savedCount
            ]);

        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Approve a pending review invoice.
     */
    public function approveInvoice($id)
    {
        $this->ensureTableExists();
        $ocrInvoiceModel = new OcrInvoiceModel();
        $inv = $ocrInvoiceModel->find($id);

        if (!$inv) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Factura no encontrada.']);
        }

        $ocrInvoiceModel->update($id, [
            'status' => 'approved',
            'reviewed_at' => date('Y-m-d H:i:s')
        ]);

        AuditLogModel::log(
            'ocr_invoices',
            'update',
            $id,
            ['status' => 'pending_review'],
            ['status' => 'approved'],
            null,
            "Factura #{$inv['id']} ({$inv['merchant']}) confirmada y aprobada."
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Factura confirmada y aprobada correctamente.',
            'pending_invoices' => $ocrInvoiceModel->getPendingWithAlerts(),
            'overdue_count' => $ocrInvoiceModel->countOverdue72h()
        ]);
    }

    /**
     * Update details of an existing pending review invoice.
     */
    public function updatePendingInvoice($id)
    {
        $this->ensureTableExists();
        $db = \Config\Database::connect();
        $ocrInvoiceModel = new OcrInvoiceModel();
        $accountModel = new AccountModel();
        $transModel = new TransactionModel();
        $itemModel = new TransactionItemModel();

        $inv = $ocrInvoiceModel->find($id);
        if (!$inv) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Factura no encontrada.']);
        }

        $json = $this->request->getJSON();
        if (!$json) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Datos inválidos.']);
        }

        $db->transStart();
        try {
            $newMerchant = trim($json->merchant ?? $inv['merchant']);
            $newRif = trim($json->rif ?? $inv['rif']);
            $newInvoiceNum = trim($json->invoice_number ?? $inv['invoice_number']);
            $newTotalBs = (float) ($json->total_bs ?? $inv['total_bs']);
            $newTotalUsd = (float) ($json->total_usd ?? $inv['total_usd']);
            $newAccountId = (int) ($json->account_id ?? $inv['account_id']);
            $newCategoryId = !empty($json->category_id) ? (int)$json->category_id : $inv['category_id'];
            $newDate = !empty($json->invoice_date) ? $json->invoice_date : $inv['invoice_date'];

            // Adjust bank account balances if amount or account changed
            $oldAccountId = (int) $inv['account_id'];
            $oldTotalBs = (float) $inv['total_bs'];

            if ($oldAccountId !== $newAccountId || abs($oldTotalBs - $newTotalBs) > 0.001) {
                // Revert old deduction
                $oldAcc = $accountModel->find($oldAccountId);
                if ($oldAcc) {
                    $accountModel->update($oldAccountId, ['balance' => (float)$oldAcc['balance'] + $oldTotalBs]);
                }
                // Apply new deduction
                $newAcc = $accountModel->find($newAccountId);
                if ($newAcc) {
                    $accountModel->update($newAccountId, ['balance' => (float)$newAcc['balance'] - $newTotalBs]);
                }
            }

            // Update attached transaction if exists
            if (!empty($inv['transaction_id'])) {
                $rawDate = !empty($newDate) ? trim($newDate) : date('Y-m-d');
                $rawTime = !empty($inv['invoice_time']) ? trim($inv['invoice_time']) : date('H:i:s');
                $parsedTs = strtotime("$rawDate $rawTime");
                $updatedCreatedAt = ($parsedTs !== false && $parsedTs > 0) ? date('Y-m-d H:i:s', $parsedTs) : date('Y-m-d H:i:s');

                $transModel->update($inv['transaction_id'], [
                    'account_id' => $newAccountId,
                    'category_id' => $newCategoryId,
                    'amount' => $newTotalBs,
                    'amount_usd' => $newTotalUsd,
                    'description' => mb_substr("Gasto Factura: $newMerchant" . ($newInvoiceNum ? " (Fac #$newInvoiceNum)" : ""), 0, 250),
                    'created_at' => $updatedCreatedAt
                ]);

                // Update items if provided
                if (!empty($json->items) && is_array($json->items)) {
                    $itemModel->where('transaction_id', $inv['transaction_id'])->delete();
                    foreach ($json->items as $it) {
                        $rawQty = (float) ($it->quantity ?? 1);
                        $intQty = max(1, (int) round($rawQty));
                        $taxType = !empty($it->tax_type) ? $it->tax_type : 'G';
                        $itemDesc = "IVA: $taxType";
                        if (abs($rawQty - $intQty) > 0.001) {
                            $itemDesc = trim("$itemDesc [Cant: " . number_format($rawQty, 2, ',', '.') . "]");
                        }

                        $itemModel->insert([
                            'transaction_id' => $inv['transaction_id'],
                            'name' => mb_substr(trim($it->name ?? 'Item'), 0, 250),
                            'quantity' => $intQty,
                            'price' => (float) ($it->price ?? 0),
                            'price_usd' => (float) ($it->price_usd ?? 0),
                            'description' => mb_substr($itemDesc, 0, 250)
                        ]);
                    }
                }
            }

            // Update ocr_invoices
            $itemsJson = !empty($json->items) ? json_encode($json->items, JSON_UNESCAPED_UNICODE) : $inv['items_json'];
            $ocrInvoiceModel->update($id, [
                'merchant' => mb_substr($newMerchant, 0, 250),
                'rif' => mb_substr($newRif, 0, 60),
                'invoice_number' => mb_substr($newInvoiceNum, 0, 60),
                'total_bs' => $newTotalBs,
                'total_usd' => $newTotalUsd,
                'account_id' => $newAccountId,
                'category_id' => $newCategoryId,
                'invoice_date' => $newDate,
                'items_json' => $itemsJson,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $db->transComplete();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Factura actualizada correctamente.',
                'pending_invoices' => $ocrInvoiceModel->getPendingWithAlerts(),
                'overdue_count' => $ocrInvoiceModel->countOverdue72h()
            ]);

        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Cancel an invoice and restore deducted funds to the bank account.
     */
    public function cancelInvoice($id)
    {
        $this->ensureTableExists();
        $db = \Config\Database::connect();
        $ocrInvoiceModel = new OcrInvoiceModel();
        $accountModel = new AccountModel();
        $transModel = new TransactionModel();

        $inv = $ocrInvoiceModel->find($id);
        if (!$inv) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Factura no encontrada.']);
        }

        if ($inv['status'] === 'cancelled') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Esta factura ya se encuentra cancelada.']);
        }

        $db->transStart();
        try {
            $totalBs = (float) $inv['total_bs'];
            $accountId = (int) $inv['account_id'];

            // 1. Revert bank account balance
            $account = $accountModel->find($accountId);
            if ($account && $totalBs > 0) {
                $revertedBalance = (float)$account['balance'] + $totalBs;
                $accountModel->update($accountId, ['balance' => $revertedBalance]);
            }

            // 2. Mark attached transaction as cancelled
            if (!empty($inv['transaction_id'])) {
                $existingTrans = $transModel->find($inv['transaction_id']);
                $prevDesc = $existingTrans ? $existingTrans['description'] : 'Gasto OCR';
                $transModel->update($inv['transaction_id'], [
                    'description' => mb_substr("[ANULADA] $prevDesc", 0, 250),
                    'amount' => 0,
                    'amount_usd' => 0
                ]);
            }

            // 3. Mark ocr_invoices as cancelled
            $ocrInvoiceModel->update($id, [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $accName = $account ? $account['name'] : 'la cuenta';
            AuditLogModel::log(
                'ocr_invoices',
                'cancel',
                $id,
                ['status' => $inv['status']],
                ['status' => 'cancelled'],
                ['account_id' => $accountId, 'delta' => +$totalBs],
                "Factura #$id ({$inv['merchant']}) cancelada. Se devolvió Bs. $totalBs a $accName."
            );

            $db->transComplete();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => "Factura anulada. Se han reintegrado Bs. " . number_format($totalBs, 2, ',', '.') . " a la cuenta " . ($account['name'] ?? ''),
                'pending_invoices' => $ocrInvoiceModel->getPendingWithAlerts(),
                'overdue_count' => $ocrInvoiceModel->countOverdue72h(),
                'history_invoices' => $ocrInvoiceModel->getHistory()
            ]);

        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get pending review invoices with 72h alert indicator.
     */
    public function getPending()
    {
        $this->ensureTableExists();
        $ocrInvoiceModel = new OcrInvoiceModel();
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $ocrInvoiceModel->getPendingWithAlerts(),
            'overdue_count' => $ocrInvoiceModel->countOverdue72h()
        ]);
    }

    /**
     * Get historical scanned invoices with filters.
     */
    public function history()
    {
        $this->ensureTableExists();
        $ocrInvoiceModel = new OcrInvoiceModel();

        $filters = [
            'status' => $this->request->getGet('status') ?: 'all',
            'merchant' => $this->request->getGet('merchant') ?: '',
            'date_from' => $this->request->getGet('date_from') ?: '',
            'date_to' => $this->request->getGet('date_to') ?: '',
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $ocrInvoiceModel->getHistory($filters)
        ]);
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

    /**
     * Ensure ocr_invoices table exists in database.
     */
    protected function ensureTableExists()
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('ocr_invoices')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'transaction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'account_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'merchant' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'rif' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'model_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'GENERIC_RECEIPT'],
                'model_label' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => 'Factura / Recibo General'],
                'invoice_date' => ['type' => 'DATE', 'null' => true],
                'invoice_time' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'total_bs' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'total_usd' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'exchange_rate' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 50.0000],
                'subtotal' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'exento' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'base_imponible' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'iva_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'iva_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 16.00],
                'igtf_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'payment_method' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'cashea_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
                'owner' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'Negocio'],
                'items_json' => ['type' => 'TEXT', 'null' => true],
                'raw_text' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'approved'],
                'quick_scan' => ['type' => 'INT', 'constraint' => 1, 'default' => 0],
                'reviewed_at' => ['type' => $db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
                'created_at' => ['type' => $db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
                'updated_at' => ['type' => $db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->addKey('status');
            $forge->addKey('account_id');
            $forge->createTable('ocr_invoices', true);
        }
    }
}
