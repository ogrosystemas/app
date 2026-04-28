-- ============================================================
-- demo_activate.sql
-- Ativa a conta LOJA_ML_DEMO e popula dados financeiros
-- para apresentação ao cliente.
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < demo_activate.sql
--
-- SEGURO para rodar múltiplas vezes (INSERT IGNORE + UPDATE).
-- ============================================================

SET foreign_key_checks = 0;

-- ── 1. Ativa a conta demo ────────────────────────────────────
UPDATE meli_accounts
SET is_active        = 1,
    token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE id = 'meli-acc-0001-0000-000000000001';

-- ── 2. Garante que o tenant demo está ativo ──────────────────
UPDATE tenants
SET is_active       = 1,
    license_status  = 'TRIAL',
    trial_started   = NOW(),
    license_expiry  = DATE_ADD(NOW(), INTERVAL 15 DAY)
WHERE id = 'tenant-demo-0001-0000-000000000001';

-- ── 3. Garante usuário admin demo ativo ──────────────────────
UPDATE users
SET is_active = 1
WHERE tenant_id = 'tenant-demo-0001-0000-000000000001';

-- ── 4. Atualiza datas dos pedidos para "agora" ───────────────
-- (sem isso os gráficos de 7 dias ficam vazios)
UPDATE orders SET order_date = NOW()
WHERE id = 'order-010' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE id IN ('order-001','order-008') AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 2 DAY)
WHERE id IN ('order-002','order-003') AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 3 DAY)
WHERE id = 'order-004' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 4 DAY)
WHERE id = 'order-005' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 5 DAY)
WHERE id = 'order-006' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 6 DAY)
WHERE id = 'order-007' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 7 DAY)
WHERE id = 'order-009' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

-- ── 5. Atualiza datas das mensagens SAC ─────────────────────
UPDATE sac_messages
SET created_at = DATE_SUB(NOW(), INTERVAL 2 HOUR)
WHERE order_id = 'order-005' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE sac_messages
SET created_at = DATE_SUB(NOW(), INTERVAL 3 HOUR)
WHERE order_id = 'order-001' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE sac_messages
SET created_at = DATE_SUB(NOW(), INTERVAL 5 HOUR)
WHERE order_id = 'order-003' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

-- ── 6. Atualiza datas das transactions ──────────────────────
UPDATE transactions
SET reference_date = DATE_SUB(CURDATE(), INTERVAL 5 DAY)
WHERE order_id = 'order-006' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE transactions
SET reference_date = DATE_SUB(CURDATE(), INTERVAL 6 DAY)
WHERE order_id = 'order-007' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

-- ── 7. Insere financial_entries demo (com meli_account_id) ──────
INSERT IGNORE INTO financial_entries
    (id, tenant_id, meli_account_id, direction, amount, description, entry_date, due_date, paid_date,
     status, dre_category, is_recurring, notes)
VALUES
('fin-demo-001','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','CREDIT',  1219.10,'Receitas ML — semana 1',
  DATE_FORMAT(CURDATE(),'%Y-%m-10'), DATE_FORMAT(CURDATE(),'%Y-%m-10'), DATE_FORMAT(CURDATE(),'%Y-%m-10'),
  'PAID','RECEITA_VENDA',0,'Importado automaticamente'),

('fin-demo-002','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','CREDIT',   890.50,'Receitas ML — semana 2',
  DATE_FORMAT(CURDATE(),'%Y-%m-14'), DATE_FORMAT(CURDATE(),'%Y-%m-14'), DATE_FORMAT(CURDATE(),'%Y-%m-14'),
  'PAID','RECEITA_VENDA',0,'Importado automaticamente'),

('fin-demo-003','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','CREDIT',  1540.00,'Receitas ML — semana 3',
  DATE_FORMAT(CURDATE(),'%Y-%m-17'), DATE_FORMAT(CURDATE(),'%Y-%m-17'), DATE_FORMAT(CURDATE(),'%Y-%m-17'),
  'PAID','RECEITA_VENDA',0,'Importado automaticamente'),

('fin-demo-010','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','DEBIT',  1200.00,'Aluguel galpão',
  DATE_FORMAT(CURDATE(),'%Y-%m-05'), DATE_FORMAT(CURDATE(),'%Y-%m-05'), DATE_FORMAT(CURDATE(),'%Y-%m-05'),
  'PAID','ADMINISTRATIVO',1,'Recorrente mensal'),

('fin-demo-011','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','DEBIT',   450.00,'Energia elétrica',
  DATE_FORMAT(CURDATE(),'%Y-%m-08'), DATE_FORMAT(CURDATE(),'%Y-%m-08'), DATE_FORMAT(CURDATE(),'%Y-%m-08'),
  'PAID','OPERACIONAL',0,NULL),

('fin-demo-012','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','DEBIT',   320.00,'Embalagens e insumos',
  DATE_FORMAT(CURDATE(),'%Y-%m-11'), DATE_FORMAT(CURDATE(),'%Y-%m-11'), DATE_FORMAT(CURDATE(),'%Y-%m-11'),
  'PAID','OPERACIONAL',0,NULL),

('fin-demo-013','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','DEBIT',   180.00,'Frete coleta Correios',
  DATE_FORMAT(CURDATE(),'%Y-%m-13'), DATE_FORMAT(CURDATE(),'%Y-%m-13'), DATE_FORMAT(CURDATE(),'%Y-%m-13'),
  'PAID','FRETE_CUSTO',0,NULL),

('fin-demo-014','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','DEBIT',   890.00,'Reposição estoque — Fones',
  DATE_FORMAT(CURDATE(),'%Y-%m-15'), DATE_FORMAT(CURDATE(),'%Y-%m-15'), DATE_FORMAT(CURDATE(),'%Y-%m-15'),
  'PAID','CMV',0,NULL),

('fin-demo-020','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','DEBIT',   650.00,'Contador — honorários',
  DATE_FORMAT(CURDATE(),'%Y-%m-25'), DATE_FORMAT(CURDATE(),'%Y-%m-25'), NULL,
  'PENDING','ADMINISTRATIVO',1,'Vence dia 25'),

('fin-demo-021','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','CREDIT',  480.00,'Devolução ML pendente',
  DATE_FORMAT(CURDATE(),'%Y-%m-22'), DATE_FORMAT(CURDATE(),'%Y-%m-28'), NULL,
  'PENDING','RECEITA_VENDA',0,'Aguardando liberação ML');

-- ── 8. Insere sac_conversations para as mensagens demo ───────
INSERT IGNORE INTO sac_conversations (id, tenant_id, order_id, status)
VALUES
('conv-demo-001','tenant-demo-0001-0000-000000000001','order-005','OPEN'),
('conv-demo-002','tenant-demo-0001-0000-000000000001','order-001','OPEN'),
('conv-demo-003','tenant-demo-0001-0000-000000000001','order-003','OPEN'),
('conv-demo-004','tenant-demo-0001-0000-000000000001','order-006','RESOLVED');

SET foreign_key_checks = 1;

SELECT 'demo_activate.sql executado com sucesso!' AS status;
SELECT nickname, is_active, token_expires_at FROM meli_accounts WHERE id = 'meli-acc-0001-0000-000000000001';
SELECT COUNT(*) AS pedidos_demo FROM orders WHERE tenant_id = 'tenant-demo-0001-0000-000000000001';
SELECT COUNT(*) AS lancamentos_demo FROM financial_entries WHERE tenant_id = 'tenant-demo-0001-0000-000000000001';
