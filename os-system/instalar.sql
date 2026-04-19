-- ============================================================
-- OS-System — Script de Instalação Completo
-- Versão: 2.1
-- ============================================================
-- INSTRUÇÕES:
--   1. Crie o banco: CREATE DATABASE ossystem CHARACTER SET utf8mb4;
--   2. Execute este arquivo completo no MySQL/phpMyAdmin
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `ossystem`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ossystem`;

-- ══════════════════════════════════════════════════════════════
-- TABELAS BASE
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(100) NOT NULL,
  `email`       VARCHAR(100) NOT NULL UNIQUE,
  `senha`       VARCHAR(255) NOT NULL,
  `perfil`      ENUM('admin','gerente','mecanico','caixa','vendedor') NOT NULL DEFAULT 'vendedor',
  `ativo`       TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `clientes` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `tipo`        ENUM('pf','pj') NOT NULL DEFAULT 'pf',
  `nome`        VARCHAR(100) NOT NULL,
  `cpf_cnpj`   VARCHAR(20) DEFAULT NULL,
  `rg_ie`      VARCHAR(30) DEFAULT NULL,
  `telefone`    VARCHAR(20) DEFAULT NULL,
  `celular`     VARCHAR(20) DEFAULT NULL,
  `email`       VARCHAR(100) DEFAULT NULL,
  `cep`         VARCHAR(10) DEFAULT NULL,
  `endereco`    TEXT DEFAULT NULL,
  `numero`      VARCHAR(10) DEFAULT NULL,
  `complemento` VARCHAR(100) DEFAULT NULL,
  `bairro`      VARCHAR(100) DEFAULT NULL,
  `cidade`      VARCHAR(100) DEFAULT NULL,
  `estado`      CHAR(2) DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`(50)),
  KEY `idx_cpf`  (`cpf_cnpj`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `motos` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `cliente_id`  INT(11) NOT NULL,
  `placa`       VARCHAR(10) NOT NULL,
  `modelo`      VARCHAR(50) DEFAULT NULL,
  `marca`       VARCHAR(50) DEFAULT NULL,
  `ano`         INT(4) DEFAULT NULL,
  `cor`         VARCHAR(20) DEFAULT NULL,
  `chassi`      VARCHAR(50) DEFAULT NULL,
  `cilindrada`  VARCHAR(10) DEFAULT NULL,
  `km_atual`    INT UNSIGNED DEFAULT 0,
  `combustivel` ENUM('gasolina','alcool','flex','eletrico') DEFAULT 'gasolina',
  `observacoes` TEXT DEFAULT NULL,
  `ativo`       TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_placa` (`placa`),
  KEY `idx_cliente` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categorias_produtos` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(100) NOT NULL,
  `descricao`   VARCHAR(255) DEFAULT NULL,
  `cor`         VARCHAR(7) DEFAULT '#f59e0b',
  `ativo`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fornecedores` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(100) NOT NULL,
  `cnpj`        VARCHAR(20) DEFAULT NULL,
  `telefone`    VARCHAR(20) DEFAULT NULL,
  `email`       VARCHAR(100) DEFAULT NULL,
  `contato`     VARCHAR(100) DEFAULT NULL,
  `endereco`    TEXT DEFAULT NULL,
  `ativo`       TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mao_de_obra` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `valor_hora`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descricao`   VARCHAR(255) DEFAULT NULL,
  `ativo`       TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `produtos` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_barras` VARCHAR(50) DEFAULT NULL,
  `nome`          VARCHAR(100) NOT NULL,
  `descricao`     TEXT DEFAULT NULL,
  `preco_compra`  DECIMAL(10,2) DEFAULT NULL,
  `preco_venda`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estoque_atual` DECIMAL(10,3) DEFAULT 0.000,
  `estoque_minimo` INT(11) DEFAULT 5,
  `unidade`       VARCHAR(5) DEFAULT 'UN',
  `categoria_id`  INT(11) DEFAULT NULL,
  `exibir_pdv`    TINYINT(1) NOT NULL DEFAULT 1,
  `ncm`           VARCHAR(10) DEFAULT NULL,
  `localizacao`   VARCHAR(50) DEFAULT NULL,
  `ativo`         TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_codigo_barras` (`codigo_barras`(50)),
  KEY `idx_nome_produto`  (`nome`(50)),
  KEY `idx_categoria`     (`categoria_id`),
  KEY `idx_pdv`           (`exibir_pdv`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `servicos` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `nome`            VARCHAR(100) NOT NULL,
  `descricao`       TEXT DEFAULT NULL,
  `valor`           DECIMAL(10,2) DEFAULT 0.00,
  `tempo_estimado`  INT(11) DEFAULT NULL COMMENT 'minutos',
  `garantia_dias`   INT(11) DEFAULT 30,
  `ativo`           TINYINT(1) DEFAULT 1,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `permissoes` (
  `id`       INT(11) NOT NULL AUTO_INCREMENT,
  `perfil`   VARCHAR(50) NOT NULL,
  `modulo`   VARCHAR(50) NOT NULL,
  `acao`     VARCHAR(50) NOT NULL DEFAULT 'ver',
  `ativo`    TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_perm` (`perfil`,`modulo`,`acao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `caixa` (
  `id`                 INT(11) NOT NULL AUTO_INCREMENT,
  `data_abertura`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_fechamento`    TIMESTAMP NULL DEFAULT NULL,
  `saldo_inicial`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_final`        DECIMAL(10,2) DEFAULT NULL,
  `total_vendas`       DECIMAL(10,2) DEFAULT 0.00,
  `total_sangrias`     DECIMAL(10,2) DEFAULT 0.00,
  `total_suprimentos`  DECIMAL(10,2) DEFAULT 0.00,
  `status`             ENUM('aberto','fechado') DEFAULT 'aberto',
  `usuario_abertura`   INT(11) DEFAULT NULL,
  `usuario_fechamento` INT(11) DEFAULT NULL,
  `created_by`         INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `caixa_movimentacoes` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `caixa_id`   INT(11) NOT NULL,
  `tipo`       ENUM('venda','sangria','suprimento') NOT NULL,
  `valor`      DECIMAL(10,2) NOT NULL,
  `descricao`  VARCHAR(200) DEFAULT NULL,
  `venda_id`   INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ordens_servico` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `numero_os`     VARCHAR(20) NOT NULL,
  `cliente_id`    INT(11) NOT NULL,
  `moto_id`       INT(11) NOT NULL,
  `km_entrada`    INT UNSIGNED DEFAULT NULL,
  `status`        ENUM('aberta','em_andamento','aguardando_pecas','finalizada','cancelada') DEFAULT 'aberta',
  `prioridade`    ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
  `data_abertura` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_previsao` DATE DEFAULT NULL,
  `data_finalizacao` DATE DEFAULT NULL,
  `observacoes`   TEXT DEFAULT NULL,
  `total_servicos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_produtos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_geral`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_by`    INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_numero_os` (`numero_os`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `os_servicos` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `os_id`          INT(11) NOT NULL,
  `servico_id`     INT(11) NOT NULL DEFAULT 0,
  `quantidade`     INT(11) NOT NULL DEFAULT 1,
  `valor_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `mecanico_id`    INT(11) DEFAULT NULL,
  `garantia_dias`  INT DEFAULT 0,
  `tempo_gasto`    INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `os_produtos` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `os_id`          INT(11) NOT NULL,
  `produto_id`     INT(11) NOT NULL DEFAULT 0,
  `quantidade`     INT(11) NOT NULL DEFAULT 1,
  `valor_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `os_status_log` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `os_id`          INT(11) NOT NULL,
  `status_anterior` VARCHAR(50) DEFAULT NULL,
  `status_novo`    VARCHAR(50) NOT NULL,
  `observacao`     TEXT DEFAULT NULL,
  `usuario_id`     INT(11) DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orcamentos` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  `numero_orcamento`  VARCHAR(20) NOT NULL,
  `cliente_id`        INT(11) NOT NULL,
  `moto_id`           INT(11) NOT NULL,
  `data_criacao`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_validade`     DATE NOT NULL,
  `status`            ENUM('ativo','aprovado','rejeitado','convertido') DEFAULT 'ativo',
  `observacoes`       TEXT DEFAULT NULL,
  `convertido_os_id`  INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orcamento_itens` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `orcamento_id`   INT(11) NOT NULL,
  `tipo`           ENUM('servico','produto') NOT NULL,
  `item_id`        INT(11) NOT NULL,
  `quantidade`     DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `valor_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vendas` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `numero_venda`    VARCHAR(30) NOT NULL,
  `cliente_id`      INT(11) DEFAULT NULL,
  `data_venda`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto`        DECIMAL(10,2) DEFAULT 0.00,
  `total`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` ENUM('dinheiro','pix','cartao_credito','cartao_debito','boleto','mix') NOT NULL,
  `parcelas`        INT(11) DEFAULT 1,
  `status`          ENUM('finalizada','cancelada') DEFAULT 'finalizada',
  `caixa_id`        INT(11) DEFAULT NULL,
  `created_by`      INT(11) DEFAULT NULL,
  `mp_payment_id`   VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_data_venda` (`data_venda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `venda_itens` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `venda_id`       INT(11) NOT NULL,
  `produto_id`     INT(11) NOT NULL,
  `quantidade`     DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `valor_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `movimentacoes_estoque` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `produto_id`     INT(11) NOT NULL,
  `tipo`           ENUM('entrada','saida') NOT NULL,
  `quantidade`     DECIMAL(10,3) NOT NULL,
  `custo_unitario` DECIMAL(10,2) DEFAULT NULL,
  `motivo`         VARCHAR(200) DEFAULT NULL,
  `documento`      VARCHAR(100) DEFAULT NULL,
  `created_by`     INT(11) DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produto_mov` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `logs_atividades` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT(11) DEFAULT NULL,
  `acao`        VARCHAR(100) DEFAULT NULL,
  `tabela`      VARCHAR(50) DEFAULT NULL,
  `registro_id` INT(11) DEFAULT NULL,
  `ip`          VARCHAR(45) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════
-- DADOS INICIAIS
-- ══════════════════════════════════════════════════════════════

-- Usuários padrão (senha: password para todos exceto admin)
INSERT IGNORE INTO `usuarios` (`id`,`nome`,`email`,`senha`,`perfil`) VALUES
(1,  'Administrador',  'admin@os-system.com',    '$2y$10$7XFWoIjz1XcJHLUf1NRqC.Ql7oh8qW3e69LlpQP3M6tKiwHXAOwzm', 'admin'),
(12, 'João Gerente',   'gerente@os-system.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  'gerente'),
(13, 'Carlos Mecânico','mecanico@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  'mecanico'),
(14, 'Ana Caixa',      'caixa@os-system.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  'caixa'),
(15, 'Pedro Vendedor', 'vendedor@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  'vendedor');

-- Nota: senha do admin = (hashed unique), demais = 'password'

-- Categorias de produtos
INSERT IGNORE INTO `categorias_produtos` (`id`,`nome`,`cor`) VALUES
(1,  'Óleo e Lubrificantes', '#f59e0b'),
(2,  'Filtros',              '#22c55e'),
(3,  'Freios',               '#ef4444'),
(4,  'Pneus e Rodas',        '#3b82f6'),
(5,  'Elétrica',             '#a855f7'),
(6,  'Motor',                '#f97316'),
(7,  'Transmissão',          '#06b6d4'),
(8,  'Escapamento',          '#64748b'),
(9,  'Carroceria e Acessórios','#ec4899'),
(10, 'Parafusos e Fixadores','#84cc16'),
(11, 'Baterias',             '#eab308'),
(12, 'Outros',               '#94a3b8');

-- Mão de obra padrão
INSERT IGNORE INTO `mao_de_obra` (`id`,`valor_hora`,`descricao`) VALUES
(1, 100.00, 'Tabela padrão');

-- Permissões
INSERT IGNORE INTO `permissoes` (`perfil`,`modulo`,`acao`) VALUES
('admin','*','*'),
('gerente','clientes','ver'),('gerente','clientes','editar'),
('gerente','produtos','ver'),('gerente','produtos','editar'),
('gerente','os','ver'),('gerente','os','editar'),
('gerente','orcamentos','ver'),('gerente','orcamentos','editar'),
('gerente','estoque','ver'),('gerente','estoque','editar'),
('gerente','relatorios','ver'),
('mecanico','os','ver'),('mecanico','os','editar'),
('mecanico','clientes','ver'),
('caixa','pdv','ver'),('caixa','pdv','editar'),
('caixa','caixa','ver'),('caixa','caixa','editar'),
('vendedor','pdv','ver'),('vendedor','pdv','editar'),
('vendedor','produtos','ver'),('vendedor','clientes','ver');

-- Clientes de exemplo
INSERT IGNORE INTO `clientes` (`id`,`tipo`,`nome`,`cpf_cnpj`,`telefone`,`celular`,`email`,`cidade`,`estado`) VALUES
(1,'pf','João Silva',      '123.456.789-00','(11) 9999-9999','(11) 99999-9999','joao.silva@email.com',   'São Paulo','SP'),
(2,'pf','Maria Santos',    '987.654.321-00','(11) 8888-8888','(11) 98888-8888','maria.santos@email.com', 'São Paulo','SP'),
(3,'pf','Carlos Oliveira', '111.222.333-44','(21) 7777-7777','(21) 97777-7777','carlos@email.com',       'Rio de Janeiro','RJ'),
(4,'pf','Ana Souza',       '555.666.777-88','(31) 6666-6666','(31) 96666-6666','ana.souza@email.com',    'Belo Horizonte','MG'),
(5,'pf','Roberto Lima',    '999.888.777-66','(41) 5555-5555','(41) 95555-5555','roberto.lima@email.com', 'Curitiba','PR');

-- Motos de exemplo
INSERT IGNORE INTO `motos` (`id`,`cliente_id`,`placa`,`modelo`,`marca`,`ano`,`cor`,`cilindrada`,`km_atual`) VALUES
(1,1,'ABC-1234','CG 160',     'Honda',  2022,'Vermelha','160',15000),
(2,1,'DEF-5678','Factor 150', 'Yamaha', 2021,'Azul',    '150',22000),
(3,2,'GHI-9012','CB 300R',    'Honda',  2023,'Preta',   '300',8500),
(4,3,'JKL-3456','Pop 100',    'Honda',  2020,'Branca',  '100',35000),
(5,4,'MNO-7890','Lander 250', 'Yamaha', 2022,'Cinza',   '250',12000),
(6,5,'PQR-1234','Crosser 150','Yamaha', 2021,'Azul',    '150',19000);

-- Serviços de exemplo
INSERT IGNORE INTO `servicos` (`id`,`nome`,`descricao`,`valor`,`tempo_estimado`,`garantia_dias`) VALUES
(1, 'Troca de Óleo',                'Troca de óleo motor + filtro',         0.00, 30,  1000),
(2, 'Revisão Completa',             'Revisão completa da moto',             0.00, 120, 30),
(3, 'Troca de Pastilhas de Freio',  'Substituição das pastilhas de freio',  0.00, 60,  30),
(4, 'Alinhamento e Balanceamento',  'Alinhamento e balanceamento das rodas',0.00, 45,  30),
(5, 'Troca de Corrente',            'Substituição da corrente de transmissão',0.00,50, 30),
(6, 'Regulagem de Motor',           'Regulagem completa do motor',          0.00, 120, 30),
(7, 'Troca de Pneus',               'Troca do par de pneus',                0.00, 60,  30),
(8, 'Troca de Bateria',             'Substituição da bateria',              0.00, 20,  365),
(9, 'Revisão Preventiva',           'Revisão preventiva completa',          0.00, 90,  60),
(10,'Lavagem Especial',             'Lavagem completa da moto',             0.00, 60,  31);

-- Produtos de exemplo (com categorias)
INSERT IGNORE INTO `produtos` (`id`,`codigo_barras`,`nome`,`descricao`,`preco_compra`,`preco_venda`,`estoque_atual`,`estoque_minimo`,`unidade`,`categoria_id`,`exibir_pdv`,`ativo`) VALUES
(1, '7891234560010','Óleo Motor 20W50 1L',          'Óleo para motor 20W50 - 1 litro',           25.00, 35.00, 65, 10,'LT', 1, 1, 1),
(2, '7891234560027','Filtro de Óleo',               'Filtro de óleo para motos',                  15.00, 25.00, 29,  8,'PC', 2, 1, 1),
(3, '7891234560034','Pastilha de Freio Dianteira',  'Pastilha de freio dianteira universal',       30.00, 45.00, 18,  5,'PC', 3, 1, 1),
(4, '7891234560041','Corrente de Transmissão 428',  'Corrente para transmissão 428H',              80.00,120.00, 14,  4,'PC', 7, 1, 1),
(5, '7891234560058','Vela de Ignição NGK',          'Vela de ignição NGK para motos 125-160cc',   12.00, 18.00, 39, 10,'PC', 6, 1, 1),
(6, '7891234560065','Pneu Dianteiro 90/90-18',      'Pneu dianteiro aro 18',                      150.00,250.00, 23,  3,'PC', 4, 1, 1),
(7, '7891234560072','Pneu Traseiro 110/90-18',      'Pneu traseiro aro 18',                       170.00,280.00, 17,  3,'PC', 4, 1, 1),
(8, '7891234560089','Bateria 12V 5Ah',              'Bateria selada 12V 5Ah para motos',           90.00,180.00,  7,  3,'PC',11, 1, 1),
(9, '7891234560096','Farol LED Universal',          'Farol LED H4 com suporte universal',          80.00,150.00, 25,  5,'PC', 5, 1, 1),
(10,'7891234560102','Retrovisor Par Universal',     'Par de retrovisores cromados universais',     35.00, 60.00, 18,  4,'PR', 9, 1, 1);

-- Fornecedores
INSERT IGNORE INTO `fornecedores` (`id`,`nome`,`cnpj`,`telefone`,`email`) VALUES
(1,'Peças Honda Brasil',   '12.345.678/0001-90','(11) 4000-0000','vendas@pecashonda.com.br'),
(2,'Distribuidora Yamaha', '98.765.432/0001-10','(11) 5000-0000','vendas@yamaha.com.br'),
(3,'Motoparts Brasil',     '11.222.333/0001-44','(11) 6000-0000','contato@motoparts.com.br');

-- Caixa aberto (exemplo)
INSERT IGNORE INTO `caixa` (`id`,`saldo_inicial`,`total_vendas`,`status`,`usuario_abertura`,`created_by`) VALUES
(4, 200.00, 0.00, 'aberto', 1, 1);

COMMIT;

-- ══════════════════════════════════════════════════════════════
-- FIM DA INSTALAÇÃO
-- Acesse: http://seuservidor/ossystem/
-- admin@os-system.com / (senha configurada no sistema)
-- gerente/mecanico/caixa/vendedor@os-system.com / password
-- ══════════════════════════════════════════════════════════════
