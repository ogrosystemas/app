-- ============================================================
-- migration_renovacoes_log.sql
-- Tabela de log do processo automático de renovação de anúncios
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < database/migration_renovacoes_log.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS renovacoes_log (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    meli_account_id  VARCHAR(36)   NULL,
    product_id       VARCHAR(36)   NOT NULL,
    product_title    VARCHAR(255)  NULL,
    old_item_id      VARCHAR(30)   NULL,
    new_item_id      VARCHAR(30)   NULL,
    dias_ativo       INT           NULL,
    status           ENUM('SUCCESS','FAILED','SKIPPED') NOT NULL DEFAULT 'FAILED',
    error_message    TEXT          NULL,
    gemini_changes   JSON          NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant   (tenant_id),
    KEY idx_status   (tenant_id, status),
    KEY idx_date     (tenant_id, created_at),
    KEY idx_product  (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'migration_renovacoes_log.sql OK' AS status;
