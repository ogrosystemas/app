-- ============================================================
-- PATCH SQL — Correções e campos faltantes
-- Execute após o ossystem.sql principal
-- ============================================================

USE `ossystem`;

-- ──────────────────────────────────────────────────────────────
-- 1) Tabela clientes — adicionar campos de endereço detalhado
--    e tipo (PF/PJ) que existem nos módulos PHP mas faltam no schema
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `clientes`
  ADD COLUMN IF NOT EXISTS `tipo`             ENUM('pf','pj') NOT NULL DEFAULT 'pf' AFTER `id`,
  ADD COLUMN IF NOT EXISTS `celular`          VARCHAR(20)  DEFAULT NULL AFTER `telefone`,
  ADD COLUMN IF NOT EXISTS `cep`              VARCHAR(10)  DEFAULT NULL AFTER `endereco`,
  ADD COLUMN IF NOT EXISTS `numero`           VARCHAR(10)  DEFAULT NULL AFTER `cep`,
  ADD COLUMN IF NOT EXISTS `complemento`      VARCHAR(100) DEFAULT NULL AFTER `numero`,
  ADD COLUMN IF NOT EXISTS `bairro`           VARCHAR(100) DEFAULT NULL AFTER `complemento`,
  ADD COLUMN IF NOT EXISTS `cidade`           VARCHAR(100) DEFAULT NULL AFTER `bairro`,
  ADD COLUMN IF NOT EXISTS `estado`           CHAR(2)      DEFAULT NULL AFTER `cidade`,
  ADD COLUMN IF NOT EXISTS `rg_ie`            VARCHAR(30)  DEFAULT NULL AFTER `cpf_cnpj`,
  ADD COLUMN IF NOT EXISTS `observacoes`      TEXT         DEFAULT NULL AFTER `estado`;

-- ──────────────────────────────────────────────────────────────
-- 2) Tabela motos — campos extras usados no clientes.php
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `motos`
  ADD COLUMN IF NOT EXISTS `cilindrada`       VARCHAR(10)  DEFAULT NULL AFTER `ano`,
  ADD COLUMN IF NOT EXISTS `km_atual`         INT UNSIGNED DEFAULT 0   AFTER `cilindrada`,
  ADD COLUMN IF NOT EXISTS `combustivel`      ENUM('gasolina','alcool','flex','eletrico') DEFAULT 'gasolina' AFTER `km_atual`,
  ADD COLUMN IF NOT EXISTS `observacoes`      TEXT         DEFAULT NULL AFTER `combustivel`,
  ADD COLUMN IF NOT EXISTS `ativo`            TINYINT(1)   DEFAULT 1   AFTER `observacoes`;

-- ──────────────────────────────────────────────────────────────
-- 3) Tabela produtos — campos extras
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `produtos`
  ADD COLUMN IF NOT EXISTS `ncm`              VARCHAR(10)  DEFAULT NULL AFTER `codigo_barras`,
  ADD COLUMN IF NOT EXISTS `localizacao`      VARCHAR(50)  DEFAULT NULL AFTER `ncm`,
  ADD COLUMN IF NOT EXISTS `ativo`            TINYINT(1)   DEFAULT 1   AFTER `localizacao`,
  ADD COLUMN IF NOT EXISTS `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ──────────────────────────────────────────────────────────────
-- 4) Tabela ordens_servico — campos ausentes usados no código
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `ordens_servico`
  ADD COLUMN IF NOT EXISTS `km_entrada`       INT UNSIGNED DEFAULT NULL AFTER `moto_id`,
  ADD COLUMN IF NOT EXISTS `km_saida`         INT UNSIGNED DEFAULT NULL AFTER `km_entrada`,
  ADD COLUMN IF NOT EXISTS `prioridade`       ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `mecanico_id`      INT(11)      DEFAULT NULL AFTER `prioridade`,
  ADD COLUMN IF NOT EXISTS `total_servicos`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `total_produtos`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `total_geral`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `desconto`         DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `total_geral`,
  ADD COLUMN IF NOT EXISTS `problema_relatado` TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `diagnostico`       TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `data_entrega`      DATETIME     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ──────────────────────────────────────────────────────────────
-- 5) Tabela caixa — coluna created_by que o pdv.php usa
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `caixa`
  ADD COLUMN IF NOT EXISTS `created_by`      INT(11) DEFAULT NULL AFTER `usuario_fechamento`;

-- Compatibilidade: o código usa caixa.usuario_abertura mas pdv.php filtra por created_by
UPDATE `caixa` SET `created_by` = `usuario_abertura` WHERE `created_by` IS NULL;

-- ──────────────────────────────────────────────────────────────
-- 6) Tabela vendas — coluna status já existe mas precisa de status padrão
-- ──────────────────────────────────────────────────────────────
-- (nenhuma alteração necessária — estrutura já correta)

-- ──────────────────────────────────────────────────────────────
-- 7) Tabela fornecedores — campos que aparecem em telas mas faltam
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `fornecedores`
  ADD COLUMN IF NOT EXISTS `contato`         VARCHAR(100) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `ativo`           TINYINT(1)   DEFAULT 1   AFTER `contato`,
  ADD COLUMN IF NOT EXISTS `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ──────────────────────────────────────────────────────────────
-- 8) Tabela os_servicos — campo garantia_dias para PDF
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `os_servicos`
  ADD COLUMN IF NOT EXISTS `garantia_dias`   INT DEFAULT 0 AFTER `mecanico_id`,
  ADD COLUMN IF NOT EXISTS `tempo_gasto`     INT DEFAULT NULL COMMENT 'minutos' AFTER `garantia_dias`;

-- ──────────────────────────────────────────────────────────────
-- 9) Tabela servicos — campo garantia_dias
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `servicos`
  ADD COLUMN IF NOT EXISTS `garantia_dias`   INT DEFAULT 0 AFTER `preco`,
  ADD COLUMN IF NOT EXISTS `tempo_estimado`  INT DEFAULT NULL COMMENT 'minutos' AFTER `garantia_dias`,
  ADD COLUMN IF NOT EXISTS `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ──────────────────────────────────────────────────────────────
-- 10) Tabela movimentacoes_estoque — coluna custo_unitario
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `movimentacoes_estoque`
  ADD COLUMN IF NOT EXISTS `custo_unitario`  DECIMAL(10,2) DEFAULT NULL AFTER `quantidade`;

-- ──────────────────────────────────────────────────────────────
-- 11) Índices úteis (melhoram performance das buscas)
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `produtos`    ADD INDEX IF NOT EXISTS `idx_codigo_barras` (`codigo_barras`);
ALTER TABLE `clientes`    ADD INDEX IF NOT EXISTS `idx_cpf_cnpj`      (`cpf_cnpj`(20));
ALTER TABLE `motos`       ADD INDEX IF NOT EXISTS `idx_placa`         (`placa`);
ALTER TABLE `vendas`      ADD INDEX IF NOT EXISTS `idx_data_venda`    (`data_venda`);

-- ──────────────────────────────────────────────────────────────
-- FIM DO PATCH
-- ──────────────────────────────────────────────────────────────
