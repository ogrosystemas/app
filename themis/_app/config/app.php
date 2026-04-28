<?php
/**
 * Themis Enterprise — Configuração Central
 * themis.ogrosystemas.com.br
 *
 * Este arquivo fica em: public_html/_app/config/app.php
 * Edite pelo File Manager do seu painel (cPanel, Plesk, DirectAdmin).
 * Sem .env | Sem Composer | Sem SSH
 */
declare(strict_types=1);

// Raiz do projeto = pasta onde está o index.php (public_html/)
$root = dirname(__DIR__, 2); // _app/config/ -> _app/ -> raiz

return [

    // ── Aplicação ───────────────────────────────────────────
    'app' => [
        'name'     => 'Themis Enterprise',
        'url'      => 'https://themis.ogrosystemas.com.br',
        'secret'   => 'th3m1s_0gr0_s3cr3t_2025_3nterpr1se_l3g4l_k3y!!',
        'debug'    => false,
        'timezone' => 'America/Sao_Paulo',
        'env'      => 'production',
    ],

    // ── Banco de Dados ──────────────────────────────────────
    'db' => [
        'host'    => 'localhost',
        'port'    => '3306',
        'name'    => 'themis',
        'user'    => 'themis',
        'pass'    => 'Themis147369#',
        'charset' => 'utf8mb4',
    ],

    // ── Storage (pasta _storage/ na raiz) ───────────────────
    'storage' => [
        'path'       => $root . '/_storage',
        'max_mb'     => 50,
        'trash_days' => 30,
    ],

    // ── CORS ────────────────────────────────────────────────
    'cors' => [
        'origins' => [
            'https://themis.ogrosystemas.com.br',
            'http://themis.ogrosystemas.com.br',
        ],
    ],

    // ── Sessão / JWT ────────────────────────────────────────
    'session' => [
        'ttl' => 28800, // 8 horas
    ],

    // ── PDF — TCPDF ─────────────────────────────────────────
    // Arraste a pasta tcpdf/ para vendor/tcpdf/ (na raiz do site)
    'pdf' => [
        'driver'        => 'tcpdf',
        'tcpdf_path'    => $root . '/vendor/tcpdf',
        'margin_top'    => 25,
        'margin_right'  => 15,
        'margin_bottom' => 25,
        'margin_left'   => 20,
        'font'          => 'helvetica',
        'font_size'     => 11,
    ],

    // ── Assinafy ────────────────────────────────────────────
    'assinafy' => [
        'token'  => '',
        'secret' => '',
    ],

    // ── WhatsApp Business API ───────────────────────────────
    'whatsapp' => [
        'phone_id'     => '',
        'token'        => '',
        'verify_token' => '',
    ],

    // ── DataJud / CNJ ───────────────────────────────────────
    'datajud' => [
        'api_key'  => '',
        'base_url' => 'https://api-publica.datajud.cnj.jus.br',
    ],

    // ── E-mail SMTP ─────────────────────────────────────────
    'mail' => [
        'host'       => '',
        'port'       => 587,
        'encryption' => 'tls',
        'user'       => '',
        'pass'       => '',
        'from_name'  => 'Themis Enterprise',
        'from_addr'  => 'noreply@ogrosystemas.com.br',
    ],

    // ── Despesas de Campo ───────────────────────────────────
    'despesas' => [
        'valor_km_padrao' => 0.90,
    ],

];
