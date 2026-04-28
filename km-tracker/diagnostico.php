<?php
// diagnostico.php - Script de verificação do sistema
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico do Sistema</title>
    <style>
        body { font-family: monospace; background:var(--bg-body); color:var(--text); padding: 20px; }
        .ok { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #f39c12; }
        .card { background:var(--bg-card); border:1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        h3 { color: #f5b041; margin-top: 0; }
        pre { background:var(--bg-input); padding: 10px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico do Sistema - Mutantes KM Tracker</h1>
";

// 1. Verificar configurações
echo "<div class='card'>";
echo "<h3>📁 Configurações</h3>";
echo "<p>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : '<span class="error">❌ Não definida</span>') . "</p>";
echo "<p>GraphHopper Key: " . (defined('GRAPHOPPER_API_KEY') && !empty(GRAPHOPPER_API_KEY) ? '<span class="ok">✅ Configurada</span>' : '<span class="error">❌ Não configurada</span>') . "</p>";
echo "<p>Ambiente: " . (defined('DEBUG_MODE') && DEBUG_MODE ? '<span class="warning">⚠️ Desenvolvimento</span>' : '<span class="ok">✅ Produção</span>') . "</p>";
echo "</div>";

// 2. Verificar banco de dados
echo "<div class='card'>";
echo "<h3>🗄️ Banco de Dados</h3>";

try {
    $db = db();
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tabelas encontradas: " . count($tables) . "</p>";
    
    $requiredTables = ['users', 'events', 'attendances'];
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "<p class='ok'>✅ Tabela $table existe</p>";
        } else {
            echo "<p class='error'>❌ Tabela $table não encontrada</p>";
        }
    }
    
    // Verificar colunas adicionais
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['moto_modelo', 'moto_tanque', 'moto_kml', 'gas_preco'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "<p class='ok'>✅ Coluna users.$col existe</p>";
        } else {
            echo "<p class='error'>❌ Coluna users.$col não encontrada</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erro no banco: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Verificar API
echo "<div class='card'>";
echo "<h3>🌐 API</h3>";

// Testar autocomplete
$apiUrl = BASE_URL . '/api/route.php';
echo "<p>Testando API em: $apiUrl</p>";

$ch = curl_init($apiUrl . '?action=autocomplete&q=São Paulo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        echo "<p class='ok'>✅ API acessível. Retornou " . count($data) . " resultados.</p>";
    } else {
        echo "<p class='warning'>⚠️ API respondeu mas retornou dados vazios</p>";
    }
} else {
    echo "<p class='error'>❌ API não respondeu. HTTP $httpCode</p>";
}
echo "</div>";

// 4. Verificar pastas
echo "<div class='card'>";
echo "<h3>📂 Estrutura de Pastas</h3>";

$folders = ['api/cache', 'api/assets', 'includes', 'admin', 'user', 'logs'];
foreach ($folders as $folder) {
    $path = __DIR__ . '/' . $folder;
    if (file_exists($path)) {
        echo "<p class='ok'>✅ Pasta $folder existe</p>";
        if (!is_writable($path) && $folder === 'api/cache') {
            echo "<p class='warning'>⚠️ Pasta $folder não tem permissão de escrita</p>";
        }
    } else {
        echo "<p class='error'>❌ Pasta $folder não encontrada</p>";
    }
}
echo "</div>";

// 5. Verificar arquivos essenciais
echo "<div class='card'>";
echo "<h3>📄 Arquivos Essenciais</h3>";

$files = [
    'includes/config.php',
    'includes/bootstrap.php',
    'includes/auth.php',
    'includes/db.php',
    'includes/helpers.php',
    'includes/layout.php',
    'admin/events.php',
    'admin/users.php',
    'api/route.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p class='ok'>✅ $file</p>";
    } else {
        echo "<p class='error'>❌ $file não encontrado</p>";
    }
}
echo "</div>";

// 6. Verificar extensões PHP
echo "<div class='card'>";
echo "<h3>🐘 Extensões PHP</h3>";

$extensions = ['pdo_mysql', 'curl', 'json', 'session', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='ok'>✅ $ext</p>";
    } else {
        echo "<p class='error'>❌ $ext não carregada</p>";
    }
}
echo "</div>";

// 7. Teste de envio de e-mail (opcional)
echo "<div class='card'>";
echo "<h3>📧 Teste de E-mail</h3>";
if (function_exists('mail')) {
    echo "<p class='ok'>✅ Função mail() disponível</p>";
} else {
    echo "<p class='warning'>⚠️ Função mail() não disponível</p>";
}
echo "</div>";

// 8. Resumo
echo "<div class='card'>";
echo "<h3>📊 Resumo</h3>";
echo "<pre>
Para testar manualmente:
1. Acesse /admin/events.php - Deve listar eventos
2. Clique em 'Novo Evento' - Deve abrir modal
3. Digite um endereço no campo 'Ponto de Partida' - Deve aparecer sugestões
4. Clique em 'Calcular Rota' - Deve calcular distância
5. Verifique se o mapa carrega
6. Teste /admin/users.php - Deve listar usuários
</pre>";
echo "</div>";

echo "</body></html>";