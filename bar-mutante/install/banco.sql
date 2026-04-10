-- ============================================================
-- BAR SYSTEM PRO — Schema MySQL
-- PHP 8.3 | MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS bar_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bar_system;

-- Empresa / Configurações gerais
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categorias de produto
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cor VARCHAR(7) DEFAULT '#f59e0b',
    icone VARCHAR(50) DEFAULT 'beer',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- Produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    tipo ENUM('unidade','dose','chopp_lata','chopp_barril','garrafa','drink','combo') DEFAULT 'unidade',
    -- Para chopp barril: define capacidade e rendimento
    capacidade_ml DECIMAL(10,2) DEFAULT NULL COMMENT 'Para barris: capacidade total em ml',
    rendimento_pct DECIMAL(5,2) DEFAULT 85.00 COMMENT 'Percentual útil do barril (padrão 85%)',
    ml_por_dose DECIMAL(8,2) DEFAULT NULL COMMENT 'ML por copo/dose servida',
    -- Preços
    preco_custo DECIMAL(10,2) DEFAULT 0.00,
    preco_venda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    -- Estoque
    estoque_atual DECIMAL(10,3) DEFAULT 0.000,
    estoque_minimo DECIMAL(10,3) DEFAULT 0.000,
    unidade_estoque ENUM('unidade','litro','ml','dose') DEFAULT 'unidade',
    codigo_barras VARCHAR(60),
    imagem VARCHAR(255),
    composicao JSON COMMENT 'JSON para drinks e combos',
    ativo TINYINT(1) DEFAULT 1,
    destaque TINYINT(1) DEFAULT 0,
    disponivel_pdv TINYINT(1) DEFAULT 1,
    ordem_pdv INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Barris de chopp (controle individual)
CREATE TABLE barris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL COMMENT 'Produto base do barril',
    numero_serie VARCHAR(50),
    capacidade_ml DECIMAL(10,2) NOT NULL,
    rendimento_pct DECIMAL(5,2) DEFAULT 85.00,
    -- ml_util e ml_saldo calculados em PHP via calcBarril()
    ml_consumido DECIMAL(10,2) DEFAULT 0.00,
    data_abertura DATE,
    data_vencimento DATE,
    status ENUM('fechado','em_uso','vazio','descartado') DEFAULT 'fechado',
    custo_barril DECIMAL(10,2) DEFAULT 0.00,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- Caixas
CREATE TABLE caixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operador VARCHAR(100) NOT NULL,
    data_abertura DATETIME NOT NULL,
    data_fechamento DATETIME,
    saldo_inicial DECIMAL(10,2) DEFAULT 0.00,
    saldo_final_informado DECIMAL(10,2),
    total_vendas DECIMAL(10,2) DEFAULT 0.00,
    total_sangrias DECIMAL(10,2) DEFAULT 0.00,
    total_suprimentos DECIMAL(10,2) DEFAULT 0.00,
    saldo_esperado DECIMAL(10,2) GENERATED ALWAYS AS (saldo_inicial + total_vendas + total_suprimentos - total_sangrias) STORED,
    diferenca DECIMAL(10,2),
    status ENUM('aberto','fechado') DEFAULT 'aberto',
    observacoes TEXT
) ENGINE=InnoDB;

-- Vendas
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    numero VARCHAR(20) UNIQUE,
    data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    desconto DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    forma_pagamento ENUM('dinheiro','mercadopago','cortesia','ficha','outro') DEFAULT 'dinheiro',
    status ENUM('pendente','pago','cancelado') DEFAULT 'pendente',
    -- Mercado Pago Point
    mp_order_id VARCHAR(100),
    mp_intent_id VARCHAR(100),
    mp_device_id VARCHAR(100),
    mp_status VARCHAR(50),
    mp_response JSON,
    mesa VARCHAR(30),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caixa_id) REFERENCES caixas(id)
) ENGINE=InnoDB;

-- Itens da venda
CREATE TABLE venda_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    barril_id INT DEFAULT NULL,
    descricao VARCHAR(200),
    quantidade DECIMAL(10,3) DEFAULT 1.000,
    preco_unitario DECIMAL(10,2) NOT NULL,
    desconto_item DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (barril_id) REFERENCES barris(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Movimentações de estoque
CREATE TABLE estoque_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    barril_id INT DEFAULT NULL,
    tipo ENUM('entrada','saida','ajuste','perda','abertura_barril') NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    estoque_anterior DECIMAL(10,3),
    estoque_novo DECIMAL(10,3),
    unidade VARCHAR(20),
    custo_unitario DECIMAL(10,2) DEFAULT 0.00,
    motivo VARCHAR(200),
    referencia VARCHAR(100),
    referencia_id INT,
    operador VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (barril_id) REFERENCES barris(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sangrias e Suprimentos de caixa
CREATE TABLE caixa_movimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    tipo ENUM('suprimento','sangria') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(200),
    operador VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caixa_id) REFERENCES caixas(id)
) ENGINE=InnoDB;


