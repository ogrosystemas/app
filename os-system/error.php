<?php
$code = $_GET['code'] ?? '404';
$messages = [
    '400' => 'Requisição Inválida',
    '401' => 'Não Autorizado',
    '403' => 'Acesso Proibido',
    '404' => 'Página Não Encontrada',
    '500' => 'Erro Interno do Servidor'
];
$message = $messages[$code] ?? 'Erro Desconhecido';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro <?php echo $code; ?> - OS-System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 50px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-code {
            font-size: 100px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .error-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 35px;
            color: white;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code"><?php echo $code; ?></div>
        <h2 class="mt-3"><?php echo $message; ?></h2>
        <p class="text-muted mt-3">A página que você está procurando pode ter sido removida, renomeada ou está temporariamente indisponível.</p>
        <a href="/os-system/index.php" class="btn-home mt-4">
            <i class="fas fa-arrow-left me-2"></i> Voltar para o Dashboard
        </a>
    </div>
</body>
</html>