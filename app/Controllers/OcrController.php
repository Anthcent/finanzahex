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

        // Determine target bank account
        if ($targetAccountId <= 0) {
            $defaultAcc = $accountModel->where('status', 'active')->orderBy('id', 'ASC')->first();
            if (!$defaultAcc) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No hay cuentas bancarias activas disponibles para debitar el gasto.'
                ]);
            }
            $targetAccountId = (int) $defaultAcc['id'];
        }

        // Determine default category
        if ($targetCategoryId <= 0) {
            $defaultCat = $categoryModel->orderBy('id', 'ASC')->first();
            $targetCategoryId = $defaultCat ? (int) $defaultCat['id'] : null;
        }

        $parser = new InvoiceParserService();
        $savedInvoices = [];

        $db->transStart();

        try {
            $account = $accountModel->find($targetAccountId);
            if (!$account) {
                throw new \Exception('La cuenta bancaria seleccionada no existe.');
            }

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
                    $totalBs = (float) ($inv['total_bs'] ?? 0);
                    $totalUsd = (float) ($inv['total_usd'] ?? 0);
                    $rate = (float) ($inv['exchange_rate'] ?? $exchangeRate);
                    $merchant = trim($inv['merchant'] ?? 'Gasto Factura');
                    $invoiceNum = trim($inv['invoice_number'] ?? '');
                    $invDate = !empty($inv['date']) ? $inv['date'] : date('Y-m-d');
                    $invTime = !empty($inv['time']) ? $inv['time'] : date('H:i');
                    $createdAt = "$invDate $invTime:00";

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
                        'description' => $desc,
                        'created_at' => $createdAt
                    ]);

                    // 2. Insert line items
                    $itemsList = !empty($inv['items']) && is_array($inv['items']) ? $inv['items'] : [];
                    if (!empty($itemsList)) {
                        foreach ($itemsList as $it) {
                            $itemModel->insert([
                                'transaction_id' => $transId,
                                'name' => trim($it['name'] ?? 'Producto'),
                                'quantity' => (float) ($it['quantity'] ?? 1),
                                'price' => (float) ($it['price'] ?? 0),
                                'price_usd' => (float) ($it['price_usd'] ?? 0),
                                'description' => !empty($it['tax_type']) ? "IVA: {$it['tax_type']}" : null
                            ]);
                        }
                    } else if ($totalBs > 0) {
                        $itemModel->insert([
                            'transaction_id' => $transId,
                            'name' => $merchant,
                            'quantity' => 1,
                            'price' => $totalBs,
                            'price_usd' => $totalUsd,
                            'description' => 'Factura rápida escaneada'
                        ]);
                    }

                    // 3. Deduct from bank account balance
                    $currentBalance = (float) $accountModel->find($targetAccountId)['balance'];
                    $accountModel->update($targetAccountId, ['balance' => $currentBalance - $totalBs]);

                    // 4. Record in ocr_invoices as pending_review
                    $ocrInvoiceId = $ocrInvoiceModel->insert([
                        'transaction_id' => $transId,
                        'account_id' => $targetAccountId,
                        'category_id' => $targetCategoryId,
                        'merchant' => $merchant,
                        'rif' => $inv['rif'] ?? '',
                        'invoice_number' => $invoiceNum,
                        'model_type' => $inv['model_type'] ?? 'GENERIC_RECEIPT',
                        'model_label' => $inv['model_label'] ?? 'Factura / Recibo General',
                        'invoice_date' => $invDate,
                        'invoice_time' => $invTime,
                        'total_bs' => $totalBs,
                        'total_usd' => $totalUsd,
                        'exchange_rate' => $rate,
                        'subtotal' => (float) ($inv['subtotal'] ?? 0),
                        'exento' => (float) ($inv['exento'] ?? 0),
                        'base_imponible' => (float) ($inv['base_imponible'] ?? 0),
                        'iva_amount' => (float) ($inv['iva_amount'] ?? 0),
                        'iva_rate' => (float) ($inv['iva_rate'] ?? 16.0),
                        'igtf_amount' => (float) ($inv['igtf_amount'] ?? 0),
                        'payment_method' => $inv['payment_method'] ?? null,
                        'cashea_amount' => (float) ($inv['cashea'] ?? 0),
                        'owner' => $owner,
                        'items_json' => json_encode($itemsList, JSON_UNESCAPED_UNICODE),
                        'raw_text' => $inv['raw_text'] ?? '',
                        'status' => 'pending_review',
                        'quick_scan' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    AuditLogModel::log(
                        'ocr_invoices',
                        'create',
                        $ocrInvoiceId,
                        null,
                        ['merchant' => $merchant, 'total_bs' => $totalBs, 'quick_scan' => 1],
                        ['account_id' => $targetAccountId, 'delta' => -$totalBs],
                        "Carga Rápida OCR: $merchant ($totalBs Bs) descontado de {$account['name']} [Pendiente por revisar]"
                    );

                    $savedInvoices[] = [
                        'id' => $ocrInvoiceId,
                        'merchant' => $merchant,
                        'total_bs' => $totalBs,
                        'total_usd' => $totalUsd,
                        'invoice_number' => $invoiceNum
                    ];
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error al guardar la factura en la base de datos.');
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => '¡Factura registrada automáticamente en Carga Rápida y descontada de la cuenta! Ha quedado en la lista de Pendientes por Revisar.',
                'saved_count' => count($savedInvoices),
                'pending_invoices' => $ocrInvoiceModel->getPendingWithAlerts(),
                'overdue_count' => $ocrInvoiceModel->countOverdue72h()
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
        $ocrInvoiceModel = new OcrInvoiceModel();
        $db = \Config\Database::connect();

        $db->transStart();

        try {
            $savedCount = 0;

            foreach ($json->invoices as $inv) {
                $accountId = (int) ($inv->account_id ?? 0);
                $categoryId = !empty($inv->category_id) ? (int) $inv->category_id : null;
                $merchant = trim($inv->merchant ?? 'Gasto');
                $invoiceNum = trim($inv->invoice_number ?? '');
                $invDate = !empty($inv->date) ? $inv->date : date('Y-m-d');
                $invTime = !empty($inv->time) ? $inv->time : date('H:i');
                $createdAt = "$invDate $invTime:00";
                $totalBs = (float) ($inv->total_bs ?? 0);
                $totalUsd = (float) ($inv->total_usd ?? 0);
                $rate = (float) ($inv->exchange_rate ?? 50.0);
                $owner = !empty($inv->owner) && in_array($inv->owner, ['Personal', 'Negocio']) ? $inv->owner : 'Negocio';

                if ($accountId <= 0) {
                    throw new \Exception("Debe seleccionar una cuenta bancaria para la factura de $merchant.");
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
                    'description' => $description,
                    'created_at' => $createdAt
                ]);

                // 2. Insert Items
                $itemsArray = [];
                if (!empty($inv->items) && is_array($inv->items)) {
                    foreach ($inv->items as $it) {
                        $p = (float) ($it->price ?? 0);
                        $pu = (float) ($it->price_usd ?? 0);
                        $qty = (float) ($it->quantity ?? 1);
                        $name = trim($it->name ?? 'Item');
                        $taxType = !empty($it->tax_type) ? $it->tax_type : 'G';

                        $itemModel->insert([
                            'transaction_id' => $transId,
                            'name' => $name,
                            'quantity' => $qty,
                            'price' => $p,
                            'price_usd' => $pu,
                            'description' => "IVA: $taxType"
                        ]);

                        $itemsArray[] = [
                            'name' => $name,
                            'quantity' => $qty,
                            'price' => $p,
                            'price_usd' => $pu,
                            'tax_type' => $taxType
                        ];
                    }
                }

                // 3. Deduct from account
                $newBalance = (float)$account['balance'] - $totalBs;
                $accountModel->update($accountId, ['balance' => $newBalance]);

                // 4. Save into ocr_invoices as approved
                $ocrInvoiceModel->insert([
                    'transaction_id' => $transId,
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'merchant' => $merchant,
                    'rif' => $inv->rif ?? '',
                    'invoice_number' => $invoiceNum,
                    'model_type' => $inv->model_type ?? 'GENERIC_RECEIPT',
                    'model_label' => $inv->model_label ?? 'Factura / Recibo General',
                    'invoice_date' => $invDate,
                    'invoice_time' => $invTime,
                    'total_bs' => $totalBs,
                    'total_usd' => $totalUsd,
                    'exchange_rate' => $rate,
                    'subtotal' => (float) ($inv->subtotal ?? 0),
                    'exento' => (float) ($inv->exento ?? 0),
                    'base_imponible' => (float) ($inv->base_imponible ?? 0),
                    'iva_amount' => (float) ($inv->iva_amount ?? 0),
                    'iva_rate' => (float) ($inv->iva_rate ?? 16.0),
                    'igtf_amount' => (float) ($inv->igtf_amount ?? 0),
                    'payment_method' => $inv->payment_method ?? null,
                    'cashea_amount' => (float) ($inv->cashea ?? 0),
                    'owner' => $owner,
                    'items_json' => json_encode($itemsArray, JSON_UNESCAPED_UNICODE),
                    'raw_text' => $inv->raw_text ?? '',
                    'status' => 'approved',
                    'quick_scan' => 0,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                AuditLogModel::log(
                    'transactions',
                    'create',
                    $transId,
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
                $transModel->update($inv['transaction_id'], [
                    'account_id' => $newAccountId,
                    'category_id' => $newCategoryId,
                    'amount' => $newTotalBs,
                    'amount_usd' => $newTotalUsd,
                    'description' => "Gasto Factura: $newMerchant" . ($newInvoiceNum ? " (Fac #$newInvoiceNum)" : ""),
                    'created_at' => "$newDate " . ($inv['invoice_time'] ?: '12:00') . ":00"
                ]);

                // Update items if provided
                if (!empty($json->items) && is_array($json->items)) {
                    $itemModel->where('transaction_id', $inv['transaction_id'])->delete();
                    foreach ($json->items as $it) {
                        $itemModel->insert([
                            'transaction_id' => $inv['transaction_id'],
                            'name' => trim($it->name ?? 'Item'),
                            'quantity' => (float) ($it->quantity ?? 1),
                            'price' => (float) ($it->price ?? 0),
                            'price_usd' => (float) ($it->price_usd ?? 0),
                            'description' => !empty($it->tax_type) ? "IVA: {$it->tax_type}" : null
                        ]);
                    }
                }
            }

            // Update ocr_invoices
            $itemsJson = !empty($json->items) ? json_encode($json->items, JSON_UNESCAPED_UNICODE) : $inv['items_json'];
            $ocrInvoiceModel->update($id, [
                'merchant' => $newMerchant,
                'rif' => $newRif,
                'invoice_number' => $newInvoiceNum,
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

        } catch (\Exception $e) {
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

            // 2. Mark attached transaction as cancelled or delete
            if (!empty($inv['transaction_id'])) {
                $transModel->update($inv['transaction_id'], [
                    'description' => "[ANULADA] " . $transModel->find($inv['transaction_id'])['description'],
                    'amount' => 0,
                    'amount_usd' => 0
                ]);
            }

            // 3. Mark ocr_invoices as cancelled
            $ocrInvoiceModel->update($id, [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            AuditLogModel::log(
                'ocr_invoices',
                'cancel',
                $id,
                ['status' => $inv['status']],
                ['status' => 'cancelled'],
                ['account_id' => $accountId, 'delta' => +$totalBs],
                "Factura #$id ({$inv['merchant']}) cancelada. Se devolvió Bs. $totalBs a {$account['name']}."
            );

            $db->transComplete();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => "Factura anulada. Se han reintegrado Bs. " . number_format($totalBs, 2, ',', '.') . " a la cuenta " . ($account['name'] ?? ''),
                'pending_invoices' => $ocrInvoiceModel->getPendingWithAlerts(),
                'overdue_count' => $ocrInvoiceModel->countOverdue72h(),
                'history_invoices' => $ocrInvoiceModel->getHistory()
            ]);

        } catch (\Exception $e) {
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
