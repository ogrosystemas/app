============================================================
  OS-System — Gestão de Oficinas de Motocicletas
  Versão: 2.0 (Build corrigido)
============================================================

INSTALAÇÃO RÁPIDA
-----------------
1. Copie a pasta ossystem_new/ para seu servidor web
   (ex: /var/www/html/ossystem ou c:\xampp\htdocs\ossystem)

2. No MySQL, crie o banco e execute os SQLs em ordem:
      a) ossystem.sql         → Estrutura e dados iniciais
      b) database_patch.sql   → Adiciona colunas faltantes (OBRIGATÓRIO)

3. Configure a conexão em config/database.php:
      DB_HOST, DB_NAME, DB_USER, DB_PASS

4. Acesse: http://localhost/ossystem/

LOGINS PADRÃO
-------------
  admin@os-system.com    / admin123   (Administrador)
  gerente@os-system.com  / password   (Gerente)
  mecanico@os-system.com / password   (Mecânico)
  caixa@os-system.com    / password   (Caixa)
  vendedor@os-system.com / password   (Vendedor)

PROBLEMA DO LOGO — SOLUÇÃO
--------------------------
O arquivo config/sistema.php já está incluído neste build
e já aponta para assets/images/logo-pequena.png por padrão.

Para usar sua própria logo:
  1. Acesse Admin → Configurações
  2. Faça upload da logo (JPG/PNG/WebP, máx 5MB)
  3. Clique em "Salvar Configurações"

OU edite manualmente config/sistema.php:
  'logo_path' => 'assets/images/SUA-LOGO.png',

CORREÇÕES NESTE BUILD
---------------------
✅ config/sistema.php criado (logo não aparecia por falta deste arquivo)
✅ modules/os/atualizar_totais_os.php criado (estava faltando)
✅ modules/orcamentos/form_orcamento.php criado (formulário completo)
✅ database_patch.sql com todos os ALTER TABLE necessários
✅ Tags HTML quebradas corrigidas (</tr ?> em os.php, clientes.php, etc.)
✅ modules/clientes/clientes.php reescrito (campos completos + CEP/ViaCEP)
✅ modules/produtos/produtos.php reescrito (busca, alertas, modal completo)
✅ modules/servicos/servicos.php reescrito (design unificado)
✅ modules/estoque/estoque.php reescrito (entrada + ajuste de inventário)
✅ modules/relatorios/relatorios.php reescrito (5 tipos de relatório)
✅ modules/mao_de_obra/mao_de_obra.php reescrito (histórico de OS)
✅ modules/pdv/abrir_caixa.php reescrito (design consistente)
✅ modules/pdv/fechar_caixa.php reescrito (resumo por forma de pagamento)
✅ api/motos.php corrigido (retorna cilindrada, km_atual)
✅ api/produtos.php corrigido (busca por código de barras + nome)
✅ api/clientes.php corrigido (usa config.php, busca completa)
✅ api/servicos.php corrigido (retorna tempo_estimado, garantia_dias)
✅ assets/css/os-theme.css atualizado (classes .os-card, .os-table, etc.)
✅ modules/clientes/salvar_moto.php atualizado (novos campos)

ESTRUTURA
---------
ossystem_new/
├── index.php               Dashboard
├── login.php / logout.php
├── config/
│   ├── config.php          Configurações principais
│   ├── database.php        Conexão PDO
│   └── sistema.php         ← NOVO: logo + cores + nome
├── includes/
│   ├── sidebar.php         Navbar + layout
│   └── footer.php
├── api/                    Endpoints JSON (AJAX)
├── modules/
│   ├── pdv/                PDV + Caixa (F2=barcode, F10=finalizar)
│   ├── os/                 Ordens de Serviço + PDF
│   ├── orcamentos/         Orçamentos + conversão em OS
│   ├── clientes/           Clientes + Motos + CEP auto
│   ├── produtos/           Produtos + código de barras
│   ├── servicos/           Catálogo de serviços
│   ├── estoque/            Movimentações + alertas
│   ├── relatorios/         5 tipos + exportação PDF
│   ├── mao_de_obra/        Valor por hora
│   ├── usuarios/           Gestão de usuários
│   ├── mercadopago/        Integração MP Point
│   └── configuracoes/      Logo + cores + dados empresa
└── assets/
    ├── css/os-theme.css    Design System completo
    └── images/             logo.png, logo-pequena.png
