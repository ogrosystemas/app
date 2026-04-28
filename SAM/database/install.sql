-- ============================================================
-- SAM - Sistema de Acompanhamento Mercado Livre — Instalação Limpa
-- Versão: 2.0 | Data: 2026-04-24
--
-- Este arquivo cria toda a estrutura do banco para instalação
-- em ambiente novo (sem dados de demo).
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < /home/www/lupa/database/install.sql
--
-- Para carregar a demo após instalar:
--   mysql -u lupa_user -pLupa2026 lupa_erp < /home/www/lupa/database/demo_activate.sql
--
-- Para criar o primeiro cliente:
--   /usr/local/lsws/lsphp83/bin/php8.3 /home/www/lupa/database/entregar_cliente.php 2>/dev/null
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ────────────────────────────────────────────────────────────
-- 1. SCHEMA PRINCIPAL
-- ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS tenants (
    id             VARCHAR(36)  NOT NULL,
    name           VARCHAR(100) NOT NULL,
    plan           ENUM('FREE','STARTER','PRO','ENTERPRISE') NOT NULL DEFAULT 'STARTER',
    is_active      TINYINT      NOT NULL DEFAULT 1,
    license_status ENUM('TRIAL','ACTIVE','EXPIRED','BLOCKED') NOT NULL DEFAULT 'TRIAL',
    license_expiry DATETIME     NULL,
    license_key    VARCHAR(255) NULL,
    trial_started  DATETIME     NULL,
    activated_at   DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id                    VARCHAR(36)  NOT NULL,
    tenant_id             VARCHAR(36)  NOT NULL,
    name                  VARCHAR(100) NOT NULL,
    email                 VARCHAR(150) NOT NULL,
    password_hash         VARCHAR(255) NOT NULL,
    role                  ENUM('ADMIN','MANAGER','OPERATOR','VIEWER') NOT NULL DEFAULT 'OPERATOR',
    can_access_sac        TINYINT NOT NULL DEFAULT 1,
    can_access_anuncios   TINYINT NOT NULL DEFAULT 1,
    can_access_financeiro TINYINT NOT NULL DEFAULT 0,
    can_access_logistica  TINYINT NOT NULL DEFAULT 1,
    can_access_admin      TINYINT NOT NULL DEFAULT 0,
    is_active             TINYINT NOT NULL DEFAULT 1,
    last_login            DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_email (email),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meli_accounts (
    id                 VARCHAR(36)   NOT NULL,
    tenant_id          VARCHAR(36)   NOT NULL,
    meli_user_id       VARCHAR(30)   NOT NULL,
    nickname           VARCHAR(100)  NULL,
    email              VARCHAR(150)  NULL,
    access_token_enc   TEXT          NULL,
    refresh_token_enc  TEXT          NULL,
    token_expires_at   DATETIME      NULL,
    reputation_level   VARCHAR(30)   NULL,
    sales_score        INT           NULL,
    is_active          TINYINT       NOT NULL DEFAULT 1,
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    meli_account_id  VARCHAR(36)   NULL,
    meli_question_id VARCHAR(30)   NOT NULL,
    meli_item_id     VARCHAR(30)   NULL,
    item_title       VARCHAR(255)  NULL,
    buyer_nickname   VARCHAR(100)  NULL,
    buyer_meli_id    VARCHAR(30)   NULL,
    question_text    TEXT          NOT NULL,
    status           ENUM('UNANSWERED','ANSWERED','CLOSED') NOT NULL DEFAULT 'UNANSWERED',
    answer_text      TEXT          NULL,
    answer_by_robot  TINYINT       NOT NULL DEFAULT 0,
    answered_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_meli_question (meli_question_id),
    KEY idx_tenant (tenant_id),
    KEY idx_status (tenant_id, status),
    KEY idx_item (meli_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    meli_account_id  VARCHAR(36)   NULL,
    meli_item_id     VARCHAR(30)   NOT NULL,
    sku              VARCHAR(80)   NULL,
    title            VARCHAR(255)  NOT NULL,
    price            DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost_price       DECIMAL(12,2) NOT NULL DEFAULT 0,
    ml_fee_percent   DECIMAL(5,2)  NOT NULL DEFAULT 0,
    stock_quantity   INT           NOT NULL DEFAULT 0,
    stock_min        INT           NOT NULL DEFAULT 0,
    ml_status        VARCHAR(20)   NULL DEFAULT 'ACTIVE',
    ml_health        INT           NULL,
    ml_conversion    DECIMAL(5,2)  NULL,
    ml_visits        INT           NULL DEFAULT 0,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_item (meli_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    meli_account_id  VARCHAR(36)   NULL,
    meli_order_id    VARCHAR(30)   NOT NULL,
    meli_shipment_id VARCHAR(30)   NULL,
    status           VARCHAR(30)   NOT NULL DEFAULT 'confirmed',
    buyer_meli_id    VARCHAR(30)   NULL,
    buyer_nickname   VARCHAR(100)  NULL,
    buyer_first_name VARCHAR(80)   NULL,
    buyer_last_name  VARCHAR(80)   NULL,
    buyer_email      VARCHAR(150)  NULL,
    ship_street      VARCHAR(150)  NULL,
    ship_city        VARCHAR(80)   NULL,
    ship_state       VARCHAR(30)   NULL,
    ship_zip         VARCHAR(15)   NULL,
    total_amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
    ml_fee_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status   VARCHAR(30)   NULL DEFAULT 'pending',
    ship_status      VARCHAR(30)   NULL DEFAULT 'pending',
    pdf_printed      TINYINT       NOT NULL DEFAULT 0,
    zpl_printed      TINYINT       NOT NULL DEFAULT 0,
    label_printed    TINYINT       NOT NULL DEFAULT 0,
    has_mediacao     TINYINT       NOT NULL DEFAULT 0,
    nf_number        VARCHAR(30)   NULL,
    nf_key           VARCHAR(50)   NULL,
    nf_path          VARCHAR(255)  NULL,
    nf_fetched_at    DATETIME      NULL,
    idempotency_key  VARCHAR(50)   NULL,
    order_date       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_meli_order (meli_order_id),
    KEY idx_tenant (tenant_id),
    KEY idx_ship (meli_shipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id          VARCHAR(36)   NOT NULL,
    order_id    VARCHAR(36)   NOT NULL,
    product_id  VARCHAR(36)   NULL,
    meli_item_id VARCHAR(30)  NULL,
    title       VARCHAR(255)  NOT NULL,
    quantity    INT           NOT NULL DEFAULT 1,
    unit_price  DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    sku         VARCHAR(80)   NULL,
    tenant_id   VARCHAR(36)   NULL,
    PRIMARY KEY (id),
    KEY idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sac_messages (
    id               VARCHAR(36)  NOT NULL,
    tenant_id        VARCHAR(36)  NOT NULL,
    meli_account_id  VARCHAR(36)  NULL,
    order_id         VARCHAR(36)  NULL,
    meli_pack_id     VARCHAR(30)  NULL,
    meli_message_id  VARCHAR(50)  NULL,
    from_role        ENUM('BUYER','SELLER','ML') NOT NULL DEFAULT 'BUYER',
    from_nickname    VARCHAR(100) NULL,
    from_meli_id     VARCHAR(30)  NULL,
    message_text     TEXT         NULL,
    sentiment_score  DECIMAL(4,3) NULL,
    sentiment_label  VARCHAR(30)  NULL,
    is_read          TINYINT      NOT NULL DEFAULT 0,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_meli_msg (meli_message_id),
    KEY idx_tenant (tenant_id),
    KEY idx_order (order_id),
    KEY idx_unread (tenant_id, is_read, from_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sac_conversations (
    id         VARCHAR(36) NOT NULL,
    tenant_id  VARCHAR(36) NOT NULL,
    order_id   VARCHAR(36) NOT NULL,
    status     ENUM('OPEN','WAITING','RESOLVED') NOT NULL DEFAULT 'OPEN',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
    id             VARCHAR(36)   NOT NULL,
    tenant_id      VARCHAR(36)   NOT NULL,
    meli_account_id VARCHAR(36)  NULL,
    order_id       VARCHAR(36)   NULL,
    type           VARCHAR(30)   NOT NULL DEFAULT 'SALE',
    category       VARCHAR(50)   NOT NULL DEFAULT 'REVENUE',
    description    VARCHAR(255)  NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    direction      ENUM('CREDIT','DEBIT') NOT NULL,
    dre_category   VARCHAR(50)   NULL,
    reference_date DATE          NOT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant_date (tenant_id, reference_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_entries (
    id              VARCHAR(36)   NOT NULL,
    tenant_id       VARCHAR(36)   NOT NULL,
    meli_account_id VARCHAR(36)   NULL,
    account_id      VARCHAR(36)   NULL,
    coa_id          VARCHAR(36)   NULL,
    direction       ENUM('CREDIT','DEBIT') NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    description     VARCHAR(255)  NOT NULL,
    entry_date      DATE          NOT NULL,
    due_date        DATE          NULL,
    paid_date       DATE          NULL,
    status          ENUM('PENDING','PAID','CANCELLED','OVERDUE') NOT NULL DEFAULT 'PENDING',
    dre_category    VARCHAR(50)   NULL,
    is_recurring    TINYINT       NOT NULL DEFAULT 0,
    recurrence_rule VARCHAR(50)   NULL,
    notes           TEXT          NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant_date (tenant_id, entry_date),
    KEY idx_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_accounts (
    id              VARCHAR(36)   NOT NULL,
    tenant_id       VARCHAR(36)   NOT NULL,
    meli_account_id VARCHAR(36)   NULL,
    name            VARCHAR(100)  NOT NULL,
    type            ENUM('CORRENTE','POUPANCA','CAIXA','CARTAO_CREDITO','INVESTIMENTO') NOT NULL DEFAULT 'CORRENTE',
    bank_name       VARCHAR(80)   NULL,
    balance         DECIMAL(12,2) NOT NULL DEFAULT 0,
    is_active       TINYINT       NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id          VARCHAR(36)  NOT NULL,
    tenant_id   VARCHAR(36)  NOT NULL,
    code        VARCHAR(20)  NOT NULL,
    name        VARCHAR(100) NOT NULL,
    type        ENUM('RECEITA','CUSTO','DESPESA','ATIVO','PASSIVO') NOT NULL,
    subtype     VARCHAR(50)  NULL,
    dre_line    VARCHAR(50)  NULL,
    is_active   TINYINT      NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenant_settings (
    id        VARCHAR(36)   NOT NULL,
    tenant_id VARCHAR(36)   NOT NULL,
    key_name  VARCHAR(80)   NOT NULL,
    value     TEXT          NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_tenant_key (tenant_id, key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS queue_jobs (
    id              VARCHAR(36)   NOT NULL,
    tenant_id       VARCHAR(36)   NULL,
    meli_account_id VARCHAR(36)   NULL,
    job_type        VARCHAR(50)   NULL DEFAULT 'webhook',
    topic           VARCHAR(50)   NULL,
    resource        VARCHAR(255)  NULL,
    payload         LONGTEXT      NULL,
    status          ENUM('PENDING','PROCESSING','DONE','FAILED') NOT NULL DEFAULT 'PENDING',
    attempts        TINYINT       NOT NULL DEFAULT 0,
    error_msg       TEXT          NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id         VARCHAR(128) NOT NULL,
    tenant_id  VARCHAR(36)  NULL,
    user_id    VARCHAR(36)  NULL,
    data       LONGTEXT     NULL,
    ip_address VARCHAR(45)  NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 2. SEGURANÇA
-- ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS login_attempts (
    id         BIGINT       NOT NULL AUTO_INCREMENT,
    ip_address VARCHAR(45)  NOT NULL,
    email      VARCHAR(150) NOT NULL,
    success    TINYINT      NOT NULL DEFAULT 0,
    user_agent VARCHAR(200) NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_ip_created (ip_address, created_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGINT       NOT NULL AUTO_INCREMENT,
    tenant_id  VARCHAR(36)  NULL,
    user_id    VARCHAR(36)  NULL,
    action     VARCHAR(80)  NOT NULL,
    table_name VARCHAR(60)  NULL,
    record_id  VARCHAR(36)  NULL,
    old_data   JSON         NULL,
    new_data   JSON         NULL,
    ip_address VARCHAR(45)  NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 3. COLLATION — garante utf8mb4_unicode_ci em todas as tabelas
-- ────────────────────────────────────────────────────────────

ALTER TABLE tenants           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE meli_accounts     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orders            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE order_items       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sac_messages      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sac_conversations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE transactions      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE financial_entries CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE bank_accounts     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE chart_of_accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tenant_settings   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE queue_jobs        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sessions          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE login_attempts    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE audit_logs        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

SELECT 'install.sql executado com sucesso!' AS status;
SELECT COUNT(*) AS tabelas_criadas
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_type = 'BASE TABLE';

-- ── Sprint 1: Banco de respostas prontas ──────────────────
CREATE TABLE IF NOT EXISTS quick_replies (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    title            VARCHAR(100)  NOT NULL,
    body             TEXT          NOT NULL,
    context          ENUM('sac','perguntas','ambos') NOT NULL DEFAULT 'ambos',
    tags             VARCHAR(255)  NULL,
    uses_count       INT           NOT NULL DEFAULT 0,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_context (tenant_id, context)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sprint 1: Mensagens automáticas pós-venda ─────────────
CREATE TABLE IF NOT EXISTS auto_messages (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    name             VARCHAR(100)  NOT NULL,
    trigger_event    ENUM('payment_confirmed','order_shipped','order_delivered','feedback_received') NOT NULL,
    delay_hours      INT           NOT NULL DEFAULT 0,
    message_body     TEXT          NOT NULL,
    is_active        TINYINT       NOT NULL DEFAULT 1,
    sent_count       INT           NOT NULL DEFAULT 0,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_trigger (tenant_id, trigger_event, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sprint 1: Log de mensagens automáticas enviadas ───────
CREATE TABLE IF NOT EXISTS auto_messages_log (
    id               VARCHAR(36)   NOT NULL,
    tenant_id        VARCHAR(36)   NOT NULL,
    auto_message_id  VARCHAR(36)   NOT NULL,
    order_id         VARCHAR(36)   NOT NULL,
    meli_order_id    VARCHAR(30)   NULL,
    buyer_nickname   VARCHAR(100)  NULL,
    status           ENUM('SENT','FAILED','SKIPPED') NOT NULL DEFAULT 'SENT',
    error_message    TEXT          NULL,
    sent_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_msg_order (auto_message_id, order_id),
    KEY idx_tenant (tenant_id),
    KEY idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
