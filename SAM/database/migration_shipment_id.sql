-- ============================================================
-- migration_shipment_id.sql
-- Adiciona shipment_id na tabela orders para buscar etiquetas ML.
--
-- USO:
--   mysql -u lupa_user -pLupa2026 lupa_erp < database/migration_shipment_id.sql
-- ============================================================

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS meli_shipment_id VARCHAR(30) NULL AFTER meli_order_id,
    ADD INDEX IF NOT EXISTS idx_shipment (meli_shipment_id);

-- Tenta popular shipment_id a partir do meli_order_id para pedidos existentes
-- (só funciona se o worker já salvou esse dado em algum campo JSON — senão fica NULL
--  e será preenchido no próximo sync)

SELECT 'migration_shipment_id.sql OK' AS status;
SELECT COUNT(*) AS pedidos_com_shipment FROM orders WHERE meli_shipment_id IS NOT NULL;
SELECT COUNT(*) AS pedidos_sem_shipment FROM orders WHERE meli_shipment_id IS NULL;
