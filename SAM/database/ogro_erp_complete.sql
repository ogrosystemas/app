-- ============================================================
-- LUPA ERP/WMS — Schema Completo + Seeds + Migrações
-- Versão: 1.0 | Data: 2026-04-20
-- Execute no banco: lupa_erp
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ──────────────────────────────────────────────────────────
-- 1. SCHEMA
-- ──────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS tenants (
    id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL,
    plan ENUM('FREE','STARTER','PRO','ENTERPRISE') NOT NULL DEFAULT 'STARTER',
    is_active TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    license_status ENUM('TRIAL','ACTIVE','EXPIRED','BLOCKED') NOT NULL DEFAULT 'TRIAL',
    license_expiry DATETIME NULL,
    license_key    VARCHAR(255) NULL,
    trial_started  DATETIME NULL,
    activated_at   DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN','MANAGER','OPERATOR','VIEWER') NOT NULL DEFAULT 'OPERATOR',
    can_access_sac TINYINT NOT NULL DEFAULT 1, can_access_anuncios TINYINT NOT NULL DEFAULT 1,
    can_access_financeiro TINYINT NOT NULL DEFAULT 0, can_access_logistica TINYINT NOT NULL DEFAULT 1,
    can_access_admin TINYINT NOT NULL DEFAULT 0, is_active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(64) NOT NULL, user_id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
    ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), INDEX idx_user_id (user_id), INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meli_accounts (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, meli_user_id VARCHAR(30) NOT NULL,
    nickname VARCHAR(100) NOT NULL, email VARCHAR(150) NULL,
    access_token_enc TEXT NOT NULL, refresh_token_enc TEXT NULL, token_expires_at DATETIME NULL,
    reputation_level VARCHAR(20) NULL, sales_score INT NULL, is_active TINYINT NOT NULL DEFAULT 1,
    last_sync_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_tenant_meli (tenant_id, meli_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, meli_item_id VARCHAR(20) NULL,
    sku VARCHAR(50) NOT NULL, title VARCHAR(100) NOT NULL, description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0, cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    ml_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 14, stock_quantity INT NOT NULL DEFAULT 0,
    stock_min INT NOT NULL DEFAULT 5, category_id VARCHAR(20) NULL,
    listing_type_id VARCHAR(30) NOT NULL DEFAULT 'gold_special',
    item_condition VARCHAR(10) NOT NULL DEFAULT 'new', catalog_product_id VARCHAR(50) NULL,
    ml_status ENUM('ACTIVE','PAUSED','CLOSED','UNDER_REVIEW') NOT NULL DEFAULT 'ACTIVE',
    ml_health INT NULL DEFAULT 0, ml_conversion DECIMAL(5,2) NULL DEFAULT 0,
    ml_visits INT NULL DEFAULT 0, ml_permalink VARCHAR(255) NULL,
    picture_ids JSON NULL, ml_attributes JSON NULL, gtin VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_sku_tenant (tenant_id, sku), INDEX idx_meli_item (meli_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, meli_account_id VARCHAR(36) NULL,
    meli_order_id VARCHAR(30) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'PAID',
    buyer_meli_id VARCHAR(30) NULL, buyer_nickname VARCHAR(100) NULL,
    buyer_first_name VARCHAR(60) NULL, buyer_last_name VARCHAR(60) NULL, buyer_email VARCHAR(150) NULL,
    ship_street VARCHAR(150) NULL, ship_city VARCHAR(80) NULL, ship_state VARCHAR(30) NULL, ship_zip VARCHAR(15) NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0, ml_fee_amount DECIMAL(10,2) NULL DEFAULT 0,
    net_amount DECIMAL(10,2) NULL DEFAULT 0, payment_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    ship_status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    pdf_printed TINYINT NOT NULL DEFAULT 0, zpl_printed TINYINT NOT NULL DEFAULT 0,
    label_printed TINYINT NOT NULL DEFAULT 0, has_mediacao TINYINT NOT NULL DEFAULT 0,
    nf_path VARCHAR(255) NULL, nf_number VARCHAR(50) NULL, nf_key VARCHAR(50) NULL, nf_fetched_at DATETIME NULL,
    idempotency_key VARCHAR(64) NULL, order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_meli_order (meli_order_id, tenant_id),
    INDEX idx_tenant_date (tenant_id, order_date), INDEX idx_ship_status (tenant_id, ship_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL, product_id VARCHAR(36) NULL,
    meli_item_id VARCHAR(20) NULL, title VARCHAR(150) NOT NULL, quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0, total_price DECIMAL(10,2) NOT NULL DEFAULT 0, sku VARCHAR(50) NULL,
    PRIMARY KEY (id), INDEX idx_order_id (order_id), INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sac_messages (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, meli_account_id VARCHAR(36) NULL,
    order_id VARCHAR(36) NULL, meli_pack_id VARCHAR(30) NULL, meli_message_id VARCHAR(50) NULL,
    from_role ENUM('BUYER','SELLER') NOT NULL DEFAULT 'BUYER',
    from_nickname VARCHAR(100) NULL, from_meli_id VARCHAR(30) NULL, message_text TEXT NOT NULL,
    sentiment_score DECIMAL(4,3) NULL, sentiment_label VARCHAR(20) NULL,
    is_read TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_meli_msg (meli_message_id),
    INDEX idx_order_id (order_id), INDEX idx_tenant_read (tenant_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sac_conversations (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL,
    status ENUM('OPEN','WAITING','RESOLVED') NOT NULL DEFAULT 'OPEN',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'SALE', category VARCHAR(50) NOT NULL DEFAULT 'REVENUE',
    description VARCHAR(255) NOT NULL, amount DECIMAL(12,2) NOT NULL,
    direction ENUM('CREDIT','DEBIT') NOT NULL, dre_category VARCHAR(50) NULL,
    reference_date DATE NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), INDEX idx_tenant_date (tenant_id, reference_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS queue_jobs (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NULL, meli_account_id VARCHAR(36) NULL,
    job_type VARCHAR(50) NULL DEFAULT 'webhook', topic VARCHAR(50) NULL, resource VARCHAR(255) NULL,
    payload LONGTEXT NULL,
    status ENUM('PENDING','PROCESSING','DONE','FAILED','RUNNING','COMPLETED','RETRYING') NOT NULL DEFAULT 'PENDING',
    priority TINYINT NOT NULL DEFAULT 2, attempts INT NOT NULL DEFAULT 0,
    idempotency_key VARCHAR(64) NULL, error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status_priority (status, priority, created_at),
    INDEX idx_idem_key (idempotency_key), INDEX idx_tenant_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NULL,
    action VARCHAR(50) NOT NULL, table_name VARCHAR(50) NULL, record_id VARCHAR(36) NULL,
    old_data JSON NULL, new_data JSON NULL, ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), INDEX idx_tenant_action (tenant_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tenant_settings (
    id VARCHAR(36) NOT NULL DEFAULT (UUID()), tenant_id VARCHAR(36) NOT NULL,
    `key` VARCHAR(100) NOT NULL, value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_tenant_key (tenant_id, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id VARCHAR(36) NOT NULL DEFAULT (UUID()), tenant_id VARCHAR(36) NOT NULL,
    code VARCHAR(20) NOT NULL, name VARCHAR(100) NOT NULL,
    type ENUM('RECEITA','DESPESA','CUSTO','ATIVO','PASSIVO','PATRIMONIO') NOT NULL,
    subtype VARCHAR(50) NULL, parent_id VARCHAR(36) NULL, dre_line VARCHAR(50) NULL,
    is_active TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uk_code_tenant (tenant_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bank_accounts (
    id VARCHAR(36) NOT NULL DEFAULT (UUID()), tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('CORRENTE','POUPANCA','CAIXA','CARTAO_CREDITO','INVESTIMENTO') NOT NULL DEFAULT 'CORRENTE',
    bank_name VARCHAR(60) NULL, agency VARCHAR(20) NULL, account_num VARCHAR(30) NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00, is_active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS financial_entries (
    id VARCHAR(36) NOT NULL DEFAULT (UUID()), tenant_id VARCHAR(36) NOT NULL,
    entry_date DATE NOT NULL, due_date DATE NULL, paid_date DATE NULL,
    description VARCHAR(255) NOT NULL, amount DECIMAL(12,2) NOT NULL,
    direction ENUM('CREDIT','DEBIT') NOT NULL,
    status ENUM('PENDING','PAID','CANCELLED','OVERDUE') NOT NULL DEFAULT 'PAID',
    account_id VARCHAR(36) NULL, coa_id VARCHAR(36) NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'OPERATIONAL', dre_category VARCHAR(50) NULL,
    is_recurring TINYINT NOT NULL DEFAULT 0,
    recurrence_type ENUM('MONTHLY','WEEKLY','YEARLY') NULL,
    recurrence_end DATE NULL, parent_entry_id VARCHAR(36) NULL, order_id VARCHAR(36) NULL,
    notes TEXT NULL, attachment_path VARCHAR(255) NULL, created_by VARCHAR(36) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_tenant_date (tenant_id, entry_date),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_due_date (due_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- 2. SEEDS DEMO (senha: demo@1234)
-- ──────────────────────────────────────────────────────────

INSERT IGNORE INTO tenants (id, name, plan) VALUES
('tenant-demo-0001-0000-000000000001','Loja ML Demo','PRO');

INSERT IGNORE INTO users (id, tenant_id, name, email, password_hash, role, can_access_financeiro, can_access_admin) VALUES
('user-admin-0001-0000-000000000001','tenant-demo-0001-0000-000000000001','Administrador','admin@lojaml.com.br','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','ADMIN',1,1),
('user-sac-00001-0000-000000000002','tenant-demo-0001-0000-000000000001','Atendente SAC','sac@lojaml.com.br','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','OPERATOR',0,0),
('user-vend-0001-0000-000000000003','tenant-demo-0001-0000-000000000001','Vendedor','vendas@lojaml.com.br','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','OPERATOR',0,0),
('user-fin-00001-0000-000000000004','tenant-demo-0001-0000-000000000001','Financeiro','financeiro@lojaml.com.br','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','MANAGER',1,0),
('user-exp-00001-0000-000000000005','tenant-demo-0001-0000-000000000001','Expedição','expedicao@lojaml.com.br','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','OPERATOR',0,0);

INSERT IGNORE INTO meli_accounts (id, tenant_id, meli_user_id, nickname, email, access_token_enc, refresh_token_enc, token_expires_at, reputation_level, sales_score, is_active) VALUES
('meli-acc-0001-0000-000000000001','tenant-demo-0001-0000-000000000001','123456789','LOJA_ML_DEMO','loja@lojaml.com.br','demo_token','demo_refresh',DATE_ADD(NOW(),INTERVAL 6 HOUR),'platinum',98,0);

INSERT IGNORE INTO products (id,tenant_id,meli_item_id,sku,title,price,cost_price,ml_fee_percent,stock_quantity,stock_min,ml_status,ml_health,ml_conversion,ml_visits) VALUES
('prod-0001-0000-0000-000000000001','tenant-demo-0001-0000-000000000001','MLB1001','FONE-BT-PRO','Fone Bluetooth Pro Max 50h',189.90,72.00,14,47,10,'ACTIVE',90,5.2,3240),
('prod-0002-0000-0000-000000000002','tenant-demo-0001-0000-000000000001','MLB1002','TELA-15-FHD','Tela LCD 15 FHD para Notebook',389.90,195.00,14,23,5,'ACTIVE',55,2.1,890),
('prod-0003-0000-0000-000000000003','tenant-demo-0001-0000-000000000001','MLB1003','CABO-USBC-2M','Cabo USB-C 2m Reforçado',34.90,8.00,16,210,30,'ACTIVE',95,7.8,8100),
('prod-0004-0000-0000-000000000004','tenant-demo-0001-0000-000000000001','MLB1004','SUPORTE-NB-ADJ','Suporte Notebook Ajustável',129.90,48.00,14,3,5,'ACTIVE',30,1.8,420),
('prod-0005-0000-0000-000000000005','tenant-demo-0001-0000-000000000001','MLB1005','MOUSE-ERGO-WL','Mouse Sem Fio Ergonômico 2.4GHz',79.90,26.00,14,88,15,'ACTIVE',75,4.5,4680),
('prod-0006-0000-0000-000000000006','tenant-demo-0001-0000-000000000001','MLB1006','KBD-MECH-RGB','Teclado Mecânico RGB Switch Blue',249.90,98.00,14,31,8,'ACTIVE',82,3.9,2100),
('prod-0007-0000-0000-000000000007','tenant-demo-0001-0000-000000000001','MLB1007','WEBCAM-FHD-60','Webcam Full HD 1080p 60fps',159.90,58.00,14,19,5,'ACTIVE',68,3.2,1560),
('prod-0008-0000-0000-000000000008','tenant-demo-0001-0000-000000000001','MLB1008','HUB-USB-7P','Hub USB 7 Portas USB 3.0',89.90,32.00,14,54,10,'PAUSED',45,2.8,980);

INSERT IGNORE INTO orders (id,tenant_id,meli_account_id,meli_order_id,status,buyer_meli_id,buyer_nickname,buyer_first_name,buyer_last_name,buyer_email,ship_street,ship_city,ship_state,ship_zip,total_amount,ml_fee_amount,net_amount,payment_status,ship_status,pdf_printed,zpl_printed,label_printed,has_mediacao,idempotency_key,order_date) VALUES
('order-001','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00891','PAID','buyer001','carlos.melo22','Carlos','Melo','carlos@email.com','Rua das Flores 123','São Paulo','SP','01310-100',189.90,26.59,163.31,'APPROVED','READY_TO_SHIP',0,0,0,0,'idem-001',DATE_SUB(NOW(),INTERVAL 1 DAY)),
('order-002','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00889','PAID','buyer002','ana.sousa99','Ana','Sousa','ana@email.com','Av Paulista 1000','São Paulo','SP','01310-200',34.90,5.58,29.32,'APPROVED','SHIPPED',1,1,1,0,'idem-002',DATE_SUB(NOW(),INTERVAL 2 DAY)),
('order-003','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00887','PAID','buyer003','pedro.lima55','Pedro','Lima','pedro@email.com','Rua XV de Novembro 50','Curitiba','PR','80020-310',79.90,11.19,68.71,'APPROVED','READY_TO_SHIP',1,0,0,0,'idem-003',DATE_SUB(NOW(),INTERVAL 2 DAY)),
('order-004','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00883','PAID','buyer004','maria.oliveira3','Maria','Oliveira','maria@email.com','Rua Dom Pedro II 200','Belo Horizonte','MG','30110-012',129.90,18.19,111.71,'APPROVED','READY_TO_SHIP',0,0,0,0,'idem-004',DATE_SUB(NOW(),INTERVAL 3 DAY)),
('order-005','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00871','PAID','buyer005','joao.costa77','João','Costa','joao@email.com','Av Beira Mar 500','Florianópolis','SC','88015-200',389.90,54.59,335.31,'APPROVED','READY_TO_SHIP',0,0,0,1,'idem-005',DATE_SUB(NOW(),INTERVAL 4 DAY)),
('order-006','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00865','PAID','buyer006','lucia.fernandes','Lucia','Fernandes','lucia@email.com','Rua Liberdade 88','São Paulo','SP','01503-010',249.90,34.99,214.91,'APPROVED','DELIVERED',1,1,1,0,'idem-006',DATE_SUB(NOW(),INTERVAL 5 DAY)),
('order-007','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00860','PAID','buyer007','roberto.silva11','Roberto','Silva','roberto@email.com','Av Atlantica 1800','Rio de Janeiro','RJ','22021-001',159.90,22.39,137.51,'APPROVED','SHIPPED',1,1,1,0,'idem-007',DATE_SUB(NOW(),INTERVAL 6 DAY)),
('order-008','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00855','PAID','buyer008','fernanda.gomes','Fernanda','Gomes','fernanda@email.com','Rua Augusta 300','São Paulo','SP','01305-000',89.90,12.59,77.31,'APPROVED','READY_TO_SHIP',0,0,0,0,'idem-008',DATE_SUB(NOW(),INTERVAL 1 DAY)),
('order-009','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00848','PAID','buyer009','thiago.mendes22','Thiago','Mendes','thiago@email.com','Rua Conceição 450','Campinas','SP','13010-050',34.90,5.58,29.32,'APPROVED','DELIVERED',1,1,1,0,'idem-009',DATE_SUB(NOW(),INTERVAL 7 DAY)),
('order-010','tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','#00840','PAID','buyer010','patricia.souza','Patricia','Souza','pat@email.com','Av Brasil 2000','Porto Alegre','RS','90010-170',189.90,26.59,163.31,'APPROVED','READY_TO_SHIP',0,0,0,0,'idem-010',NOW());

INSERT IGNORE INTO order_items (id,order_id,product_id,meli_item_id,title,quantity,unit_price,total_price,sku) VALUES
(UUID(),'order-001','prod-0001-0000-0000-000000000001','MLB1001','Fone Bluetooth Pro Max 50h',1,189.90,189.90,'FONE-BT-PRO'),
(UUID(),'order-002','prod-0003-0000-0000-000000000003','MLB1003','Cabo USB-C 2m Reforçado',1,34.90,34.90,'CABO-USBC-2M'),
(UUID(),'order-003','prod-0005-0000-0000-000000000005','MLB1005','Mouse Sem Fio Ergonômico',1,79.90,79.90,'MOUSE-ERGO-WL'),
(UUID(),'order-004','prod-0004-0000-0000-000000000004','MLB1004','Suporte Notebook Ajustável',1,129.90,129.90,'SUPORTE-NB-ADJ'),
(UUID(),'order-005','prod-0002-0000-0000-000000000002','MLB1002','Tela LCD 15 FHD',1,389.90,389.90,'TELA-15-FHD'),
(UUID(),'order-006','prod-0006-0000-0000-000000000006','MLB1006','Teclado Mecânico RGB',1,249.90,249.90,'KBD-MECH-RGB'),
(UUID(),'order-007','prod-0007-0000-0000-000000000007','MLB1007','Webcam Full HD 1080p',1,159.90,159.90,'WEBCAM-FHD-60'),
(UUID(),'order-008','prod-0008-0000-0000-000000000008','MLB1008','Hub USB 7 Portas',1,89.90,89.90,'HUB-USB-7P'),
(UUID(),'order-009','prod-0003-0000-0000-000000000003','MLB1003','Cabo USB-C 2m Reforçado',2,34.90,69.80,'CABO-USBC-2M'),
(UUID(),'order-010','prod-0001-0000-0000-000000000001','MLB1001','Fone Bluetooth Pro Max',1,189.90,189.90,'FONE-BT-PRO');

INSERT IGNORE INTO sac_messages (id,tenant_id,meli_account_id,order_id,meli_pack_id,from_role,from_nickname,from_meli_id,message_text,sentiment_score,sentiment_label,is_read,created_at) VALUES
(UUID(),'tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','order-005','pack-001','BUYER','joao.costa77','buyer005','Meu pedido ainda não foi enviado! Já faz 4 dias.',-0.7,'NEGATIVE',0,DATE_SUB(NOW(),INTERVAL 2 HOUR)),
(UUID(),'tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','order-001','pack-002','BUYER','carlos.melo22','buyer001','Olá, quando meu produto vai chegar?',-0.2,'NEUTRAL',0,DATE_SUB(NOW(),INTERVAL 3 HOUR)),
(UUID(),'tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','order-003','pack-003','BUYER','pedro.lima55','buyer003','O mouse chegou mas não está funcionando!',-0.6,'NEGATIVE',0,DATE_SUB(NOW(),INTERVAL 5 HOUR)),
(UUID(),'tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','order-006','pack-004','BUYER','lucia.fernandes','buyer006','Produto chegou perfeito! Muito satisfeita!',0.95,'VERY_POSITIVE',1,DATE_SUB(NOW(),INTERVAL 1 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001','meli-acc-0001-0000-000000000001','order-006','pack-004','SELLER','LOJA_ML_DEMO',NULL,'Que ótimo! Ficamos felizes que gostou!',0.9,'POSITIVE',1,DATE_SUB(NOW(),INTERVAL 23 HOUR));

INSERT IGNORE INTO transactions (id,tenant_id,order_id,type,category,description,amount,direction,dre_category,reference_date) VALUES
(UUID(),'tenant-demo-0001-0000-000000000001','order-006','SALE','REVENUE','Venda #00865',249.90,'CREDIT','RECEITA_BRUTA',DATE_SUB(CURDATE(),INTERVAL 5 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001','order-006','ML_FEE','MARKETPLACE_FEE','Taxa ML #00865',34.99,'DEBIT','DEDUCOES',DATE_SUB(CURDATE(),INTERVAL 5 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001','order-007','SALE','REVENUE','Venda #00860',159.90,'CREDIT','RECEITA_BRUTA',DATE_SUB(CURDATE(),INTERVAL 6 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001','order-007','ML_FEE','MARKETPLACE_FEE','Taxa ML #00860',22.39,'DEBIT','DEDUCOES',DATE_SUB(CURDATE(),INTERVAL 6 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001',NULL,'SALE','REVENUE','Venda #00835',189.90,'CREDIT','RECEITA_BRUTA',DATE_SUB(CURDATE(),INTERVAL 9 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001',NULL,'ML_FEE','MARKETPLACE_FEE','Taxa ML #00835',26.59,'DEBIT','DEDUCOES',DATE_SUB(CURDATE(),INTERVAL 9 DAY)),
(UUID(),'tenant-demo-0001-0000-000000000001',NULL,'MANUAL_ADJUSTMENT','COST_OF_GOODS','CMV — produtos vendidos',312.00,'DEBIT','CMV',CURDATE()),
(UUID(),'tenant-demo-0001-0000-000000000001',NULL,'SUBSCRIPTION','OPERATIONAL','Mensalidade SAM',149.90,'DEBIT','DESPESAS_OPERACIONAIS',CURDATE()),
(UUID(),'tenant-demo-0001-0000-000000000001',NULL,'ADVERTISING','OPERATIONAL','Anúncios patrocinados ML',89.00,'DEBIT','DESPESAS_OPERACIONAIS',CURDATE());

-- Plano de contas
INSERT IGNORE INTO chart_of_accounts (id,tenant_id,code,name,type,subtype,dre_line) VALUES
(UUID(),'tenant-demo-0001-0000-000000000001','3.1.1','Vendas Marketplace ML','RECEITA','OPERACIONAL','RECEITA_BRUTA'),
(UUID(),'tenant-demo-0001-0000-000000000001','3.1.2','Vendas Loja Própria','RECEITA','OPERACIONAL','RECEITA_BRUTA'),
(UUID(),'tenant-demo-0001-0000-000000000001','3.9.1','Taxas Marketplace ML','DESPESA','DEDUCAO','DEDUCOES'),
(UUID(),'tenant-demo-0001-0000-000000000001','3.9.2','Devoluções','DESPESA','DEDUCAO','DEDUCOES'),
(UUID(),'tenant-demo-0001-0000-000000000001','4.1.1','Custo de Mercadorias','CUSTO','CMV','CMV'),
(UUID(),'tenant-demo-0001-0000-000000000001','4.2.1','Fretes de Envio','CUSTO','LOGISTICA','CMV'),
(UUID(),'tenant-demo-0001-0000-000000000001','4.2.2','Embalagens','CUSTO','LOGISTICA','CMV'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.1.1','Salários e Pró-labore','DESPESA','PESSOAL','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.1.2','Encargos Trabalhistas','DESPESA','PESSOAL','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.2.1','Aluguel','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.2.2','Energia Elétrica','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.2.3','Internet e Telefone','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.2.4','Água e Saneamento','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.2.5','Material de Escritório','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.2.6','Contabilidade','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.3.1','Anúncios Patrocinados ML','DESPESA','MARKETING','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.3.2','Google Ads / Meta Ads','DESPESA','MARKETING','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.4.1','SAM / Sistemas','DESPESA','TECNOLOGIA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.4.2','Hospedagem e Domínio','DESPESA','TECNOLOGIA','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.5.1','Tarifas Bancárias','DESPESA','FINANCEIRA','DESPESAS_FINANCEIRAS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.5.2','Juros e Multas','DESPESA','FINANCEIRA','DESPESAS_FINANCEIRAS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.6.1','Simples Nacional / DAS','DESPESA','FISCAL','DESPESAS_OPERACIONAIS'),
(UUID(),'tenant-demo-0001-0000-000000000001','5.9.1','Despesas Diversas','DESPESA','OUTRAS','OUTRAS_DESPESAS');

-- Contas bancárias
INSERT IGNORE INTO bank_accounts (id,tenant_id,name,type,bank_name,balance) VALUES
(UUID(),'tenant-demo-0001-0000-000000000001','Conta Corrente Principal','CORRENTE','Banco do Brasil',5000.00),
(UUID(),'tenant-demo-0001-0000-000000000001','Caixa Físico','CAIXA',NULL,800.00),
(UUID(),'tenant-demo-0001-0000-000000000001','Cartão de Crédito Empresarial','CARTAO_CREDITO','Nubank',0.00);

SET foreign_key_checks = 1;

-- ──────────────────────────────────────────────────────────
-- 4. SEGURANÇA — tabelas adicionais
-- ──────────────────────────────────────────────────────────

-- Rate limiting de login (anti brute-force)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Limpeza automática de tentativas antigas (via event scheduler ou cron)
-- DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
