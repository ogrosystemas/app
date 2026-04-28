-- migration_kits.sql

CREATE TABLE IF NOT EXISTS kits (
    id              VARCHAR(36)   NOT NULL,
    tenant_id       VARCHAR(36)   NOT NULL,
    meli_account_id VARCHAR(36)   NULL,
    title           VARCHAR(255)  NOT NULL,
    description     TEXT          NULL,
    price           DECIMAL(12,2) NOT NULL DEFAULT 0,   -- preço de venda do kit
    cost_price      DECIMAL(12,2) NOT NULL DEFAULT 0,   -- custo total calculado
    discount_pct    DECIMAL(5,2)  NOT NULL DEFAULT 0,   -- desconto aplicado sobre soma dos componentes
    ml_fee_percent  DECIMAL(5,2)  NOT NULL DEFAULT 14,
    meli_item_id    VARCHAR(30)   NULL,                 -- ID do anúncio no ML (se publicado)
    ml_status       VARCHAR(20)   NULL DEFAULT 'draft', -- draft|active|paused
    sku             VARCHAR(80)   NULL,
    status          ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kit_items (
    id          VARCHAR(36)   NOT NULL,
    kit_id      VARCHAR(36)   NOT NULL,
    product_id  VARCHAR(36)   NOT NULL,
    quantity    INT           NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_kit (kit_id),
    KEY idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garantir UNIQUE KEY em tenant_settings para ON DUPLICATE KEY UPDATE funcionar
ALTER TABLE tenant_settings
    ADD CONSTRAINT uk_tenant_key UNIQUE KEY (tenant_id, `key`)
    IGNORE;

-- IPI por produto
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS ipi_valor DECIMAL(12,2) NOT NULL DEFAULT 0
    AFTER ml_fee_percent;
