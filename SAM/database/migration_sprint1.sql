-- ============================================================
-- migration_sprint1.sql
-- Sprint 1: Banco de respostas prontas + Mensagens automáticas
-- USO: mysql -u lupa_user -pLupa2026 lupa_erp < database/migration_sprint1.sql
-- ============================================================

-- Banco de respostas prontas para perguntas pré-venda
CREATE TABLE IF NOT EXISTS quick_replies (
    id          VARCHAR(36)  NOT NULL,
    tenant_id   VARCHAR(36)  NOT NULL,
    title       VARCHAR(100) NOT NULL,
    body        TEXT         NOT NULL,
    tags        VARCHAR(255) NULL,
    uso         INT          NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagens automáticas pós-venda
CREATE TABLE IF NOT EXISTS auto_messages (
    id          VARCHAR(36)  NOT NULL,
    tenant_id   VARCHAR(36)  NOT NULL,
    trigger_event ENUM('payment_approved','shipped','delivered') NOT NULL,
    title       VARCHAR(100) NOT NULL,
    body        TEXT         NOT NULL,
    is_active   TINYINT      NOT NULL DEFAULT 1,
    delay_hours INT          NOT NULL DEFAULT 0,
    sent_count  INT          NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_event  (tenant_id, trigger_event, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de mensagens automáticas enviadas (evita duplicatas)
CREATE TABLE IF NOT EXISTS auto_messages_log (
    id              VARCHAR(36) NOT NULL,
    tenant_id       VARCHAR(36) NOT NULL,
    auto_message_id VARCHAR(36) NOT NULL,
    order_id        VARCHAR(36) NOT NULL,
    meli_order_id   VARCHAR(30) NOT NULL,
    sent_at         DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_msg_order (auto_message_id, order_id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'migration_sprint1.sql OK' AS status;
