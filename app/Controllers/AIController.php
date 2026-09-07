<?php

namespace App\Controllers;

use App\Libraries\GeminiClient;

class AIController extends BaseController
{
    public function index()
    {
        return view('ai/index');
    }
    
    public function chat()
    {
        $json = $this->request->getJSON();
        $query = $json->query ?? '';
        $apiKey = $json->apiKey ?? '';
        $history = is_array($json->history ?? null) ? array_slice($json->history, -8) : [];
        
        if (empty($query) || empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Query and API key are required'
            ]);
        }
        
        // Get financial data context
        $context = $this->getFinancialContext();
        
        // Call Gemini API
        $aiResponse = $this->callGeminiAPI($query, $context, $apiKey, $history);
        
        return $this->response->setJSON([
            'status' => 'success',
            'response' => $aiResponse
        ]);
    }
    
    private function getFinancialContext()
    {
        $db = \Config\Database::connect();
        
        // Compact, relevant context. Keep payload bounded so the model can focus
        // on the answer instead of repeating raw records.
        $transactions = $db->table('transactions t')
            ->select('t.*, c.name as category_name, a.name as account_name')
            ->join('categories c', 'c.id = t.category_id', 'left')
            ->join('accounts a', 'a.id = t.account_id', 'left')
            ->orderBy('t.created_at', 'DESC')
            ->limit(60)
            ->get()
            ->getResultArray();
        
        // Get accounts summary
        $accounts = $db->table('accounts')
            ->select('name, type, balance, currency, tenure_type')
            ->where('status', 'active')
            ->get()
            ->getResultArray();
        
        $sales = $db->tableExists('sales') ? $db->table('sales')
            ->select('*')
            ->orderBy('date', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray() : [];

        // Get debts (partial sales)
        $debts = $db->tableExists('sales') ? $db->table('sales')
            ->select('*')
            ->where('status', 'partial')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray() : [];

        $printing = $db->tableExists('print_orders') ? $db->table('print_orders')
            ->select('id, customer_name, total_bs, total_usd, paid_bs, paid_usd, status, due_date, created_at')
            ->orderBy('created_at', 'DESC')->limit(30)->get()->getResultArray() : [];
        $pendingInvoices = $db->tableExists('ocr_invoices') ? $db->table('ocr_invoices')
            ->select('id, merchant, total_bs, total_usd, status, invoice_date, created_at')
            ->whereIn('status', ['pending', 'review'])->orderBy('created_at', 'DESC')->limit(20)->get()->getResultArray() : [];
        $currencyOperations = $db->tableExists('currency_operations') ? $db->table('currency_operations')
            ->select('id, operation_type, total_bs, amount_usd, effective_rate, status, operation_date')
            ->orderBy('operation_date', 'DESC')->limit(20)->get()->getResultArray() : [];

        $monthStart = date('Y-m-01 00:00:00');
        $monthTransactions = $db->table('transactions')->select('type, amount, amount_usd, exchange_rate')
            ->where('created_at >=', $monthStart)->get()->getResultArray();
        $monthly = ['income_bs' => 0.0, 'expense_bs' => 0.0, 'savings_bs' => 0.0, 'records' => count($monthTransactions)];
        foreach ($monthTransactions as $row) {
            $key = ($row['type'] ?? '') . '_bs';
            if (isset($monthly[$key])) $monthly[$key] += (float) ($row['amount'] ?? 0);
        }
        
        return [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'sales' => $sales,
            'debts' => $debts,
            'printing_orders' => $printing,
            'pending_ocr_invoices' => $pendingInvoices,
            'currency_operations' => $currencyOperations,
            'current_month' => $monthly,
            'generated_at' => date(DATE_ATOM),
        ];
    }
    
    private function callGeminiAPI($query, $context, $apiKey, array $history = [])
    {
        $systemPrompt = "Eres un asistente financiero experto con capacidades de análisis predictivo. Tienes acceso a DOS módulos principales:
1.  **Finanzas Personales/Negocio**: Transacciones de gastos e ingresos.
2.  **Gestión de Ventas**: Registro de ventas de mercancía y control de deudas (cuentas por cobrar).

Analiza los datos proporcionados y responde en formato JSON estructurado.

IMPORTANTE SOBRE COLORES:
- USA SOLO nombres de colores válidos de TailwindCSS: 'red', 'green', 'blue', 'purple', 'indigo', 'pink', 'teal', 'yellow', 'orange', 'cyan', 'rose', 'emerald', 'violet', 'fuchsia'.
- NO uses códigos hexadecimales.
- NO uses arrays para colores, solo strings.

IMPORTANTE SOBRE JSON:
- Tu respuesta debe ser ÚNICAMENTE el objeto JSON.
- NO agregues markdown, ni ```json, ni explicaciones antes o después.
- Asegúrate de que el JSON sea válido.

ESTRUCTURA DE RESPUESTA REQUERIDA:
{
  \"type\": \"summary|cards|table|list|comparison|progress|timeline|forecast\",
  \"title\": \"Título del análisis\",
  \"data\": [...],
  \"insights\": [\"insight 1\", \"insight 2\"]
}

Tipos de respuesta y sus datos específicos:

1. summary: {total, count, average, period}
2. cards: [{title, amount, description, color: 'blue'}]
3. table: {headers: ['Col1', 'Col2'], rows: [['Val1', 'Val2']]}
4. list: [{title, description, amount, color: 'indigo'}]
5. comparison: {period1: {label, value, description}, period2: {label, value, description}, difference, differencePercent}
6. progress: [{label, value, percentage, color: 'emerald'}]
7. timeline: [{date, title, description, amount, color: 'rose'}]
8. forecast: {current, projected, change, trend, breakdown: [{category, amount}]}

ELIGE SIEMPRE LA MEJOR VISUALIZACIÓN para los datos solicitados. Si preguntan por deudas, usa 'list' o 'table'. Si preguntan por ventas totales, usa 'summary' o 'cards'.

Datos disponibles:
- Transacciones (Gastos/Ingresos): " . count($context['transactions']) . " registros
- Cuentas: " . json_encode($context['accounts']) . "
- Ventas Recientes: " . count($context['sales']) . " registros
- Deudas Pendientes: " . count($context['debts']) . " registros

Muestra de Datos:
- Transacciones (últimas 20): " . json_encode(array_slice($context['transactions'], 0, 20)) . "
- Ventas (últimas 20): " . json_encode(array_slice($context['sales'], 0, 20)) . "
- Deudas (Todas): " . json_encode($context['debts']);

        $systemPrompt = $this->buildAssistantPrompt($query, $context, $history);
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json'
            ]
        ];

        $result = (new GeminiClient())->generate($apiKey, $payload);
        if ($result['ok']) return $this->processResponse($result['body']);
        $details = array_map(static fn(array $attempt): string => "{$attempt['model']} (HTTP {$attempt['status']})", $result['attempts'] ?? []);
        return [
            'type' => 'error',
            'message' => "❌ No se pudo consultar Gemini.\n\n" . ($result['message'] ?? 'Error desconocido.'),
            'details' => implode(' | ', $details),
        ];
    }

    private function buildAssistantPrompt(string $query, array $context, array $history): string
    {
        $conversation = [];
        foreach ($history as $message) {
            $role = ($message['role'] ?? '') === 'assistant' ? 'ASISTENTE' : 'USUARIO';
            $content = $message['content'] ?? '';
            if ($content === '' && is_array($message['data'] ?? null)) {
                $content = $message['data']['answer'] ?? $message['data']['title'] ?? '';
            }
            if ($content !== '') $conversation[] = $role . ': ' . mb_substr((string) $content, 0, 500);
        }

        $contract = [
            'type' => 'summary|cards|table|list|comparison|progress|timeline|forecast|text',
            'title' => 'Título breve',
            'answer' => 'Respuesta directa y conversacional, máximo 3 frases',
            'data' => 'Datos adecuados al tipo elegido',
            'insights' => ['Hallazgo concreto basado en datos'],
            'suggestions' => ['Pregunta breve que el usuario podría hacer después'],
            'actions' => [['label' => 'Ver registros', 'module' => 'history']],
        ];

        return "Eres el copiloto financiero de Finanzahex. Responde en español claro, con cifras de los datos suministrados. "
            . "Puedes analizar cuentas, gastos, ingresos, ahorro, ventas, deudas, órdenes de impresión, facturas OCR y operaciones en divisas. "
            . "No inventes valores ni afirmes que ejecutaste cambios. Si faltan datos, dilo. Distingue Bs de USD. "
            . "Devuelve EXCLUSIVAMENTE un objeto JSON completo, compacto y válido; nunca markdown ni JSON parcial. "
            . "Usa máximo 6 elementos por lista/tabla y máximo 4 insights. Contrato: "
            . json_encode($contract, JSON_UNESCAPED_UNICODE) . ". "
            . "Para data: summary={total,count,average,period}; cards=[{title,amount,description,color}]; "
            . "table={headers,rows}; list=[{title,description,amount,color}]; comparison={period1,period2,difference,differencePercent}; "
            . "progress=[{label,value,percentage,color}]; timeline=[{date,title,description,amount,color}]; "
            . "forecast={current,projected,change,trend,breakdown}. "
            . "Los únicos módulos permitidos en actions son history, metrics, accounts, printing, ocr y currency. "
            . "Conversación reciente:\n" . implode("\n", $conversation) . "\n"
            . "Datos actuales:\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) . "\n"
            . "Consulta actual: " . $query;
    }

    private function processResponse($response) {
        $data = json_decode($response, true);
        
        if (isset($data['candidates'][0]['content']['parts'])) {
            $aiText = implode('', array_map(static fn(array $part): string => (string) ($part['text'] ?? ''), $data['candidates'][0]['content']['parts']));
            
            // CLEANING STRATEGY
            
            // 1. Remove markdown code blocks if present (keep content)
            $aiText = preg_replace('/```(?:json)?/i', '', $aiText);
            
            // 2. Find the FIRST '{' and LAST '}' to extract proper JSON object
            $start = strpos($aiText, '{');
            $end = strrpos($aiText, '}');
            
            if ($start !== false && $end !== false && $end > $start) {
                $jsonCandidate = substr($aiText, $start, $end - $start + 1);
                
                $parsed = json_decode($jsonCandidate, true);
                if ($parsed && json_last_error() === JSON_ERROR_NONE) {
                    return $this->normalizeResponse($parsed);
                }
            }
            
            // 3. Last attempt: Direct decode
            $parsed = json_decode($aiText, true);
            if ($parsed) return $this->normalizeResponse($parsed);
            
            // Never expose broken/truncated JSON as if it were a useful answer.
            return [
                'type' => 'error',
                'message' => 'Gemini devolvió una respuesta incompleta. Intenta nuevamente; tu consulta no se perdió.',
                'details' => json_last_error_msg(),
            ];
        }
        
        return [
            'type' => 'error',
            'message' => 'Respuesta vacía del API',
            'raw' => $response
        ];
    }
    
    // Normalizes the AI response to match Frontend expectations
    private function normalizeResponse($parsed) {
        $allowedTypes = ['summary', 'cards', 'table', 'list', 'comparison', 'progress', 'timeline', 'forecast', 'text'];
        if (!in_array($parsed['type'] ?? '', $allowedTypes, true)) $parsed['type'] = 'text';
        $parsed['title'] = trim((string) ($parsed['title'] ?? 'Análisis financiero'));
        $parsed['answer'] = trim((string) ($parsed['answer'] ?? ''));
        $parsed['insights'] = array_slice(array_values(array_filter((array) ($parsed['insights'] ?? []), 'is_string')), 0, 4);
        $parsed['suggestions'] = array_slice(array_values(array_filter((array) ($parsed['suggestions'] ?? []), 'is_string')), 0, 4);
        $allowedModules = ['history', 'metrics', 'accounts', 'printing', 'ocr', 'currency'];
        $parsed['actions'] = array_values(array_filter((array) ($parsed['actions'] ?? []), static function ($action) use ($allowedModules): bool {
            return is_array($action) && in_array($action['module'] ?? '', $allowedModules, true) && !empty($action['label']);
        }));

        // Ensure 'data' exists
        if (!isset($parsed['data'])) {
            $parsed['data'] = [];
        }
        if (in_array($parsed['type'], ['cards', 'list', 'progress', 'timeline'], true) && !array_is_list($parsed['data'])) {
            $nestedKey = $parsed['type'] === 'cards' ? 'cards' : ($parsed['type'] === 'list' ? 'items' : $parsed['type']);
            $parsed['data'] = array_values((array) ($parsed['data'][$nestedKey] ?? []));
        }
        if ($parsed['type'] === 'table') {
            $parsed['data'] = [
                'headers' => array_values((array) ($parsed['data']['headers'] ?? [])),
                'rows' => array_values((array) ($parsed['data']['rows'] ?? [])),
            ];
        }
        if ($parsed['type'] === 'text' && $parsed['answer'] === '') {
            $parsed['answer'] = trim((string) ($parsed['content'] ?? 'Análisis completado.'));
        }
        
        // Handle 'comparison' type specific structure
        if (isset($parsed['type']) && $parsed['type'] === 'comparison') {
            // Check if fields are incorrectly at text root instead of data
            $fields = ['period1', 'period2', 'difference', 'differencePercent'];
            foreach ($fields as $field) {
                if (isset($parsed[$field]) && !isset($parsed['data'][$field])) {
                    $parsed['data'][$field] = $parsed[$field];
                    unset($parsed[$field]);
                }
            }
            // Ensure default structure to prevent JS errors
            if (!isset($parsed['data']['period1'])) $parsed['data']['period1'] = ['label' => 'A', 'value' => 0, 'description' => ''];
            if (!isset($parsed['data']['period2'])) $parsed['data']['period2'] = ['label' => 'B', 'value' => 0, 'description' => ''];
            if (!isset($parsed['data']['difference'])) $parsed['data']['difference'] = 0;
            if (!isset($parsed['data']['differencePercent'])) $parsed['data']['differencePercent'] = 0;
        }
        
        // Handle 'forecast' type
        if (isset($parsed['type']) && $parsed['type'] === 'forecast') {
            $fields = ['current', 'currentPeriod', 'projected', 'projectedPeriod', 'trend', 'change', 'breakdown'];
            foreach ($fields as $field) {
                if (isset($parsed[$field]) && !isset($parsed['data'][$field])) {
                    $parsed['data'][$field] = $parsed[$field];
                    unset($parsed[$field]);
                }
            }
        }
        
        return $parsed;
    }
    
    public function saveConversation()
    {
        $json = $this->request->getJSON();
        $title = $json->title ?? 'Conversación sin título';
        $messages = $json->messages ?? [];
        
        if (empty($messages)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No hay mensajes para guardar'
            ]);
        }
        
        $db = \Config\Database::connect();
        $builder = $db->table('ai_conversations');
        
        $data = [
            'title' => $title,
            'messages' => json_encode($messages)
        ];
        
        $builder->insert($data);
        
        return $this->response->setJSON([
            'status' => 'success',
            'id' => $db->insertID(),
            'message' => 'Conversación guardada'
        ]);
    }
    
    public function getConversations()
    {
        $db = \Config\Database::connect();
        $conversations = $db->table('ai_conversations')
            ->select('id, title, created_at, updated_at')
            ->orderBy('updated_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();
        
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $conversations
        ]);
    }
    
    public function loadConversation($id)
    {
        $db = \Config\Database::connect();
        $conversation = $db->table('ai_conversations')
            ->where('id', $id)
            ->get()
            ->getRowArray();
        
        if (!$conversation) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Conversación no encontrada'
            ]);
        }
        
        $conversation['messages'] = json_decode($conversation['messages'], true);
        
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $conversation
        ]);
    }
    
    public function deleteConversation($id)
    {
        $db = \Config\Database::connect();
        $db->table('ai_conversations')->delete(['id' => $id]);
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Conversación eliminada'
        ]);
    }
}
