<?php

namespace App\Controllers;

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
        
        if (empty($query) || empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Query and API key are required'
            ]);
        }
        
        // Get financial data context
        $context = $this->getFinancialContext();
        
        // Call Gemini API
        $aiResponse = $this->callGeminiAPI($query, $context, $apiKey);
        
        return $this->response->setJSON([
            'status' => 'success',
            'response' => $aiResponse
        ]);
    }
    
    private function getFinancialContext()
    {
        $db = \Config\Database::connect();
        
        // Get recent transactions
        $transactions = $db->table('transactions t')
            ->select('t.*, c.name as category_name, a.name as account_name')
            ->join('categories c', 'c.id = t.category_id', 'left')
            ->join('accounts a', 'a.id = t.account_id', 'left')
            ->orderBy('t.created_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();
        
        // Get accounts summary
        $accounts = $db->table('accounts')
            ->select('name, type, balance')
            ->where('status', 'active')
            ->get()
            ->getResultArray();
        
        // Get categories
        $categories = $db->table('categories')
            ->select('name, type')
            ->get()
            ->getResultArray();
        
        // Get recent sales
        $sales = $db->table('sales')
            ->select('*')
            ->orderBy('date', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        // Get debts (partial sales)
        $debts = $db->table('sales')
            ->select('*')
            ->where('status', 'partial')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();
        
        return [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
            'sales' => $sales,
            'debts' => $debts
        ];
    }
    
    private function callGeminiAPI($query, $context, $apiKey)
    {
        // ... (Debug info init) ...
        $debugInfo = [];

        // Models to try
        $models = [
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash',
            'gemini-2.0-flash-exp',
            'gemini-2.5-flash',
            'gemini-1.5-flash'
        ];
        

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

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt],
                        ['text' => "Consulta del usuario: " . $query]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 2048
            ]
        ];
        
        // Loop through models until one works
        foreach ($models as $model) {
            // Try both v1beta and v1
            $versions = ['v1beta', 'v1'];
            
            foreach ($versions as $version) {
                 $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . $apiKey;
                 
                 $ch = curl_init($url);
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($ch, CURLOPT_POST, true);
                 curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                 
                 $response = curl_exec($ch);
                 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                 $curlError = curl_error($ch);
                 curl_close($ch);
                 
                 if ($httpCode === 200) {
                     return $this->processResponse($response);
                 }
                 
                 $debugInfo[] = "Model: $model ($version) - Status: $httpCode";
                 
                 if ($httpCode === 429) {
                     sleep(1); // Brief pause on rate limit
                 }
            }
        }
        
        // Error handling...
        $availableModels = $this->listAvailableModels($apiKey);
        $errorDetails = implode("\n", array_slice($debugInfo, 0, 3));
        
        return [
            'type' => 'error',
            'message' => "❌ Fallo de Conexión\n\nModelos disponibles: [$availableModels]\n\nDetalles:\n$errorDetails",
            'details' => implode(" | ", $debugInfo),
            'available_models' => $availableModels
        ];
    }

    private function listAvailableModels($apiKey) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        if (isset($data['models'])) {
            $names = array_map(function($m) { return $m['name']; }, $data['models']);
            return implode(", ", $names);
        }
        return "Error listing models";
    }

    private function processResponse($response) {
        $data = json_decode($response, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $aiText = $data['candidates'][0]['content']['parts'][0]['text'];
            
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
            
            // Fallback: Return raw text but warn about parsing failure
            return [
                'type' => 'text',
                'content' => $aiText,
                'debug_parsing_error' => json_last_error_msg()
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
        // Ensure 'data' exists
        if (!isset($parsed['data'])) {
            $parsed['data'] = [];
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
