-- ============================================================
-- PATCH SQL v2 — OS-System
-- Execute APÓS o ossystem.sql e database_patch.sql (v1)
-- ============================================================

USE `ossystem`;

-- ──────────────────────────────────────────────────────────────
-- 1) Tabela de categorias de produtos
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categorias_produtos` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(100) NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `cor`       VARCHAR(7)   DEFAULT '#f59e0b' COMMENT 'Hex color for UI badge',
  `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorias padrão para oficina de motos
INSERT IGNORE INTO `categorias_produtos` (`id`, `nome`, `cor`) VALUES
(1,  'Óleo e Lubrificantes',  '#f59e0b'),
(2,  'Filtros',               '#22c55e'),
(3,  'Freios',                '#ef4444'),
(4,  'Pneus e Rodas',         '#3b82f6'),
(5,  'Elétrica',              '#a855f7'),
(6,  'Motor',                 '#f97316'),
(7,  'Transmissão',           '#06b6d4'),
(8,  'Escapamento',           '#64748b'),
(9,  'Carroceria e Acessórios','#ec4899'),
(10, 'Parafusos e Fixadores', '#84cc16'),
(11, 'Baterias',              '#eab308'),
(12, 'Outros',                '#94a3b8');

-- ──────────────────────────────────────────────────────────────
-- 2) Adicionar categoria_id e exibir_pdv em produtos
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `produtos`
  ADD COLUMN IF NOT EXISTS `categoria_id` INT(11) DEFAULT NULL AFTER `unidade`,
  ADD COLUMN IF NOT EXISTS `exibir_pdv`   TINYINT(1) NOT NULL DEFAULT 1 AFTER `categoria_id`,
  ADD COLUMN IF NOT EXISTS `ativo`        TINYINT(1) NOT NULL DEFAULT 1 AFTER `exibir_pdv`;

-- Todos os produtos existentes ficam ativos e exibidos no PDV
UPDATE `produtos` SET `ativo` = 1, `exibir_pdv` = 1 WHERE `ativo` IS NULL OR `ativo` = 0;

-- Índice para categoria
ALTER TABLE `produtos` ADD INDEX IF NOT EXISTS `idx_categoria` (`categoria_id`);
ALTER TABLE `produtos` ADD INDEX IF NOT EXISTS `idx_pdv`       (`exibir_pdv`, `ativo`);

-- Relacionamento (soft - sem FK para não quebrar instâncias antigas)
-- ALTER TABLE `produtos` ADD CONSTRAINT `fk_produto_categoria`
--   FOREIGN KEY (`categoria_id`) REFERENCES `categorias_produtos`(`id`) ON DELETE SET NULL;

-- ──────────────────────────────────────────────────────────────
-- 3) Garantir colunas já previstas no patch v1 (idempotente)
-- ──────────────────────────────────────────────────────────────

-- Clientes
ALTER TABLE `clientes`
  ADD COLUMN IF NOT EXISTS `tipo`         ENUM('pf','pj') NOT NULL DEFAULT 'pf' AFTER `id`,
  ADD COLUMN IF NOT EXISTS `celular`      VARCHAR(20)  DEFAULT NULL AFTER `telefone`,
  ADD COLUMN IF NOT EXISTS `cep`         VARCHAR(10)  DEFAULT NULL AFTER `endereco`,
  ADD COLUMN IF NOT EXISTS `numero`      VARCHAR(10)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `complemento` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `bairro`      VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cidade`      VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `estado`      CHAR(2)      DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rg_ie`       VARCHAR(30)  DEFAULT NULL AFTER `cpf_cnpj`,
  ADD COLUMN IF NOT EXISTS `observacoes` TEXT         DEFAULT NULL;

-- Motos
ALTER TABLE `motos`
  ADD COLUMN IF NOT EXISTS `cilindrada`  VARCHAR(10)  DEFAULT NULL AFTER `ano`,
  ADD COLUMN IF NOT EXISTS `km_atual`    INT UNSIGNED DEFAULT 0   AFTER `cilindrada`,
  ADD COLUMN IF NOT EXISTS `combustivel` ENUM('gasolina','alcool','flex','eletrico') DEFAULT 'gasolina' AFTER `km_atual`,
  ADD COLUMN IF NOT EXISTS `observacoes` TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `ativo`       TINYINT(1)   DEFAULT 1;

-- Serviços (garantir campo preco/valor e garantia)
ALTER TABLE `servicos`
  ADD COLUMN IF NOT EXISTS `garantia_dias`  INT DEFAULT 30  AFTER `tempo_estimado`,
  ADD COLUMN IF NOT EXISTS `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Ordens de serviço
ALTER TABLE `ordens_servico`
  ADD COLUMN IF NOT EXISTS `km_entrada`      INT UNSIGNED DEFAULT NULL AFTER `moto_id`,
  ADD COLUMN IF NOT EXISTS `prioridade`      ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
  ADD COLUMN IF NOT EXISTS `total_servicos`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `total_produtos`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `total_geral`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `desconto`        DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Caixa
ALTER TABLE `caixa`
  ADD COLUMN IF NOT EXISTS `created_by` INT(11) DEFAULT NULL;
UPDATE `caixa` SET `created_by` = `usuario_abertura` WHERE `created_by` IS NULL;

-- ──────────────────────────────────────────────────────────────
-- 4) Índices de performance
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `produtos`    ADD INDEX IF NOT EXISTS `idx_codigo_barras` (`codigo_barras`(50));
ALTER TABLE `clientes`    ADD INDEX IF NOT EXISTS `idx_nome_cliente`   (`nome`(50));
ALTER TABLE `motos`       ADD INDEX IF NOT EXISTS `idx_placa`          (`placa`);
ALTER TABLE `vendas`      ADD INDEX IF NOT EXISTS `idx_data_venda`     (`data_venda`);


-- ──────────────────────────────────────────────────────────────
-- Extra: ampliar numero_venda para evitar truncamento
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `vendas` MODIFY COLUMN `numero_venda` VARCHAR(30) NOT NULL;

-- ──────────────────────────────────────────────────────────────
-- FIM DO PATCH v2
-- ──────────────────────────────────────────────────────────────
