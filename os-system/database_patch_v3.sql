-- ============================================================
-- PATCH SQL v3 — OS-System
-- Execute APÓS o ossystem.sql, database_patch.sql e database_patch_v2.sql
-- Módulo: Relatório Técnico (Laudo de Oficina de Moto)
-- ============================================================

USE `ossystem`;

-- ──────────────────────────────────────────────────────────────
-- 1) Tabela principal do laudo técnico
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `laudos_tecnicos` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `os_id`             INT(11)       NOT NULL,
  `tipo_manutencao`   ENUM('preventiva','corretiva') NOT NULL DEFAULT 'corretiva',
  `objetivo`          TEXT          DEFAULT NULL,
  `km_revisao`        INT UNSIGNED  DEFAULT NULL,
  `conclusao_tecnica` TEXT          DEFAULT NULL,
  `status_veiculo`    ENUM('apta','em_revisao','aguardando_pecas','inapta') NOT NULL DEFAULT 'apta',
  `created_by`        INT(11)       DEFAULT NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_os_laudo` (`os_id`),
  CONSTRAINT `fk_laudo_os` FOREIGN KEY (`os_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_laudo_usuario` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 2) Tabela de itens dinâmicos por seção do laudo
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `laudo_secoes` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `laudo_id`    INT(11)      NOT NULL,
  `secao`       TINYINT(4)   NOT NULL COMMENT '1=Motor/Lubrificação 2=Arrefecimento 3=Alimentação 4=Transmissão 5=Freios 6=Rodas/Vedações 7=Suspensão/Direção 8=Comandos 9=Serviços Complementares',
  `item`        TEXT         NOT NULL,
  `resultado`   ENUM('ok','atencao','critico','substituido','nao_aplicavel') NOT NULL DEFAULT 'ok',
  `observacao`  TEXT         DEFAULT NULL,
  `ordem`       TINYINT(4)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_laudo_secao` (`laudo_id`, `secao`),
  CONSTRAINT `fk_secao_laudo` FOREIGN KEY (`laudo_id`) REFERENCES `laudos_tecnicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 3) Ampliar colunas caso a tabela já exista com VARCHAR
--    (seguro rodar mesmo se já for TEXT — não faz nada)
-- ──────────────────────────────────────────────────────────────
ALTER TABLE `laudo_secoes`
  MODIFY COLUMN `item`       TEXT NOT NULL,
  MODIFY COLUMN `observacao` TEXT DEFAULT NULL;
