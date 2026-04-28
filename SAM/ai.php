<?php
/**
 * ai.php — Sistema Multi-IA centralizado
 * Provedores: groq, gemini, openai, claude, mistral
 */

const AI_PROVIDERS = [
    'groq' => [
        'name'    => 'Groq — LLaMA 3.3 70B',
        'model'   => 'llama-3.3-70b-versatile',
        'badge'   => 'GRATUITO',
        'color'   => '#22c55e',
        'format'  => 'openai',
        'url'     => 'https://api.groq.com/openai/v1/chat/completions',
        'key_hint'=> 'gsk_...',
        'link'    => 'https://console.groq.com',
        'desc'    => '14.400 req/dia gratuito · Muito rápido',
    ],
    'gemini' => [
        'name'    => 'Google Gemini 2.0 Flash',
        'model'   => 'gemini-2.0-flash',
        'badge'   => 'FREE TIER',
        'color'   => '#3483FA',
        'format'  => 'gemini',
        'url'     => '',
        'key_hint'=> 'AIzaSy...',
        'link'    => 'https://aistudio.google.com/apikey',
        'desc'    => 'Free tier disponível · Google AI Studio',
    ],
    'openai' => [
        'name'    => 'OpenAI GPT-4o Mini',
        'model'   => 'gpt-4o-mini',
        'badge'   => 'PAGO',
        'color'   => '#f59e0b',
        'format'  => 'openai',
        'url'     => 'https://api.openai.com/v1/chat/completions',
        'key_hint'=> 'sk-...',
        'link'    => 'https://platform.openai.com/api-keys',
        'desc'    => 'Melhor custo-benefício da OpenAI',
    ],
    'claude' => [
        'name'    => 'Anthropic Claude Haiku',
        'model'   => 'claude-haiku-4-5-20251001',
        'badge'   => 'PAGO',
        'color'   => '#a855f7',
        'format'  => 'claude',
        'url'     => 'https://api.anthropic.com/v1/messages',
        'key_hint'=> 'sk-ant-...',
        'link'    => 'https://console.anthropic.com',
        'desc'    => 'Claude da Anthropic — rápido e preciso',
    ],
    'mistral' => [
        'name'    => 'Mistral Small',
        'model'   => 'mistral-small-latest',
        'badge'   => 'FREE TIER',
        'color'   => '#f97316',
        'format'  => 'openai',
        'url'     => 'https://api.mistral.ai/v1/chat/completions',
        'key_hint'=> 'xxxxxxxx...',
        'link'    => 'https://console.mistral.ai',
        'desc'    => 'Free tier generoso · Ótimo para português',
    ],
];

function ai_get_config(string $tenantId): array {
    $rows = db_all(
        "SELECT `key`, value FROM tenant_settings WHERE tenant_id=? AND `key` IN
         ('ai_provider','ai_key_groq','ai_key_gemini','ai_key_openai','ai_key_claude','ai_key_mistral',
          'groq_api_key','gemini_api_key')",
        [$tenantId]
    );
    $cfg = ['provider' => 'groq'];
    foreach ($rows as $row) {
        $k = $row['key'];
        if ($k === 'ai_provider') {
            $cfg['provider'] = $row['value'];
        } elseif (str_starts_with($k, 'ai_key_')) {
            $cfg[str_replace('ai_key_', '', $k)] = $row['value'];
        } elseif ($k === 'groq_api_key'   && empty($cfg['groq']))   {
            $cfg['groq']   = $row['value']; // compatibilidade legada
        } elseif ($k === 'gemini_api_key' && empty($cfg['gemini'])) {
            $cfg['gemini'] = $row['value']; // compatibilidade legada
        }
    }
    // Fallback para config.php
    if (empty($cfg['groq'])   && defined('GROQ_API_KEY')   && GROQ_API_KEY)   $cfg['groq']   = GROQ_API_KEY;
    if (empty($cfg['gemini']) && defined('GEMINI_API_KEY') && GEMINI_API_KEY) $cfg['gemini'] = GEMINI_API_KEY;
    return $cfg;
}

function ai_call_openai(string $url, string $key, string $model, string $prompt, int $maxTokens): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['model'=>$model,'messages'=>[['role'=>'user','content'=>$prompt]],'max_tokens'=>$maxTokens,'temperature'=>0.3]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Authorization: Bearer '.$key],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$res || $code !== 200) return '';
    return trim(json_decode($res, true)['choices'][0]['message']['content'] ?? '');
}

function ai_call_gemini(string $key, string $model, string $prompt, int $maxTokens): string {
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['maxOutputTokens'=>$maxTokens,'temperature'=>0.3]]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$res || $code !== 200) return '';
    return trim(json_decode($res, true)['candidates'][0]['content']['parts'][0]['text'] ?? '');
}

