-- ============================================================
-- migration_products_account.sql
-- 1. Adiciona meli_account_id na tabela products
-- 2. Adiciona meli_account_id na tabela financial_entries
-- Popula dados demo corretamente.
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < database/migration_products_account.sql
-- ============================================================

-- ── Normaliza collation de todas as tabelas para utf8mb4_unicode_ci ──────────
-- Necessário quando tabelas foram criadas com collations diferentes
ALTER TABLE products          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE financial_entries CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orders            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE meli_accounts     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tenants           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sac_messages      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sac_conversations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE transactions      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE queue_jobs        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE audit_logs        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE order_items       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE bank_accounts     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE chart_of_accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tenant_settings   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE login_attempts    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ── bank_accounts ────────────────────────────────────────────
ALTER TABLE bank_accounts
    ADD COLUMN IF NOT EXISTS meli_account_id VARCHAR(36) NULL AFTER tenant_id,
    ADD INDEX  IF NOT EXISTS idx_bank_acct (tenant_id, meli_account_id);

-- Popula contas bancárias demo com a conta LOJA_ML_DEMO
UPDATE bank_accounts
SET meli_account_id = 'meli-acc-0001-0000-000000000001'
WHERE tenant_id = 'tenant-demo-0001-0000-000000000001'
  AND meli_account_id IS NULL;

-- Para tenants com uma única conta ML ativa: associa automaticamente
UPDATE bank_accounts ba
JOIN (
    SELECT tenant_id, MIN(id) AS acct_id
    FROM meli_accounts
    WHERE is_active = 1
    GROUP BY tenant_id
    HAVING COUNT(*) = 1
) sole ON sole.tenant_id = ba.tenant_id
SET ba.meli_account_id = sole.acct_id
WHERE ba.meli_account_id IS NULL;

-- ── products ─────────────────────────────────────────────────
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS meli_account_id VARCHAR(36) NULL AFTER tenant_id,
    ADD INDEX  IF NOT EXISTS idx_products_acct (tenant_id, meli_account_id);

-- Popula produtos demo
UPDATE products
SET meli_account_id = 'meli-acc-0001-0000-000000000001'
WHERE tenant_id = 'tenant-demo-0001-0000-000000000001'
  AND meli_account_id IS NULL;

-- Para tenants reais: infere pela conta mais frequente nos orders
UPDATE products p
JOIN (
    SELECT o.tenant_id, o.meli_account_id, COUNT(*) AS cnt
    FROM orders o
    WHERE o.meli_account_id IS NOT NULL
    GROUP BY o.tenant_id, o.meli_account_id
    ORDER BY cnt DESC
) best ON best.tenant_id = p.tenant_id
SET p.meli_account_id = best.meli_account_id
WHERE p.meli_account_id IS NULL
  AND best.meli_account_id IS NOT NULL;

-- ── financial_entries ────────────────────────────────────────
ALTER TABLE financial_entries
    ADD COLUMN IF NOT EXISTS meli_account_id VARCHAR(36) NULL AFTER tenant_id,
    ADD INDEX  IF NOT EXISTS idx_fin_acct (tenant_id, meli_account_id);

-- Popula entradas demo com a conta LOJA_ML_DEMO
UPDATE financial_entries
SET meli_account_id = 'meli-acc-0001-0000-000000000001'
WHERE tenant_id = 'tenant-demo-0001-0000-000000000001'
  AND meli_account_id IS NULL;

-- Para tenants reais: entradas vinculadas a orders herdam a conta
UPDATE financial_entries fe
JOIN orders o ON o.id = fe.order_id AND o.tenant_id = fe.tenant_id
SET fe.meli_account_id = o.meli_account_id
WHERE fe.meli_account_id IS NULL
  AND fe.order_id IS NOT NULL;

-- Entradas manuais sem order_id: conta única do tenant (se tiver só uma)
UPDATE financial_entries fe
JOIN (
    SELECT tenant_id, MIN(id) AS acct_id
    FROM meli_accounts
    WHERE is_active = 1
    GROUP BY tenant_id
    HAVING COUNT(*) = 1
) sole ON sole.tenant_id = fe.tenant_id
SET fe.meli_account_id = sole.acct_id
WHERE fe.meli_account_id IS NULL;

SELECT 'migration_products_account.sql OK' AS status;
SELECT COUNT(*) AS produtos_com_conta   FROM products           WHERE meli_account_id IS NOT NULL;
SELECT COUNT(*) AS produtos_sem_conta   FROM products           WHERE meli_account_id IS NULL;
SELECT COUNT(*) AS fin_com_conta        FROM financial_entries  WHERE meli_account_id IS NOT NULL;
SELECT COUNT(*) AS fin_sem_conta        FROM financial_entries  WHERE meli_account_id IS NULL;

-- ── Campos de licença na tabela tenants (adicionados em v3) ──
ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS license_status ENUM('TRIAL','ACTIVE','EXPIRED','BLOCKED') NOT NULL DEFAULT 'TRIAL',
    ADD COLUMN IF NOT EXISTS license_expiry DATETIME NULL,
    ADD COLUMN IF NOT EXISTS license_key    VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS trial_started  DATETIME NULL,
    ADD COLUMN IF NOT EXISTS activated_at   DATETIME NULL;

-- Inicializa trial para tenants existentes que ainda não têm licença
UPDATE tenants
SET trial_started  = COALESCE(trial_started, created_at),
    license_expiry = COALESCE(license_expiry, DATE_ADD(COALESCE(created_at, NOW()), INTERVAL 15 DAY))
WHERE license_status = 'TRIAL' AND license_expiry IS NULL;

SELECT 'Campos de licença adicionados em tenants' AS status;
