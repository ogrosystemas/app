-- migration_crm.sql
-- Tabela de CRM de compradores

CREATE TABLE IF NOT EXISTS customers (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    meli_user_id     VARCHAR(30)   NULL,
    nickname         VARCHAR(100)  NOT NULL,
    first_name       VARCHAR(80)   NULL,
    last_name        VARCHAR(80)   NULL,
    email            VARCHAR(150)  NULL,
    phone            VARCHAR(30)   NULL,
    city             VARCHAR(80)   NULL,
    state            VARCHAR(30)   NULL,
    zip              VARCHAR(15)   NULL,
    tags             JSON          NULL,         -- ['vip','recorrente','problemático']
    notes            TEXT          NULL,         -- notas internas
    status           ENUM('ativo','inativo','bloqueado','vip') NOT NULL DEFAULT 'ativo',
    total_orders     INT           NOT NULL DEFAULT 0,
    total_spent      DECIMAL(14,2) NOT NULL DEFAULT 0,
    avg_ticket       DECIMAL(12,2) NOT NULL DEFAULT 0,
    last_order_at    DATETIME      NULL,
    first_order_at   DATETIME      NULL,
    has_complaints   TINYINT       NOT NULL DEFAULT 0,
    complaint_count  INT           NOT NULL DEFAULT 0,
    rating_given     DECIMAL(3,1)  NULL,         -- média de avaliações que deu
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_customer (tenant_id, nickname),
    KEY idx_meli_id (tenant_id, meli_user_id),
    KEY idx_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissão CRM nos usuários
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS can_access_crm TINYINT NOT NULL DEFAULT 1 AFTER can_access_sac;

-- Sincroniza compradores existentes a partir das orders
INSERT IGNORE INTO customers (
    id, tenant_id, meli_user_id, nickname, first_name, last_name, email,
    city, state, zip, total_orders, total_spent, avg_ticket, last_order_at, first_order_at
)
SELECT
    UUID(),
    o.tenant_id,
    MAX(o.buyer_meli_id),
    o.buyer_nickname,
    MAX(o.buyer_first_name),
    MAX(o.buyer_last_name),
    MAX(o.buyer_email),
    MAX(o.ship_city),
    MAX(o.ship_state),
    MAX(o.ship_zip),
    COUNT(DISTINCT o.id),
    SUM(o.total_amount),
    AVG(o.total_amount),
    MAX(o.order_date),
    MIN(o.order_date)
FROM orders o
WHERE o.payment_status IN ('approved','APPROVED')
  AND o.buyer_nickname IS NOT NULL
GROUP BY o.tenant_id, o.buyer_nickname
ON DUPLICATE KEY UPDATE
    total_orders   = VALUES(total_orders),
    total_spent    = VALUES(total_spent),
    avg_ticket     = VALUES(avg_ticket),
    last_order_at  = VALUES(last_order_at),
    first_order_at = VALUES(first_order_at);
