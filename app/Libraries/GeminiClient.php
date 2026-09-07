<?php

namespace App\Libraries;

final class GeminiClient
{
    private const API_ROOT = 'https://generativelanguage.googleapis.com/v1beta';
    private const FALLBACK_MODELS = [
        'gemini-2.5-flash',
        'gemini-flash-latest',
        'gemini-3.5-flash',
        'gemini-3.6-flash',
        'gemini-2.5-flash-lite',
        'gemini-pro-latest',
    ];

    /**
     * Sends a generateContent request using only models currently advertised
     * by the API key. This prevents retired hard-coded model names from
     * breaking the assistant.
     */
    public function generate(string $apiKey, array $payload): array
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') return ['ok' => false, 'message' => 'La API key de Gemini está vacía.', 'attempts' => []];

        $listed = $this->listModels($apiKey);
        $models = self::rankModels($listed['models'] ?? []);
        if ($models === []) $models = self::FALLBACK_MODELS;

        $attempts = [];
        foreach ($models as $model) {
            $result = $this->request('POST', self::API_ROOT . '/models/' . rawurlencode($model) . ':generateContent', $apiKey, $payload);
            if ($result['status'] === 200 && is_string($result['body'])) {
                return ['ok' => true, 'body' => $result['body'], 'model' => $model, 'attempts' => $attempts];
            }

            $apiMessage = $result['status'] === 0
                ? ('Error de conexión: ' . ($result['error'] ?: 'sin respuesta del servidor.'))
                : $this->apiError($result['body'] ?? '');
            $attempts[] = ['model' => $model, 'status' => $result['status'], 'message' => $apiMessage];
            // Invalid keys and permissions will not improve with another model.
            if (in_array($result['status'], [400, 401, 403], true)) break;
        }

        $last = end($attempts) ?: [];
        $message = $last['message'] ?? ($listed['error'] ?? 'No fue posible conectar con Gemini.');
        return ['ok' => false, 'message' => $message, 'attempts' => $attempts];
    }

    public static function rankModels(array $models): array
    {
        $available = [];
        foreach ($models as $model) {
            if (is_string($model)) {
                $name = $model;
                $methods = ['generateContent'];
            } else {
                $name = (string) ($model['name'] ?? '');
                $methods = $model['supportedGenerationMethods'] ?? [];
            }
            $name = preg_replace('#^models/#', '', $name);
            if ($name === '' || !in_array('generateContent', $methods, true) || self::isSpecialized($name)) continue;
            $available[] = $name;
        }
        $available = array_values(array_unique($available));

        $configured = trim((string) env('gemini.model', ''));
        $preferred = array_values(array_filter(array_merge([$configured], self::FALLBACK_MODELS)));
        $ranked = [];
        foreach ($preferred as $name) if (in_array($name, $available, true)) $ranked[] = $name;
        foreach ($available as $name) if (!in_array($name, $ranked, true) && str_contains($name, 'flash')) $ranked[] = $name;
        foreach ($available as $name) if (!in_array($name, $ranked, true)) $ranked[] = $name;
        return $ranked;
    }

    private function listModels(string $apiKey): array
    {
        $result = $this->request('GET', self::API_ROOT . '/models?pageSize=1000', $apiKey);
        if ($result['status'] !== 200) return ['models' => [], 'error' => $this->apiError($result['body'] ?? '')];
        $data = json_decode((string) $result['body'], true);
        return ['models' => is_array($data['models'] ?? null) ? $data['models'] : []];
    }

    private function request(string $method, string $url, string $apiKey, ?array $payload = null): array
    {
        $ch = curl_init($url);
        $headers = ['Accept: application/json', 'x-goog-api-key: ' . $apiKey];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false) return ['status' => 0, 'body' => '', 'error' => $error];
        return ['status' => $status, 'body' => $body, 'error' => $error];
    }

    private function apiError(string $body): string
    {
        $data = json_decode($body, true);
        return (string) ($data['error']['message'] ?? 'Gemini no respondió correctamente.');
    }

    private static function isSpecialized(string $name): bool
    {
        foreach (['tts', 'image', 'embedding', 'transcribe', 'audio', 'veo', 'lyria', 'robotics', 'computer-use', 'deep-research', 'antigravity'] as $token) {
            if (str_contains($name, $token)) return true;
        }
        return false;
    }
}
