<?php
// ============================================================
// includes/bootstrap.php — Inicialização de páginas protegidas
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/helpers.php';

// 1. Inicia sessão segura (DEVE ser antes de qualquer output)
if (session_status() === PHP_SESSION_NONE) session_start();

// 2. Aplica headers HTTP de segurança
applySecurityHeaders();

// 3. Rate limit global — 120 req/min por IP (generoso para uso normal)
checkRateLimit('global', 120, 60);

// 4. Escaneia GET e POST em busca de padrões de ataque
scanRequestInputs();
