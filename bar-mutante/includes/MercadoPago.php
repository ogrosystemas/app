<?php
/**
 * MercadoPago.php — Integração Mercado Pago Point (PDV)
 *
 * Documentação: https://www.mercadopago.com.br/developers/pt/docs/mp-point
 *
 * Fluxo:
 * 1. GET  /point/integration-api/devices          → listar terminais
 * 2. POST /v1/orders                               → criar cobrança no terminal
 * 3. GET  /v1/orders/{order_id}                   → consultar status
 * 4. POST /v1/orders/{order_id}/cancel            → cancelar
 * Webhook: topic=order, notifica quando status muda para paid/cancelled
 */
class MercadoPago {

    private string $baseUrl   = 'https://api.mercadopago.com';
    private string $token;

    public function __construct() {
        $this->token = DB::cfg('mp_access_token', '');
    }

    // ── HTTP Request ─────────────────────────────────────────────────────────

    private function request(string $method, string $endpoint, array $body = [], array $extraHeaders = []): array {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => array_merge([
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . uniqid('bar_', true),
            ], $extraHeaders),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['error' => true, 'message' => 'cURL: ' . $curlErr, 'http_code' => 0];
        }

        $decoded = json_decode($response, true) ?? [];
        $decoded['http_code'] = $httpCode;
        $decoded['_raw']      = $response;
        return $decoded;
    }

    // ── Terminais ─────────────────────────────────────────────────────────────

    /**
     * Listar terminais Point vinculados à conta
     * GET /point/integration-api/devices
     */
    public function listarTerminais(): array {
        return $this->request('GET', '/point/integration-api/devices');
    }

    /**
     * Ativar modo PDV no terminal
     * PATCH /point/integration-api/devices/{device_id}
     */
    public function ativarModoPDV(string $deviceId): array {
        return $this->request('PATCH',
            '/point/integration-api/devices/' . $deviceId,
            ['operating_mode' => 'PDV']
        );
    }

    // ── Cobranças ─────────────────────────────────────────────────────────────

    /**
     * Criar cobrança no terminal Point
     * POST /v1/orders
     *
     * O terminal exibirá a cobrança automaticamente no modo PDV.
     */
    public function criarCobranca(
        string $deviceId,
        float  $valor,
        string $referencia,
        string $descricao = 'Venda Bar'
    ): array {
        $webhookUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/webhook.php' : '';

        $body = [
            'external_reference' => $referencia,
            'title'              => mb_substr($descricao, 0, 64),
            'total_amount'       => round($valor, 2),
            'items'              => [[
                'sku_number'   => $referencia,
                'category'     => 'services',
                'title'        => mb_substr($descricao, 0, 64),
                'description'  => mb_substr($descricao, 0, 64),
                'unit_price'   => round($valor, 2),
                'quantity'     => 1,
                'unit_measure' => 'unit',
                'total_amount' => round($valor, 2),
            ]],
        ];

        // Se tem webhook configurado, adicionar notificação
        if ($webhookUrl) {
            $body['notification_url'] = $webhookUrl;
        }

        // Criar order e atribuir ao terminal
        $order = $this->request('POST', '/v1/orders', $body);

        $code = $order['http_code'] ?? 0;
        if ($code < 200 || $code >= 300) {
            return $order; // retorna o erro
        }

        $orderId = $order['id'] ?? null;
        if (!$orderId) {
            return ['error' => true, 'message' => 'Order criada mas sem ID', 'http_code' => $code];
        }

        // Atribuir a order ao terminal Point
        $assign = $this->request('POST',
            '/point/integration-api/devices/' . $deviceId . '/payment-intents',
            [
                'order'  => ['id' => $orderId],
                'amount' => (int) round($valor * 100), // em centavos para o terminal
                'additional_info' => [
                    'external_reference' => $referencia,
                    'print_on_terminal'  => true,
                    'ticket_number'      => $referencia,
                ],
            ]
        );

        $assign['order_id'] = $orderId;
        return $assign;
    }

    /**
     * Consultar status de uma cobrança
     * GET /v1/orders/{order_id}
     */
    public function consultarCobranca(string $orderId): array {
        return $this->request('GET', '/v1/orders/' . $orderId);
    }

    /**
     * Consultar payment intent no terminal
     * GET /point/integration-api/devices/{device_id}/payment-intents/{payment_intent_id}
     */
    public function consultarIntent(string $deviceId, string $intentId): array {
        return $this->request('GET',
            '/point/integration-api/devices/' . $deviceId . '/payment-intents/' . $intentId
        );
    }

    /**
     * Cancelar cobrança
     * POST /v1/orders/{order_id}/cancel
     */
    public function cancelarCobranca(string $orderId): array {
        return $this->request('POST', '/v1/orders/' . $orderId . '/cancel');
    }

    /**
     * Cancelar payment intent no terminal
     * DELETE /point/integration-api/devices/{device_id}/payment-intents
     */
    public function cancelarIntent(string $deviceId): array {
        return $this->request('DELETE',
            '/point/integration-api/devices/' . $deviceId . '/payment-intents'
        );
    }

    // ── Diagnóstico ───────────────────────────────────────────────────────────

    /**
     * Testar conexão — verifica token e lista terminais
     */
    public function testarConexao(): array {
        if (empty($this->token)) {
            return ['ok' => false, 'message' => 'Access Token não configurado.'];
        }
        if (strlen($this->token) < 20) {
            return ['ok' => false, 'message' => 'Access Token parece inválido (' . strlen($this->token) . ' chars).'];
        }

        $r = $this->listarTerminais();
        $code = $r['http_code'] ?? 0;

        if ($code >= 200 && $code < 300) {
            $terminais = $r['data']['terminals'] ?? $r['terminals'] ?? [];
            return [
                'ok'        => true,
                'message'   => 'Conexão OK! ' . count($terminais) . ' terminal(is) encontrado(s).',
                'terminais' => $terminais,
                'http_code' => $code,
            ];
        }

        $msg = $r['message'] ?? $r['error'] ?? "HTTP $code";
        return ['ok' => false, 'message' => $msg, 'http_code' => $code, 'raw' => $r['_raw'] ?? ''];
    }

    // ── Webhook ───────────────────────────────────────────────────────────────

    /**
     * Processar evento de webhook do Mercado Pago
     * Topic: order → notifica mudança de status
     */
    public static function processarWebhook(array $data): void {
        $topic   = $data['topic']   ?? $data['type'] ?? '';
        $orderId = $data['resource'] ?? $data['data']['id'] ?? '';

        if (!$orderId) return;

        // Buscar venda pelo mp_order_id
        $venda = DB::row("SELECT * FROM vendas WHERE mp_order_id=?", [$orderId]);
        if (!$venda) return;

        // Consultar status atual da order
        $mp = new self();
        $order = $mp->consultarCobranca($orderId);
        $status = $order['status'] ?? '';

        $novoStatus = match($status) {
            'paid'                          => 'pago',
            'cancelled', 'refunded'         => 'cancelado',
            default                         => $venda['status'],
        };

        DB::update('vendas', [
            'status'    => $novoStatus,
            'mp_status' => $status,
        ], 'id=?', [$venda['id']]);

        if ($novoStatus === 'pago' && $venda['status'] !== 'pago') {
            DB::q("UPDATE caixas SET total_vendas=total_vendas+? WHERE id=?",
                [$venda['total'], $venda['caixa_id']]);
        }
    }
}
