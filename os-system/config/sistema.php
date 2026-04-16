<?php
/**
 * config/sistema.php
 *
 * Arquivo de configurações visuais e de identidade do sistema.
 * É criado/atualizado automaticamente pelo módulo Configurações (Admin).
 *
 * ⚠️  ESTE É O ARQUIVO QUE RESOLVE O PROBLEMA DO LOGO:
 *     A sidebar.php tenta carregar este arquivo em:
 *         __DIR__ . '/../config/sistema.php'
 *     Sem ele, logo_path fica vazio e o ícone padrão é exibido no lugar.
 *
 *     Para exibir sua logo:
 *       1) Acesse o sistema como admin
 *       2) Vá em Admin → Configurações
 *       3) Faça upload da logo (JPG/PNG/WebP/SVG, max 5MB)
 *       4) Clique em "Salvar Configurações"
 *
 *     O arquivo será atualizado automaticamente com o caminho da imagem.
 *
 * Alternativamente, edite logo_path manualmente apontando para um arquivo
 * dentro de assets/images/, por exemplo:
 *     'logo_path' => 'assets/images/logo.png',
 */
return [
    'nome_sistema'   => 'OS-System',
    'nome_oficina'   => 'Oficina de Motos',
    'telefone'       => '',
    'email'          => '',
    'endereco'       => '',
    'cnpj'           => '',
    'cor_primaria'   => '#f59e0b',
    /*
     * Para ativar o logo imediatamente, descomente e ajuste a linha abaixo:
     * 'logo_path'   => 'assets/images/logo.png',
     */
    'logo_path'      => 'assets/images/logo-pequena.png',
];
