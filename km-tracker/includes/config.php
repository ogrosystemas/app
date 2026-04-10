<?php
// config.php - Configurações do sistema

// Configurações de PHP
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('error_reporting', E_ALL);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configurações do banco de dados
define('DB_HOST', '');
define('DB_PORT', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// ============================================================
// URL DO SITE - CORRIGIDA
// ============================================================
define('BASE_URL', '');

define('APP_NAME', 'Mutantes KM Tracker');
define('APP_VERSION', '2.0');
define('APP_SECRET', '');

// Configurações de upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt']);

// Configurações de paginação
define('ITEMS_PER_PAGE', 20);

// GraphHopper API Key
define('GRAPHOPPER_API_KEY', '');