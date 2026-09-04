<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

class AuditLogController extends BaseController
{
    public function index()
    {
        return view('audit/index');
    }

    public function fetch()
    {
        $json = $this->request->getJSON();
        $filters = $json ? (array)$json : [];

        $model = new AuditLogModel();
        $logs = $model->getFiltered($filters);

        // Decode JSON fields for the frontend
        foreach ($logs as &$log) {
            $log['data_before'] = $log['data_before'] ? json_decode($log['data_before'], true) : null;
            $log['data_after']  = $log['data_after'] ? json_decode($log['data_after'], true) : null;
            $log['impact']      = $log['impact'] ? json_decode($log['impact'], true) : null;
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $logs]);
    }

    public function chat()
    {
        $json = $this->request->getJSON();
        $query = $json->query ?? '';
        $apiKey = $json->apiKey ?? '';
        $history = $json->history ?? []; // Array of {role, content}
        $focusedLog = $json->focusedLog ?? null; // Specific log details if selected

        if (empty($query) || empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Se requiere consulta y API key'
            ]);
        }

        // Get general audit context
        $context = $this->getAuditContext();

        // Call Gemini API with enhanced context
        $response = $this->callGeminiForAudit($query, $history, $focusedLog, $context, $apiKey);

        return $this->response->setJSON([
            'status' => 'success',
            'response' => $response
        ]);
    }

    private function getAuditContext()
    {
        $db = \Config\Database::connect();

        // Recent audit logs (last 50 for context)
        $auditLogs = $db->table('audit_logs')
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        foreach ($auditLogs as &$log) {
            // Keep it lighter for tokens, mainly need action/module/note
            $log['impact'] = $log['impact'] ? json_decode($log['impact'], true) : null;
            // Only decode before/after if essential, otherwise string is fine or omit
            unset($log['data_before'], $log['data_after']); 
        }

        // Current account balances - Essential
        $accounts = $db->table('accounts')
            ->select('id, name, balance, currency')
            ->get()
            ->getResultArray();

        return [
            'recent_logs' => $auditLogs,
            'accounts'    => $accounts,
        ];
    }

    private function callGeminiForAudit($query, $history, $focusedLog, $context, $apiKey)
    {
        // Construct the prompt with conversation history context
        $prompt = "ERES 'AUDITOR AI', un asistente experto del sistema FinazaPersonal.
        
        TUS OBJETIVOS:
        1.  **Entender el Contexto**: Si el usuario pregunta '¿qué pasó aquí?' o 'reviértelo', se refiere al LOG ENFOQUE (si existe).
        2.  **Explicar Reversiones**:
            - **Transacciones/Gastos/Ingresos**: Dile que vaya a 'Historial' y elimine la transacción. Esto restaura el saldo automáticamente.
            - **Ventas**: Dile que vaya a 'Ventas -> Historial' y elimine la venta. Esto devuelve el stock y resta el dinero.
            - **Pagos de Deudas**: Dile que elimine el pago específico en el detalle de la orden.
            - **Transferencias**: No se pueden 'deshacer' con un clic. Sugiere crear una transferencia inversa manual.
        3.  **Ser Conversacional**: No parezcas un robot. Habla como un colega contador. 'Claro, revisemos eso', 'Ojo con este detalle'.
        
        LOG ENFOQUE (El usuario está viendo esto ahora mismo):
        " . ($focusedLog ? json_encode($focusedLog, JSON_UNESCAPED_UNICODE) : "Ninguno seleccionado") . "

        DATOS DEL SISTEMA (Contexto General):
        " . json_encode($context, JSON_UNESCAPED_UNICODE) . "

        HISTORIAL DE CHAT:
        ";

        // Add last 5 messages for context
        $recentHistory = array_slice($history, -5); 
        foreach ($recentHistory as $msg) {
            $prompt .= strtoupper($msg['role']) . ": " . $msg['content'] . "\n";
        }
        
        $prompt .= "USER: " . $query;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1024,
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60, // Increased timeout
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
             $error_msg = curl_error($ch);
             curl_close($ch);
             return ['type' => 'error', 'message' => 'Error de conexión (CURL): ' . $error_msg];
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'type' => 'error',
                'message' => 'Error de API (HTTP ' . $httpCode . '): ' . $response
            ];
        }

        $parsed = json_decode($response, true);
        $text = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? 'Sin respuesta (Estructura inesperada)';

        return [
            'type' => 'text',
            'content' => $text
        ];
    }
}