-- Terminais Mercado Pago Point
CREATE TABLE IF NOT EXISTS mp_terminais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    device_id VARCHAR(150) NOT NULL UNIQUE,
    modelo VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;




-- ============================================================
-- DADOS INICIAIS
-- ============================================================

INSERT INTO configuracoes (chave, valor, descricao) VALUES
('nome_estabelecimento', 'Bar System Pro', 'Nome do bar/estabelecimento'),
('cnpj', '', 'CNPJ do estabelecimento'),
('endereco', '', 'Endereço'),
('telefone', '', 'Telefone'),
('numero_venda', '1', 'Próximo número de venda'),
('prefix_venda', 'VND', 'Prefixo do número de venda'),
('rendimento_barril_padrao', '85', 'Rendimento padrão de barris (%)'),
('ml_dose_padrao', '300', 'ML padrão por dose de chopp'),
('alerta_estoque_dias', '3', 'Dias para alerta antecipado de estoque'),
('cor_primaria', '#f59e0b', 'Cor primária do sistema'),
('taxa_servico', '0', 'Taxa de serviço (%) — 0 para desativado'),
('ml_dose_destilado_padrao', '50', 'ML por dose padrao de destilados');

INSERT INTO categorias (nome, cor, icone, ordem) VALUES
('Chopp', '#f59e0b', 'beer-bottle', 1),
('Cervejas', '#d97706', 'beer-bottle', 2),
('Drinks', '#7c3aed', 'martini', 3),
('Destilados', '#dc2626', 'tumbler', 4),
('Não Alcoólicos', '#16a34a', 'cup', 5),
('Petiscos', '#ea580c', 'fork-knife', 6),
('Outros', '#6b7280', 'box', 7);

-- Produtos de exemplo
INSERT INTO produtos (categoria_id, nome, tipo, capacidade_ml, rendimento_pct, ml_por_dose, preco_custo, preco_venda, estoque_atual, estoque_minimo, unidade_estoque, disponivel_pdv, ordem_pdv, destaque) VALUES
(1, 'Chopp Pilsen 300ml', 'chopp_barril', NULL, 85, 300, 2.50, 9.00, 0, 2, 'dose', 1, 1, 1),
(1, 'Chopp Weiss 300ml', 'chopp_barril', NULL, 85, 300, 3.00, 10.00, 0, 2, 'dose', 1, 2, 1),
(2, 'Heineken Lata 350ml', 'chopp_lata', NULL, 100, 350, 4.00, 12.00, 24, 6, 'unidade', 1, 3, 0),
(2, 'Brahma Lata 350ml', 'chopp_lata', NULL, 100, 350, 2.50, 8.00, 48, 12, 'unidade', 1, 4, 0),
(2, 'Corona Long Neck 330ml', 'garrafa', NULL, 100, 330, 6.00, 16.00, 12, 6, 'unidade', 1, 5, 0),
(3, 'Caipirinha', 'drink', NULL, NULL, NULL, 3.00, 18.00, 0, 0, 'unidade', 1, 6, 1),
(4, 'Whisky Jack Daniels Dose', 'dose', NULL, NULL, 50, 8.00, 25.00, 0, 5, 'dose', 1, 7, 0),
(5, 'Água Mineral 500ml', 'unidade', NULL, NULL, NULL, 1.00, 5.00, 24, 12, 'unidade', 1, 8, 0),
(5, 'Refrigerante Lata 350ml', 'unidade', NULL, NULL, NULL, 2.00, 7.00, 24, 12, 'unidade', 1, 9, 0),
(6, 'Porção de Batata Frita', 'unidade', NULL, NULL, NULL, 8.00, 28.00, 0, 0, 'unidade', 1, 10, 0);


-- Tickets/Fichas de consumo
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    produto_nome VARCHAR(150) NOT NULL,
    status ENUM('pendente','utilizado','cancelado') DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizado_em DATETIME NULL,
    operador_utilizou VARCHAR(100) NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id)
) ENGINE=InnoDB;

-- Barril de exemplo
INSERT INTO barris (produto_id, capacidade_ml, rendimento_pct, data_abertura, status, custo_barril) VALUES
(1, 30000, 85, CURDATE(), 'em_uso', 180.00);

-- ============================================================
-- TABELA DE USUÁRIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin','caixa_bar','caixa_totem') NOT NULL DEFAULT 'caixa_bar',
    formas_pagamento JSON COMMENT 'Formas permitidas - NULL significa todas',
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuários inseridos via install.php com hashes bcrypt corretos

-- Novas configurações
INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES
('logo_login',   '', 'Logo da tela de login (arquivo em assets/uploads/logos/)'),
('logo_pdv',     '', 'Logo do PDV (arquivo em assets/uploads/logos/)'),
('tema',         'dark', 'Tema do sistema: dark ou light'),
('cor_primaria', '#f59e0b', 'Cor primária (âmbar padrão)'),
('cor_secundaria','#d97706', 'Cor secundária'),
('mp_access_token',   '', 'Access Token Mercado Pago'),
('mp_webhook_secret', '', 'Webhook secret Mercado Pago');
