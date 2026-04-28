-- ============================================================
-- migration_questions.sql
-- Cria tabela de cache local de perguntas pré-venda.
-- Elimina dependência de chamadas em tempo real à API ML
-- na página de Perguntas, resolvendo travamento de navegação.
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < database/migration_questions.sql
-- ============================================================

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

SELECT 'migration_questions.sql OK' AS status;
SELECT COUNT(*) AS perguntas_em_cache FROM questions;
