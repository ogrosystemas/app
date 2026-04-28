<?php
// error.php
$code = (int)($_GET['code'] ?? 404);
$messages = [
    400 => 'Requisição Inválida',
    401 => 'Não Autorizado',
    403 => 'Acesso Proibido',
    404 => 'Página Não Encontrada',
    500 => 'Erro Interno do Servidor',
    502 => 'Bad Gateway',
    503 => 'Serviço Indisponível'
];
$message = $messages[$code] ?? 'Erro Desconhecido';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro <?= $code ?> - <?= htmlspecialchars(setting('sistema_nome','KM Tracker')) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background:var(--bg-body); color:var(--text); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .error-container { text-align: center; padding: 40px; }
        .error-code { font-size: 6rem; color: #f39c12; margin: 0; }
        .error-message { font-size: 1.5rem; margin: 20px 0; }
        .error-link { color: #f39c12; text-decoration: none; }
        .error-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code"><?= $code ?></h1>
        <p class="error-message"><?= $message ?></p>
        <a href="/" class="error-link">← Voltar para o início</a>
    </div>
</body>
</html>