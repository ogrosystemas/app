-- ============================================================
-- demo_reset.sql
-- Reseta as datas dos dados demo para "agora".
-- Rodar ANTES de cada apresentação ao cliente para que os
-- gráficos apareçam cheios com dados recentes.
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < demo_reset.sql
--
-- Roda em < 1 segundo. Seguro para repetir quantas vezes quiser.
-- ============================================================

-- Pedidos: distribui nos últimos 7 dias a partir de hoje
UPDATE orders SET order_date = NOW()                                WHERE id = 'order-010';
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 1  DAY)    WHERE id IN ('order-001','order-008');
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 2  DAY)    WHERE id IN ('order-002','order-003');
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 3  DAY)    WHERE id = 'order-004';
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 4  DAY)    WHERE id = 'order-005';
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 5  DAY)    WHERE id = 'order-006';
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 6  DAY)    WHERE id = 'order-007';
UPDATE orders SET order_date = DATE_SUB(NOW(), INTERVAL 7  DAY)    WHERE id = 'order-009';

-- Mensagens SAC: recentes
UPDATE sac_messages SET created_at = DATE_SUB(NOW(), INTERVAL 2  HOUR) WHERE order_id = 'order-005';
UPDATE sac_messages SET created_at = DATE_SUB(NOW(), INTERVAL 3  HOUR) WHERE order_id = 'order-001';
UPDATE sac_messages SET created_at = DATE_SUB(NOW(), INTERVAL 5  HOUR) WHERE order_id = 'order-003';
UPDATE sac_messages SET created_at = DATE_SUB(NOW(), INTERVAL 1  DAY)  WHERE order_id = 'order-006';

-- Conversas SAC
UPDATE sac_conversations SET last_message_at = DATE_SUB(NOW(), INTERVAL 2  HOUR) WHERE id = 'conv-demo-001';
UPDATE sac_conversations SET last_message_at = DATE_SUB(NOW(), INTERVAL 3  HOUR) WHERE id = 'conv-demo-002';
UPDATE sac_conversations SET last_message_at = DATE_SUB(NOW(), INTERVAL 5  HOUR) WHERE id = 'conv-demo-003';
UPDATE sac_conversations SET last_message_at = DATE_SUB(NOW(), INTERVAL 23 HOUR) WHERE id = 'conv-demo-004';
-- Marca mensagens não lidas (para o badge aparecer)
UPDATE sac_messages SET is_read = 0 WHERE order_id IN ('order-005','order-001','order-003') AND from_role = 'BUYER';

-- Transactions: no mês atual
UPDATE transactions
SET reference_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
WHERE order_id IN ('order-001','order-008') AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE transactions
SET reference_date = DATE_SUB(CURDATE(), INTERVAL 5 DAY)
WHERE order_id = 'order-006' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

UPDATE transactions
SET reference_date = DATE_SUB(CURDATE(), INTERVAL 6 DAY)
WHERE order_id = 'order-007' AND tenant_id = 'tenant-demo-0001-0000-000000000001';

-- Financial entries: distribui no mês atual
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-10'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-10'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-10') WHERE id = 'fin-demo-001';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-14'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-14'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-14') WHERE id = 'fin-demo-002';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-17'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-17'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-17') WHERE id = 'fin-demo-003';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-05'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-05'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-05') WHERE id = 'fin-demo-010';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-08'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-08'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-08') WHERE id = 'fin-demo-011';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-11'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-11'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-11') WHERE id = 'fin-demo-012';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-13'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-13'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-13') WHERE id = 'fin-demo-013';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-15'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-15'), paid_date = DATE_FORMAT(CURDATE(),'%Y-%m-15') WHERE id = 'fin-demo-014';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-25'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-25'), paid_date = NULL WHERE id = 'fin-demo-020';
UPDATE financial_entries SET entry_date = DATE_FORMAT(CURDATE(),'%Y-%m-22'), due_date = DATE_FORMAT(CURDATE(),'%Y-%m-28'), paid_date = NULL WHERE id = 'fin-demo-021';

SELECT 'demo_reset.sql OK — dados prontos para apresentação!' AS status;
SELECT COUNT(*) AS msgs_nao_lidas FROM sac_messages WHERE tenant_id='tenant-demo-0001-0000-000000000001' AND is_read=0 AND from_role='BUYER';
SELECT COUNT(*) AS pedidos_mes FROM orders WHERE tenant_id='tenant-demo-0001-0000-000000000001' AND order_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01');
SELECT SUM(amount) AS receitas_mes FROM financial_entries WHERE tenant_id='tenant-demo-0001-0000-000000000001' AND direction='CREDIT' AND status='PAID';
