# Sistema de Gestão para Oficina de Motos

## Instalação

1. Coloque os arquivos no servidor web (Apache/Nginx)
2. Execute o script SQL em `sql/database.sql` no MySQL
3. Configure as credenciais em `config/database.php`
4. Acesse o sistema via navegador

## Acesso

- URL: http://seuservidor/oficina_motos/
- Usuários padrão:
  - admin / password (acesso total)
  - gerente / password
  - caixa / password
  - vendedor / password
  - mecanico / password

## Funcionalidades

- ✅ Controle de clientes com múltiplas motos
- ✅ Cadastro de produtos com código de barras
- ✅ Controle de estoque com alertas
- ✅ PDV com leitor de código de barras
- ✅ Ordens de serviço com histórico
- ✅ Orçamentos convertíveis em OS
- ✅ Controle de caixa com sangria/suprimento
- ✅ Relatórios em PDF com gráficos
- ✅ Múltiplos perfis de usuário

## Tecnologias

- PHP 8.3
- MySQL
- Bootstrap 5
- Chart.js
- TCPDF

## Suporte

Para suporte, entre em contato com o desenvolvedor.

Estrutura completa do site abaixo

OS-System/
│
├── index.php
├── login.php
├── logout.php
│
├── config/
│   ├── config.php
│   └── database.php
│
├── includes/
│   └── sidebar.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── images/
│       ├── logo.png
│       └── logo-pequena.png
│
├── api/
│   ├── produtos.php
│   ├── clientes.php
│   ├── servicos.php
│   └── motos.php
│
├── modules/
│   ├── auth/
│   │   └── auth.php
│   │   └── permissao.php
│   │
│   ├── pdv/
│   │   ├── pdv.php
│   │   ├── abrir_caixa.php
│   │   └── fechar_caixa.php
│   │
│   ├── produtos/
│   │   └── produtos.php
│   │
│   ├── clientes/
│   │   ├── clientes.php
│   │   ├── salvar_moto.php
│   │   └── excluir_moto.php
│   │
│   ├── servicos/
│   │   └── servicos.php
│   │
│   ├── os/
│   │   ├── os.php
│   │   ├── os_detalhes.php
│   │   ├── os_editar.php
│   │   ├── carregar_detalhes_os.php
│   │   ├── gerar_os_pdf.php
│   │   ├── atualizar_status.php
│   │   └── atualizar_totais_os.php
│   │
│   ├── orcamentos/
│   │   ├── orcamentos.php
│   │   ├── detalhes_orcamento.php
│   │   ├── imprimir_orcamento.php
│   │   └── gerar_pdf_tcpdf.php
│   │
│   ├── estoque/
│   │   └── estoque.php
│   │
│   ├── relatorios/
│   │   ├── relatorios.php
│   │   └── gerar_relatorio_pdf.php
│   │
│   └── usuarios/
│       └── usuarios.php
│
└── tcpdf/
    ├── tcpdf.php
    ├── fonts/
    ├── config/
    └── ...