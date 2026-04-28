-- migration_autoparts.sql

CREATE TABLE IF NOT EXISTS autoparts (
    id              VARCHAR(36)   NOT NULL,
    tenant_id       VARCHAR(36)   NOT NULL,
    product_id      VARCHAR(36)   NOT NULL,       -- FK products.id
    oem_code        VARCHAR(80)   NULL,            -- código OEM / referência original
    part_number     VARCHAR(80)   NULL,            -- código interno do fabricante da peça
    brand           VARCHAR(80)   NULL,            -- marca da peça (Monroe, Cofap, etc.)
    position        VARCHAR(30)   NULL,            -- dianteiro/traseiro
    side            VARCHAR(20)   NULL,            -- esquerdo/direito/ambos
    condition_part  ENUM('novo','remontado','original_usado') NOT NULL DEFAULT 'novo',
    notes           TEXT          NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_product (tenant_id, product_id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoparts_compatibility (
    id          VARCHAR(36)   NOT NULL,
    autopart_id VARCHAR(36)   NOT NULL,
    tenant_id   VARCHAR(36)   NOT NULL,
    brand       VARCHAR(60)   NOT NULL,   -- ex: Volkswagen
    model       VARCHAR(100)  NOT NULL,   -- ex: Gol
    year_from   SMALLINT      NOT NULL,   -- ex: 2010
    year_to     SMALLINT      NOT NULL,   -- ex: 2020
    engine      VARCHAR(60)   NULL,       -- ex: 1.0 Flex
    fipe_code   VARCHAR(20)   NULL,
    PRIMARY KEY (id),
    KEY idx_autopart (autopart_id),
    KEY idx_tenant (tenant_id),
    KEY idx_brand_model (brand, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