function ai_call_claude(string $key, string $model, string $prompt, int $maxTokens): string {
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['model'=>$model,'max_tokens'=>$maxTokens,'messages'=>[['role'=>'user','content'=>$prompt]]]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json','x-api-key: '.$key,'anthropic-version: 2023-06-01'],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$res || $code !== 200) return '';
    return trim(json_decode($res, true)['content'][0]['text'] ?? '');
}

function ai_call_provider(string $provider, array $cfg, string $prompt, int $maxTokens): string {
    $p   = AI_PROVIDERS[$provider] ?? null;
    $key = $cfg[$provider] ?? '';
    if (!$p || !$key) return '';
    return match($p['format']) {
        'openai' => ai_call_openai($p['url'], $key, $p['model'], $prompt, $maxTokens),
        'gemini' => ai_call_gemini($key, $p['model'], $prompt, $maxTokens),
        'claude' => ai_call_claude($key, $p['model'], $prompt, $maxTokens),
        default  => '',
    };
}

function ai_generate(string $tenantId, string $prompt, int $maxTokens = 500): array {
    $cfg      = ai_get_config($tenantId);
    $provider = $cfg['provider'] ?? 'groq';
    if (!isset(AI_PROVIDERS[$provider])) $provider = 'groq';

    $text = ai_call_provider($provider, $cfg, $prompt, $maxTokens);

    // Fallback para Groq se falhar
    if (!$text && $provider !== 'groq' && !empty($cfg['groq'])) {
        $text = ai_call_provider('groq', $cfg, $prompt, $maxTokens);
        if ($text) return ['text'=>$text, 'provider'=>'groq (fallback)', 'model'=>AI_PROVIDERS['groq']['model']];
    }

    return ['text'=>$text, 'provider'=>$provider, 'model'=>AI_PROVIDERS[$provider]['model'] ?? ''];
}

function ai_test_provider(string $provider, string $apiKey): array {
    $p = AI_PROVIDERS[$provider] ?? null;
    if (!$p) return ['ok'=>false,'error'=>'Provedor desconhecido'];
    $start  = microtime(true);
    $result = match($p['format']) {
        'openai' => ai_call_openai($p['url'], $apiKey, $p['model'], 'Responda apenas a palavra: ok', 10),
        'gemini' => ai_call_gemini($apiKey, $p['model'], 'Responda apenas a palavra: ok', 10),
        'claude' => ai_call_claude($apiKey, $p['model'], 'Responda apenas a palavra: ok', 10),
        default  => '',
    };
    $ms = round((microtime(true) - $start) * 1000);
    return $result
        ? ['ok'=>true, 'latency_ms'=>$ms, 'response'=>trim($result)]
        : ['ok'=>false,'latency_ms'=>$ms, 'error'=>'Sem resposta — verifique a API key'];
}

// ── Usos disponíveis do sistema ───────────────────────────
const AI_USES = [
    'sac'      => ['label' => 'SAC — Sugestão de resposta',       'icon' => 'message-square'],
    'perguntas'=> ['label' => 'Robô — Perguntas Pré-venda',        'icon' => 'help-circle'],
    'renovar'  => ['label' => 'Auto Renovar — Validação de payload','icon' => 'refresh-cw'],
];

/**
 * Retorna o provedor configurado para um uso específico.
 * Fallback para o provedor padrão do tenant.
 */
function ai_get_provider_for_use(string $tenantId, string $use): string {
    $row = db_one(
        "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?",
        [$tenantId, "ai_provider_{$use}"]
    );
    if (!empty($row['value']) && isset(AI_PROVIDERS[$row['value']])) {
        return $row['value'];
    }
    // Fallback: provedor padrão do tenant
    $def = db_one(
        "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`='ai_provider'",
        [$tenantId]
    );
    return $def['value'] ?? 'groq';
}

/**
 * ai_generate com suporte a uso específico
 * $use = 'sac' | 'perguntas' | 'renovar' | null (usa padrão)
 */
function ai_generate_for(string $tenantId, string $use, string $prompt, int $maxTokens = 500): array {
    $cfg      = ai_get_config($tenantId);
    $provider = ai_get_provider_for_use($tenantId, $use);

    if (!isset(AI_PROVIDERS[$provider])) $provider = 'groq';

    $text = ai_call_provider($provider, $cfg, $prompt, $maxTokens);

    // Fallback para Groq se falhar
    if (!$text && $provider !== 'groq' && !empty($cfg['groq'])) {
        $text = ai_call_provider('groq', $cfg, $prompt, $maxTokens);
        if ($text) return ['text'=>$text, 'provider'=>'groq (fallback)', 'model'=>AI_PROVIDERS['groq']['model'], 'use'=>$use];
    }

    return ['text'=>$text, 'provider'=>$provider, 'model'=>AI_PROVIDERS[$provider]['model']??'', 'use'=>$use];
}
