<?php

namespace App\Controllers;

use App\Libraries\GeminiClient;
use App\Libraries\PrintingPaymentCalculator;
use App\Libraries\TransactionValue;
use App\Models\AuditLogModel;

class ReconciliationController extends BaseController
{
    public function index()
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        return view('reconciliation/index', [
            'accounts' => $db->table('accounts')->where('status', 'active')->orderBy('name')->get()->getResultArray(),
            'categories' => $db->table('categories')->orderBy('name')->get()->getResultArray(),
            'batches' => $this->batchRows($db),
        ]);
    }

    public function scan()
    {
        $this->ensureTables();
        $payload = $this->request->getJSON(true) ?? [];
        $type = ($payload['import_type'] ?? '') === 'payment_capture' ? 'payment_capture' : 'bank_statement';
        $accountId = (int) ($payload['account_id'] ?? 0);
        $files = is_array($payload['files'] ?? null) ? $payload['files'] : [];
        if (!$files) return $this->failJson('Selecciona al menos un archivo.', 422);
        if ($accountId <= 0) return $this->failJson('Selecciona la cuenta bancaria relacionada.', 422);

        $rows = [];
        $sourceNames = [];
        foreach ($files as $file) {
            $name = trim((string) ($file['name'] ?? 'Archivo'));
            $sourceNames[] = $name;
            $mime = strtolower(trim((string) ($file['mime'] ?? 'application/octet-stream')));
            $dataUrl = (string) ($file['data'] ?? '');
            if ($dataUrl === '') continue;
            if (str_contains($mime, 'csv') || str_ends_with(strtolower($name), '.csv')) {
                $rows = array_merge($rows, $this->parseCsvDataUrl($dataUrl));
            } else {
                $parsed = $this->parseWithGemini($dataUrl, $mime, $type, trim((string) ($payload['api_key'] ?? '')));
                if (!$parsed['ok']) return $this->failJson($parsed['message'], 422);
                $rows = array_merge($rows, $parsed['rows']);
            }
        }
        if (!$rows) return $this->failJson('No se detectaron movimientos válidos. Verifica que monto y fecha sean legibles.', 422);

        // Images and PDFs are transient inputs. Release their base64 payloads before
        // writing anything; only the structured fields extracted above are persisted.
        unset($payload['files'], $files, $dataUrl);

        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalized = $this->normalizeRow($row, $accountId, $type);
            if ((float) $normalized['amount'] > 0) {
                $normalizedRows[] = [$normalized, $row];
            }
        }
        if (!$normalizedRows) return $this->failJson('No se detectó ningún monto mayor que cero.', 422);

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->transBegin();
        try {
            $db->table('financial_import_batches')->insert([
                'import_type' => $type, 'source_name' => implode(', ', $sourceNames),
                'account_id' => $accountId, 'status' => 'pending', 'item_count' => count($normalizedRows),
                'raw_json' => json_encode($rows, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now,
            ]);
            $batchId = (int) $db->insertID();
            $ids = [];
            foreach ($normalizedRows as [$normalized, $row]) {
                $match = $this->classify($db, $normalized, $type, $batchId);
                $normalized = array_merge($normalized, $match, [
                    'batch_id' => $batchId, 'created_at' => $now, 'updated_at' => $now,
                    'meta_json' => json_encode($row, JSON_UNESCAPED_UNICODE),
                ]);
                $db->table('financial_import_items')->insert($normalized);
                $ids[] = (int) $db->insertID();
            }
            $this->pairInternalTransfers($db, $batchId);
            if ($db->transStatus() === false) throw new \RuntimeException('No se pudo guardar la importación.');
            $db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'batch_id' => $batchId, 'data' => $this->items($db, $batchId)]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->failJson($e->getMessage(), 500);
        }
    }

    public function batches()
    {
        $this->ensureTables();
        return $this->response->setJSON(['status' => 'success', 'data' => $this->batchRows(\Config\Database::connect())]);
    }

    public function items($db = null, ?int $batchId = null)
    {
        $this->ensureTables();
        $db ??= \Config\Database::connect();
        $batchId ??= (int) $this->request->getGet('batch_id');
        $builder = $db->table('financial_import_items i')
            ->select('i.*, a.name account_name, po.customer_name, po.total_bs, po.total_usd, po.paid_bs, po.paid_usd')
            ->join('accounts a', 'a.id=i.account_id', 'left')
            ->join('print_orders po', 'po.id=i.matched_order_id', 'left')
            ->orderBy('i.movement_date', 'DESC')->orderBy('i.id', 'DESC');
        if ($batchId) $builder->where('i.batch_id', $batchId);
        $data = $builder->get()->getResultArray();
        if (func_num_args() > 0) return $data;
        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    public function updateItem(int $id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $allowed = ['account_id','direction','currency','amount','exchange_rate','movement_date','reference','bank','counterparty','description','suggested_action','matched_order_id'];
        $data = array_intersect_key($payload, array_flip($allowed));
        if (isset($data['amount'])) $data['amount'] = max(0, round((float) $data['amount'], 2));
        if (isset($data['movement_date'])) $data['movement_date'] = $this->dateTime($data['movement_date']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        \Config\Database::connect()->table('financial_import_items')->where('id', $id)->whereIn('status', ['pending','duplicate'])->update($data);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteBatch(int $id)
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        $batch = $db->table('financial_import_batches')->where('id', $id)->get()->getRowArray();
        if (!$batch) return $this->failJson('La importación ya no existe.', 404);

        $applied = $db->table('financial_import_items')
            ->where('batch_id', $id)
            ->where('status', 'applied')
            ->countAllResults();
        if ($applied > 0) {
            return $this->failJson('No se puede eliminar porque ya contiene movimientos aplicados. Puedes conservarla como historial.', 422);
        }

        $db->transBegin();
        try {
            // Delete explicitly as well as relying on the migration's cascade so this
            // remains safe on databases created before the foreign key was available.
            $db->table('financial_import_items')->where('batch_id', $id)->delete();
            $db->table('financial_import_batches')->where('id', $id)->delete();
            if ($db->transStatus() === false) throw new \RuntimeException('No se pudo eliminar la importación.');
            $db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Importación eliminada. Ya puedes escanear otro archivo.']);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->failJson($e->getMessage(), 500);
        }
    }

    public function apply(int $id)
    {
        $this->ensureTables();
        $payload = $this->request->getJSON(true) ?? [];
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $item = $db->query('SELECT * FROM financial_import_items WHERE id = ?' . ($db->DBDriver !== 'SQLite3' ? ' FOR UPDATE' : ''), [$id])->getRowArray();
            if (!$item) throw new \InvalidArgumentException('Movimiento no encontrado.');
            if ($item['status'] === 'applied') {
                $db->transRollback();
                return $this->response->setJSON(['status' => 'success', 'duplicate' => true, 'message' => 'Este movimiento ya fue aplicado.']);
            }
            if (!in_array($item['status'], ['pending','duplicate'], true)) throw new \InvalidArgumentException('Este movimiento ya no está pendiente.');
            $action = (string) ($payload['action'] ?? $item['suggested_action']);
            if ($action === 'ignore') {
                $db->table('financial_import_items')->where('id', $id)->update(['status' => 'ignored', 'updated_at' => date('Y-m-d H:i:s')]);
            } elseif ($action === 'payment') {
                $this->applyPrintingPayment($db, $item, (int) ($payload['order_id'] ?? $item['matched_order_id']));
            } elseif ($action === 'transfer') {
                $this->applyTransfer($db, $item, (int) ($payload['destination_account_id'] ?? 0));
            } else {
                $this->applyTransaction($db, $item, (int) ($payload['category_id'] ?? 0));
            }
            $this->refreshBatch($db, (int) $item['batch_id']);
            if ($db->transStatus() === false) throw new \RuntimeException('La base de datos rechazó la operación.');
            $db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Movimiento confirmado correctamente.']);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->failJson($e->getMessage(), $e instanceof \InvalidArgumentException ? 422 : 500);
        }
    }

    public function debts()
    {
        $db = \Config\Database::connect();
        $currentRate = $this->rate($db);
        $rows = $db->table('print_orders')->where('status !=', 'paid')->orderBy('created_at', 'DESC')->get()->getResultArray();
        return $this->response->setJSON(['status' => 'success', 'data' => array_map(function ($o) use ($currentRate) {
            $rate = $currentRate;
            $total = (float) $o['total_bs'];
            if ($total <= 0) $total = (float) $o['total_usd'] * $rate;
            $paid = (float) $o['paid_bs'] + (float) $o['paid_usd'] * $rate;
            $o['remaining_bs'] = max(0, round($total - $paid, 2));
            return $o;
        }, $rows)]);
    }

    private function applyPrintingPayment($db, array $item, int $orderId): void
    {
        if ($orderId <= 0) throw new \InvalidArgumentException('Selecciona la deuda que recibirá el abono.');
        $account = $db->table('accounts')->where('id', $item['account_id'])->get()->getRowArray();
        $order = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();
        if (!$account || !$order) throw new \InvalidArgumentException('La cuenta o deuda ya no existe.');
        if ($order['status'] === 'paid') throw new \InvalidArgumentException('La deuda seleccionada ya fue pagada.');
        $rate = max(0.01, (float) ($item['exchange_rate'] ?: ($order['exchange_rate'] ?? 0) ?: $this->rate($db)));
        $currency = strtoupper((string) $item['currency']);
        if ($currency === 'EUR') {
            $payment = PrintingPaymentCalculator::normalize($this->currencyValues($db, $item, $rate)[0], 0, $rate, 'bs');
        } else {
            $payment = PrintingPaymentCalculator::normalize($currency === 'USD' ? 0 : (float) $item['amount'], $currency === 'USD' ? (float) $item['amount'] : 0, $rate, strtolower($currency));
        }
        $updated = PrintingPaymentCalculator::apply($order, $payment, $rate);
        $categoryId = $this->categoryId($db, 'income');
        $native = strtoupper((string) ($account['currency'] ?? 'BS')) === 'USD' ? $payment['amount_usd'] + $payment['amount_bs'] / $rate : $payment['amount_bs'] + $payment['amount_usd'] * $rate;
        $token = 'recon_pay_' . $item['id'];
        $db->table('transactions')->insert([
            'account_id' => $account['id'], 'category_id' => $categoryId, 'print_order_id' => $orderId,
            'payment_request_id' => $token, 'amount' => $payment['amount_bs'], 'amount_usd' => $payment['amount_usd'],
            'exchange_rate' => $rate, 'type' => 'income', 'owner' => 'Negocio',
            'description' => 'Abono conciliado Impresiones #' . $orderId . ' - ' . $order['customer_name'] . ($item['reference'] ? ' Ref. ' . $item['reference'] : ''),
            'balance_before' => $account['balance'], 'balance_after' => (float) $account['balance'] + $native,
            'created_at' => $item['movement_date'], 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $transId = (int) $db->insertID();
        $db->table('accounts')->where('id', $account['id'])->update(['balance' => (float) $account['balance'] + $native]);
        $db->table('print_orders')->where('id', $orderId)->update(['paid_bs'=>$updated['paid_bs'],'paid_usd'=>$updated['paid_usd'],'status'=>$updated['status']]);
        $db->table('financial_import_items')->where('id', $item['id'])->update(['status'=>'applied','suggested_action'=>'payment','matched_order_id'=>$orderId,'transaction_id'=>$transId,'payment_request_id'=>$token,'updated_at'=>date('Y-m-d H:i:s')]);
        AuditLogModel::log('reconciliation', 'payment', $item['id'], null, $item, ['order_id'=>$orderId,'transaction_id'=>$transId], 'Abono conciliado desde comprobante');
    }

    private function applyTransaction($db, array $item, int $categoryId): void
    {
        $account = $db->table('accounts')->where('id', $item['account_id'])->get()->getRowArray();
        if (!$account) throw new \InvalidArgumentException('Selecciona una cuenta válida.');
        $type = $item['direction'] === 'debit' ? 'expense' : 'income';
        $categoryId = $categoryId > 0 ? $categoryId : $this->categoryId($db, $type);
        $rate = max(0.01, (float) ($item['exchange_rate'] ?: $this->rate($db)));
        [$amountBs, $amountUsd] = $this->currencyValues($db, $item, $rate);
        $native = strtoupper((string) ($account['currency'] ?? 'BS')) === 'USD' ? $amountUsd : ($amountBs ?: $amountUsd * $rate);
        $after = (float) $account['balance'] + ($type === 'income' ? $native : -$native);
        $db->table('transactions')->insert([
            'account_id'=>$account['id'],'category_id'=>$categoryId,'amount'=>$amountBs,'amount_usd'=>$amountUsd,'exchange_rate'=>$rate,
            'type'=>$type,'owner'=>'Negocio','description'=>'Importación bancaria: ' . ($item['description'] ?: $item['counterparty']) . ($item['reference'] ? ' Ref. '.$item['reference'] : ''),
            'balance_before'=>$account['balance'],'balance_after'=>$after,'created_at'=>$item['movement_date'],'updated_at'=>date('Y-m-d H:i:s'),
        ]);
        $transId = (int) $db->insertID();
        $db->table('accounts')->where('id', $account['id'])->update(['balance'=>$after]);
        $db->table('financial_import_items')->where('id', $item['id'])->update(['status'=>'applied','suggested_action'=>'transaction','transaction_id'=>$transId,'updated_at'=>date('Y-m-d H:i:s')]);
        AuditLogModel::log('reconciliation', 'import', $item['id'], null, $item, ['transaction_id'=>$transId], 'Movimiento bancario confirmado');
    }

    private function applyTransfer($db, array $item, int $otherAccountId): void
    {
        $statementAccount = $db->table('accounts')->where('id', $item['account_id'])->get()->getRowArray();
        $otherAccount = $db->table('accounts')->where('id', $otherAccountId)->get()->getRowArray();
        if ($item['direction'] === 'credit') { $source = $otherAccount; $dest = $statementAccount; }
        else { $source = $statementAccount; $dest = $otherAccount; }
        if (!$source || !$dest || (int) $source['id'] === (int) $dest['id']) throw new \InvalidArgumentException('Selecciona una cuenta destino diferente.');
        $rate = max(0.01, (float) ($item['exchange_rate'] ?: $this->rate($db)));
        [$bs, $usd] = $this->currencyValues($db, $item, $rate);
        $sourceNative = strtoupper((string) ($source['currency'] ?? 'BS')) === 'USD' ? $usd : ($bs ?: $usd*$rate);
        $destNative = strtoupper((string) ($dest['currency'] ?? 'BS')) === 'USD' ? $usd : ($bs ?: $usd*$rate);
        $group = 'recon_transfer_' . $item['id']; $category = $this->categoryId($db, 'transfer');
        $sourceAfter = (float)$source['balance'] - $sourceNative; $destAfter = (float)$dest['balance'] + $destNative;
        $common = ['category_id'=>$category,'amount'=>$bs,'amount_usd'=>$usd,'exchange_rate'=>$rate,'owner'=>'Negocio','transfer_group_id'=>$group,'created_at'=>$item['movement_date'],'updated_at'=>date('Y-m-d H:i:s')];
        $db->table('transactions')->insert(array_merge($common,['account_id'=>$source['id'],'type'=>'transfer_out','description'=>'Transferencia importada a '.$dest['name'],'balance_before'=>$source['balance'],'balance_after'=>$sourceAfter]));
        $transId=(int)$db->insertID();
        $db->table('transactions')->insert(array_merge($common,['account_id'=>$dest['id'],'type'=>'transfer_in','description'=>'Transferencia importada desde '.$source['name'],'balance_before'=>$dest['balance'],'balance_after'=>$destAfter]));
        $db->table('accounts')->where('id',$source['id'])->update(['balance'=>$sourceAfter]); $db->table('accounts')->where('id',$dest['id'])->update(['balance'=>$destAfter]);
        $db->table('financial_import_items')->where('id',$item['id'])->update(['status'=>'applied','suggested_action'=>'transfer','transaction_id'=>$transId,'updated_at'=>date('Y-m-d H:i:s')]);
        if (!empty($item['matched_item_id'])) {
            $db->table('financial_import_items')->where('id', $item['matched_item_id'])->whereIn('status', ['pending','duplicate'])->update(['status'=>'ignored','updated_at'=>date('Y-m-d H:i:s')]);
        }
        AuditLogModel::log('reconciliation','transfer',$item['id'],null,$item,['source_account_id'=>$source['id'],'destination_account_id'=>$dest['id']],'Transferencia interna conciliada');
    }

    private function classify($db, array $row, string $type, int $batchId): array
    {
        $duplicate = $db->table('financial_import_items')->where('duplicate_key', $row['duplicate_key'])->where('status !=', 'ignored')->countAllResults() > 0
            || $this->matchesLedgerTransaction($db, $row);
        if ($duplicate) return ['status'=>'duplicate','suggested_action'=>'ignore','matched_order_id'=>null,'matched_item_id'=>null];
        $description = mb_strtolower($row['description'] . ' ' . $row['counterparty']);
        if ($type === 'bank_statement' && (str_contains($description, 'transfer') || str_contains($description, 'traspaso') || str_contains($description, 'entre cuenta'))) {
            return ['status'=>'pending','suggested_action'=>'transfer','matched_order_id'=>null,'matched_item_id'=>null];
        }
        if ($type === 'payment_capture' || $row['direction'] === 'credit') {
            $orders = $db->table('print_orders')->where('status !=','paid')->orderBy('created_at','DESC')->limit(100)->get()->getResultArray();
            $best = null; $bestScore = 0;
            foreach ($orders as $order) {
                $rate = max(.01, (float)(($order['exchange_rate'] ?? 0) ?: $row['exchange_rate'] ?: $this->rate($db)));
                $remaining = max(0, (float)$order['total_bs'] + (float)$order['total_usd']*$rate - (float)$order['paid_bs'] - (float)$order['paid_usd']*$rate);
                $value = $this->currencyValues($db, $row, $rate)[0];
                $score = abs($remaining-$value) <= max(1, $remaining*.01) ? 70 : ($value <= $remaining+.5 ? 10 : 0);
                $needle = mb_strtolower($row['counterparty'].' '.$row['description']);
                if ($needle && str_contains($needle, mb_strtolower((string)$order['customer_name']))) $score += 30;
                if (!empty($row['movement_date']) && !empty($order['created_at']) && abs(strtotime($row['movement_date']) - strtotime($order['created_at'])) <= 2592000) $score += 5;
                if ($score > $bestScore) { $bestScore=$score; $best=$order; }
            }
            if ($best && $bestScore >= 30) return ['status'=>'pending','suggested_action'=>'payment','matched_order_id'=>(int)$best['id'],'matched_item_id'=>null];
        }
        return ['status'=>'pending','suggested_action'=>'transaction','matched_order_id'=>null,'matched_item_id'=>null];
    }

    private function pairInternalTransfers($db, int $batchId): void
    {
        $items=$db->table('financial_import_items')->where('batch_id',$batchId)->where('status','pending')->get()->getResultArray();
        foreach ($items as $a) foreach ($items as $b) {
            if ($a['id'] >= $b['id'] || $a['direction']===$b['direction'] || strtoupper($a['currency'])!==strtoupper($b['currency'])) continue;
            if (abs((float)$a['amount']-(float)$b['amount'])>.01 || abs(strtotime($a['movement_date'])-strtotime($b['movement_date']))>172800) continue;
            $db->table('financial_import_items')->whereIn('id',[$a['id'],$b['id']])->update(['suggested_action'=>'transfer']);
            $db->table('financial_import_items')->where('id',$a['id'])->update(['matched_item_id'=>$b['id']]);
            $db->table('financial_import_items')->where('id',$b['id'])->update(['matched_item_id'=>$a['id']]);
        }
    }

    private function normalizeRow(array $row, int $accountId, string $type): array
    {
        $direction = strtolower((string)($row['direction'] ?? ($type==='payment_capture'?'credit':'debit')));
        $direction = strtr($direction, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
        if (!in_array($direction,['credit','debit'],true)) $direction = preg_match('/credit|ingres|entrada|abono|deposit/', $direction) ? 'credit':'debit';
        $currency = strtoupper((string)($row['currency'] ?? 'BS')); if (!in_array($currency,['BS','USD','EUR'],true)) $currency='BS';
        $amount = abs($this->number($row['amount'] ?? $row['monto'] ?? 0));
        $reference=trim((string)($row['reference']??$row['referencia']??''));
        $date=$this->dateTime((string)($row['date']??$row['fecha']??date('Y-m-d H:i:s')));
        $identity = $reference !== '' ? $reference : (($row['counterparty'] ?? $row['payer'] ?? $row['titular'] ?? '') . '|' . ($row['description'] ?? $row['descripcion'] ?? ''));
        $key=hash('sha256',implode('|',[$accountId,$direction,$currency,number_format($amount,2,'.',''),substr($date,0,10),preg_replace('/\W+/','',mb_strtolower($identity))]));
        return ['account_id'=>$accountId,'direction'=>$direction,'currency'=>$currency,'amount'=>$amount,'exchange_rate'=>max(0,$this->number($row['exchange_rate']??0)),'movement_date'=>$date,'reference'=>mb_substr($reference,0,100),'bank'=>mb_substr(trim((string)($row['bank']??$row['banco']??'')),0,120),'counterparty'=>mb_substr(trim((string)($row['counterparty']??$row['payer']??$row['titular']??'')),0,180),'description'=>trim((string)($row['description']??$row['descripcion']??'')),'confidence'=>min(100,max(0,$this->number($row['confidence']??75))),'duplicate_key'=>$key];
    }

    private function parseWithGemini(string $dataUrl, string $mime, string $type, string $apiKey): array
    {
        if ($apiKey==='') return ['ok'=>false,'message'=>'Configura tu API key de Gemini para analizar imágenes o PDF.','rows'=>[]];
        if (!preg_match('#^data:([^;]+);base64,(.+)$#s',$dataUrl,$m)) return ['ok'=>false,'message'=>'El archivo recibido no es válido.','rows'=>[]];
        $prompt = $type==='payment_capture'
            ? 'Extrae del comprobante de pago: monto, moneda (BS/USD/EUR), fecha y hora, referencia, banco, pagador/titular y descripción. Es un ingreso (credit).'
            : 'Extrae TODOS los movimientos del estado de cuenta. Para cada uno indica credit si entra dinero o debit si sale, monto positivo, moneda, fecha/hora, referencia, banco, contraparte y descripción. No incluyas saldos como movimientos.';
        $schema='Devuelve exclusivamente JSON válido con esta forma: {"movements":[{"direction":"credit|debit","amount":0,"currency":"BS|USD|EUR","date":"YYYY-MM-DD HH:MM:SS","reference":"","bank":"","counterparty":"","description":"","confidence":0}]}. '.$prompt.' Si un dato no existe usa cadena vacía; no inventes referencias.';
        $result=(new GeminiClient())->generate($apiKey,['contents'=>[['parts'=>[['text'=>$schema],['inline_data'=>['mime_type'=>$m[1]?:$mime,'data'=>$m[2]]]]]],'generationConfig'=>['temperature'=>0.1,'responseMimeType'=>'application/json','maxOutputTokens'=>16384]]);
        if (!$result['ok']) return ['ok'=>false,'message'=>$result['message'],'rows'=>[]];
        $body=json_decode($result['body'],true); $text=$body['candidates'][0]['content']['parts'][0]['text']??''; $json=json_decode(trim(preg_replace('/^```(?:json)?|```$/m','',$text)),true);
        return ['ok'=>is_array($json['movements']??null),'message'=>'Gemini no devolvió movimientos estructurados.','rows'=>$json['movements']??[]];
    }

    private function parseCsvDataUrl(string $url): array
    {
        $raw=base64_decode(substr($url,strpos($url,',')+1),true); if ($raw===false) return [];
        $lines=preg_split('/\r\n|\n|\r/',trim($raw)); if (!$lines) return [];
        $delimiter=substr_count($lines[0],';')>substr_count($lines[0],',')?';':','; $headers=array_map(fn($v)=>$this->header($v),str_getcsv(array_shift($lines),$delimiter,'"','\\')); $rows=[];
        foreach($lines as $line){if(trim($line)==='')continue;$vals=str_getcsv($line,$delimiter,'"','\\');$r=[];foreach($headers as $i=>$h)$r[$h]=$vals[$i]??'';$credit=trim((string)($r['credit']??''));$debit=trim((string)($r['debit']??''));$amount=trim((string)($r['amount']??''));if($amount==='')$amount=$credit!==''?$credit:($debit!==''?$debit:0);$direction=$credit!==''?'credit':($debit!==''?'debit':($r['direction']??'debit'));$rows[]=['date'=>$r['date']??'','description'=>$r['description']??'','reference'=>$r['reference']??'','amount'=>$amount,'direction'=>$direction,'currency'=>$r['currency']??'BS','counterparty'=>$r['counterparty']??'','bank'=>$r['bank']??'','confidence'=>100];}
        return $rows;
    }

    private function header(string $v): string { $v=strtolower(trim($v)); $v=strtr($v,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']); foreach(['fecha'=>'date','monto'=>'amount','importe'=>'amount','credito'=>'credit','abono'=>'credit','debito'=>'debit','cargo'=>'debit','tipo'=>'direction','descripcion'=>'description','concepto'=>'description','referencia'=>'reference','moneda'=>'currency','contraparte'=>'counterparty','titular'=>'counterparty','banco'=>'bank'] as $from=>$to) if(str_contains($v,$from))return $to; return preg_replace('/\W+/','_',$v); }
    private function number($value): float { $v=preg_replace('/[^0-9,.-]/','',(string)$value); if(str_contains($v,',')&&str_contains($v,'.'))$v=strrpos($v,',')>strrpos($v,'.')?str_replace(['. ','.',','],['','','.'],$v):str_replace(',','',$v);elseif(str_contains($v,','))$v=str_replace(',','.',$v);return (float)$v; }
    private function dateTime(string $value): string { $value=trim($value);if(preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?#',$value,$m))return sprintf('%04d-%02d-%02d %02d:%02d:%02d',$m[3],$m[2],$m[1],$m[4]??0,$m[5]??0,$m[6]??0);$ts=strtotime($value);return date('Y-m-d H:i:s',$ts?:time()); }
    private function rate($db): float { return max(.01,(float)($db->table('settings')->where('key','bcv_usd_rate')->get()->getRowArray()['value']??1)); }
    private function matchesLedgerTransaction($db, array $item): bool { $day=substr((string)$item['movement_date'],0,10);$rows=$db->table('transactions')->where('account_id',$item['account_id'])->where('created_at >=',$day.' 00:00:00')->where('created_at <=',$day.' 23:59:59')->get()->getResultArray();$rate=max(.01,(float)($item['exchange_rate']?:$this->rate($db)));$account=$db->table('accounts')->where('id',$item['account_id'])->get()->getRowArray();$target=strtoupper((string)($account['currency']??'BS'))==='USD'?$this->currencyValues($db,$item,$rate)[1]:$this->currencyValues($db,$item,$rate)[0];foreach($rows as $row){$credit=in_array($row['type'],['income','return','transfer_in','exchange_in'],true);if(($item['direction']==='credit')!==$credit)continue;$native=strtoupper((string)($account['currency']??'BS'))==='USD'?TransactionValue::inDollars($row,$rate):TransactionValue::inBolivars($row,$rate);if(abs($native-$target)<=.01)return true;}return false; }
    private function currencyValues($db, array $item, float $usdRate): array { $currency=strtoupper((string)$item['currency']);$amount=(float)$item['amount'];if($currency==='USD')return [0.0,$amount];if($currency==='EUR'){$eur=max(.01,(float)($db->table('settings')->where('key','bcv_eur_rate')->get()->getRowArray()['value']??$usdRate));$bs=round($amount*$eur,2);return [$bs,round($bs/$usdRate,2)];}return [$amount,round($amount/$usdRate,2)]; }
    private function categoryId($db,string $type): int { $row=$db->table('categories')->where('type',$type)->orderBy('id')->get()->getRowArray() ?: $db->table('categories')->orderBy('id')->get()->getRowArray(); if(!$row)throw new \RuntimeException('Crea al menos una categoría antes de confirmar.');return (int)$row['id']; }
    private function refreshBatch($db,int $id): void { $pending=$db->table('financial_import_items')->where('batch_id',$id)->whereIn('status',['pending','duplicate'])->countAllResults();$db->table('financial_import_batches')->where('id',$id)->update(['status'=>$pending?'pending':'completed','updated_at'=>date('Y-m-d H:i:s')]); }
    private function batchRows($db): array { return $db->table('financial_import_batches b')->select("b.id, b.import_type, b.source_name, b.account_id, b.status, b.item_count, b.created_at, b.updated_at, a.name account_name, (SELECT COUNT(*) FROM financial_import_items pi WHERE pi.batch_id=b.id AND pi.status='pending') pending_count, (SELECT COUNT(*) FROM financial_import_items ai WHERE ai.batch_id=b.id AND ai.status='applied') applied_count, (SELECT COUNT(*) FROM financial_import_items di WHERE di.batch_id=b.id AND di.status='duplicate') duplicate_count, (SELECT COUNT(*) FROM financial_import_items ii WHERE ii.batch_id=b.id AND ii.status='ignored') ignored_count",false)->join('accounts a','a.id=b.account_id','left')->orderBy('b.created_at','DESC')->limit(40)->get()->getResultArray(); }
    private function failJson(string $message,int $code){return $this->response->setStatusCode($code)->setJSON(['status'=>'error','message'=>$message]);}
    private function ensureTables(): void { if(!\Config\Database::connect()->tableExists('financial_import_batches')){try{command('migrate',['--all']);}catch(\Throwable $e){log_message('error',$e->getMessage());}} }
}
