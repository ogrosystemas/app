-- ============================================================
-- reset_para_cliente.sql
-- Zera todos os dados do banco mantendo apenas a estrutura
-- e cria um tenant + usuário admin limpo para o cliente.
--
-- QUANDO USAR:
--   Quando o cliente fechar negócio e o sistema sair da demo
--   para produção. Remove TODOS os dados demo e cria um
--   ambiente limpo com acesso inicial para o cliente configurar.
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < database/reset_para_cliente.sql
--
-- ⚠ ATENÇÃO: IRREVERSÍVEL. Faça backup antes se necessário:
--   mysqldump -u lupa_user -pLupa2026 lupa_erp > backup_antes_reset.sql
-- ============================================================

SET foreign_key_checks = 0;

-- ── 1. Limpa TODOS os dados de todas as tabelas ──────────────
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE login_attempts;
TRUNCATE TABLE queue_jobs;
TRUNCATE TABLE sac_conversations;
TRUNCATE TABLE sac_messages;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE transactions;
TRUNCATE TABLE financial_entries;
TRUNCATE TABLE bank_accounts;
TRUNCATE TABLE products;
TRUNCATE TABLE meli_accounts;
TRUNCATE TABLE chart_of_accounts;
TRUNCATE TABLE tenant_settings;
TRUNCATE TABLE sessions;
TRUNCATE TABLE users;
TRUNCATE TABLE tenants;

SET foreign_key_checks = 1;

-- ── 2. Cria o tenant do cliente ──────────────────────────────
-- ⚠ EDITE: troque 'Nome da Empresa do Cliente' pelo nome real
SET @tenant_id   = UUID();
SET @tenant_nome = 'Minha Empresa';  -- ← EDITE AQUI
SET @tenant_plan = 'PRO';

INSERT INTO tenants (id, name, plan, is_active, license_status, trial_started, license_expiry)
VALUES (
    @tenant_id,
    @tenant_nome,
    @tenant_plan,
    1,
    'TRIAL',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 15 DAY)
);

-- ── 3. Cria o usuário admin inicial ──────────────────────────
-- ⚠ EDITE: troque email e senha antes de entregar ao cliente
-- Senha padrão: Ogro@2026 (hash bcrypt abaixo)
-- Para gerar outro hash: php -r "echo password_hash('SuaSenha',PASSWORD_BCRYPT,['cost'=>12]);"
SET @user_id    = UUID();
SET @user_email = 'admin@minhaempresa.com.br';  -- ← EDITE AQUI
SET @user_nome  = 'Administrador';               -- ← EDITE AQUI
-- Hash de 'Ogro@2026'
SET @user_hash  = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiGPtr5XWbMptVDKkF0v1eOD.BPu';

INSERT INTO users (
    id, tenant_id, name, email, password_hash, role, is_active,
    can_access_sac, can_access_anuncios, can_access_financeiro,
    can_access_logistica, can_access_admin
) VALUES (
    @user_id, @tenant_id, @user_nome, @user_email, @user_hash,
    'ADMIN', 1, 1, 1, 1, 1, 1
);

-- ── 4. Plano de contas padrão para o novo tenant ─────────────
INSERT INTO chart_of_accounts (id, tenant_id, code, name, type, subtype, dre_line) VALUES
(UUID(), @tenant_id, '3.1.1', 'Vendas Marketplace ML',    'RECEITA', 'OPERACIONAL',  'RECEITA_BRUTA'),
(UUID(), @tenant_id, '3.1.2', 'Vendas Loja Própria',      'RECEITA', 'OPERACIONAL',  'RECEITA_BRUTA'),
(UUID(), @tenant_id, '3.9.1', 'Taxas Marketplace ML',     'DESPESA', 'DEDUCAO',      'DEDUCOES'),
(UUID(), @tenant_id, '3.9.2', 'Devoluções',               'DESPESA', 'DEDUCAO',      'DEDUCOES'),
(UUID(), @tenant_id, '4.1.1', 'Custo de Mercadorias',     'CUSTO',   'CMV',          'CMV'),
(UUID(), @tenant_id, '4.2.1', 'Fretes de Envio',          'CUSTO',   'LOGISTICA',    'CMV'),
(UUID(), @tenant_id, '4.2.2', 'Embalagens',               'CUSTO',   'LOGISTICA',    'CMV'),
(UUID(), @tenant_id, '5.1.1', 'Salários e Pró-labore',    'DESPESA', 'PESSOAL',      'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.1.2', 'Encargos Trabalhistas',    'DESPESA', 'PESSOAL',      'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.2.1', 'Aluguel',                  'DESPESA', 'ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.2.2', 'Energia Elétrica',         'DESPESA', 'ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.2.3', 'Internet e Telefone',      'DESPESA', 'ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.2.4', 'Água e Saneamento',        'DESPESA', 'ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.2.5', 'Material de Escritório',   'DESPESA', 'ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.2.6', 'Contabilidade',            'DESPESA', 'ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.3.1', 'Anúncios Patrocinados ML', 'DESPESA', 'MARKETING',    'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.3.2', 'Google Ads / Meta Ads',    'DESPESA', 'MARKETING',    'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.4.1', 'SAM / Sistemas',  'DESPESA', 'TECNOLOGIA',   'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.4.2', 'Hospedagem e Domínio',     'DESPESA', 'TECNOLOGIA',   'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.5.1', 'Tarifas Bancárias',        'DESPESA', 'FINANCEIRA',   'DESPESAS_FINANCEIRAS'),
(UUID(), @tenant_id, '5.5.2', 'Juros e Multas',           'DESPESA', 'FINANCEIRA',   'DESPESAS_FINANCEIRAS'),
(UUID(), @tenant_id, '5.6.1', 'Simples Nacional / DAS',   'DESPESA', 'FISCAL',       'DESPESAS_OPERACIONAIS'),
(UUID(), @tenant_id, '5.9.1', 'Despesas Diversas',        'DESPESA', 'OUTRAS',       'OUTRAS_DESPESAS');

-- ── 5. Confirmação ───────────────────────────────────────────
SELECT '✓ Banco zerado e pronto para o cliente!' AS status;
SELECT @tenant_id AS tenant_id, @tenant_nome AS empresa, @user_email AS login;
SELECT 'Senha inicial: Ogro@2026 — oriente o cliente a trocar no primeiro acesso' AS instrucao;
