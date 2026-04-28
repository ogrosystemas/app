-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 28/04/2026 às 19:49
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `barm_barmutante`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `barris`
--

CREATE TABLE `barris` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL COMMENT 'Produto base do barril',
  `numero_serie` varchar(50) DEFAULT NULL,
  `capacidade_ml` decimal(10,2) NOT NULL,
  `rendimento_pct` decimal(5,2) DEFAULT 85.00,
  `ml_consumido` decimal(10,2) DEFAULT 0.00,
  `data_abertura` date DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `status` enum('fechado','em_uso','vazio','descartado') DEFAULT 'fechado',
  `custo_barril` decimal(10,2) DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `barris`
--

INSERT INTO `barris` (`id`, `produto_id`, `numero_serie`, `capacidade_ml`, `rendimento_pct`, `ml_consumido`, `data_abertura`, `data_vencimento`, `status`, `custo_barril`, `observacoes`, `created_at`) VALUES
(1, 1, NULL, 30000.00, 85.00, 0.00, '2026-04-08', NULL, 'em_uso', 180.00, NULL, '2026-04-08 18:42:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixas`
--

CREATE TABLE `caixas` (
  `id` int(11) NOT NULL,
  `operador` varchar(100) NOT NULL,
  `data_abertura` datetime NOT NULL,
  `data_fechamento` datetime DEFAULT NULL,
  `saldo_inicial` decimal(10,2) DEFAULT 0.00,
  `saldo_final_informado` decimal(10,2) DEFAULT NULL,
  `total_vendas` decimal(10,2) DEFAULT 0.00,
  `total_sangrias` decimal(10,2) DEFAULT 0.00,
  `total_suprimentos` decimal(10,2) DEFAULT 0.00,
  `saldo_esperado` decimal(10,2) GENERATED ALWAYS AS (`saldo_inicial` + `total_vendas` + `total_suprimentos` - `total_sangrias`) STORED,
  `diferenca` decimal(10,2) DEFAULT NULL,
  `status` enum('aberto','fechado') DEFAULT 'aberto',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `caixas`
--

INSERT INTO `caixas` (`id`, `operador`, `data_abertura`, `data_fechamento`, `saldo_inicial`, `saldo_final_informado`, `total_vendas`, `total_sangrias`, `total_suprimentos`, `diferenca`, `status`, `observacoes`) VALUES
(1, 'Administrador', '2026-04-08 20:47:48', '2026-04-08 15:51:24', 0.00, 0.00, 16.00, 0.00, 0.00, -16.00, 'fechado', ''),
(2, 'admin', '2026-04-08 15:54:10', '2026-04-08 15:54:18', 100.00, 0.00, 0.00, 0.00, 0.00, -100.00, 'fechado', ''),
(3, 'admin', '2026-04-08 15:54:24', '2026-04-08 15:55:36', 100.00, 0.00, 0.00, 0.00, 0.00, -100.00, 'fechado', ''),
(4, 'admin', '2026-04-08 15:55:43', '2026-04-08 15:55:47', 10.00, 0.00, 0.00, 0.00, 0.00, -10.00, 'fechado', ''),
(5, 'admin', '2026-04-08 15:55:52', '2026-04-12 15:57:47', 100.00, 0.00, 12.00, 0.00, 0.00, -112.00, 'fechado', ''),
(6, 'admin', '2026-04-14 21:33:45', '2026-04-14 22:42:21', 100.00, 0.00, 16.00, 10.00, 0.00, -106.00, 'fechado', ''),
(7, 'admin', '2026-04-14 22:42:37', '2026-04-14 22:42:44', 100.00, 0.00, 0.00, 0.00, 0.00, -100.00, 'fechado', ''),
(8, 'admin', '2026-04-15 09:52:30', '2026-04-15 09:52:37', 100.00, 0.00, 0.00, 0.00, 0.00, -100.00, 'fechado', ''),
(9, 'admin', '2026-04-15 09:52:48', NULL, 100.00, NULL, 16.00, 0.00, 0.00, NULL, 'aberto', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixa_movimentos`
--

CREATE TABLE `caixa_movimentos` (
  `id` int(11) NOT NULL,
  `caixa_id` int(11) NOT NULL,
  `tipo` enum('suprimento','sangria') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `operador` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `caixa_movimentos`
--

INSERT INTO `caixa_movimentos` (`id`, `caixa_id`, `tipo`, `valor`, `motivo`, `operador`, `created_at`) VALUES
(1, 6, 'sangria', 10.00, '', 'admin', '2026-04-15 00:35:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cor` varchar(7) DEFAULT '#f59e0b',
  `icone` varchar(50) DEFAULT 'beer',
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `cor`, `icone`, `ordem`, `ativo`) VALUES
(1, 'Chopp', '#f59e0b', 'beer-bottle', 1, 1),
(2, 'Cervejas', '#d97706', 'beer-bottle', 2, 1),
(3, 'Drinks', '#7c3aed', 'martini', 3, 1),
(4, 'Destilados', '#dc2626', 'tumbler', 4, 1),
(5, 'Não Alcoólicos', '#16a34a', 'cup', 5, 1),
(6, 'Petiscos', '#ea580c', 'fork-knife', 6, 1),
(7, 'Outros', '#6b7280', 'box', 7, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`, `descricao`, `updated_at`) VALUES
(1, 'nome_estabelecimento', 'Mutantes Subsede Itajai', 'Nome do bar/estabelecimento', '2026-04-09 04:09:47'),
(2, 'cnpj', '', 'CNPJ do estabelecimento', '2026-04-08 18:42:43'),
(3, 'endereco', '', 'Endereço', '2026-04-08 18:42:43'),
(4, 'telefone', '', 'Telefone', '2026-04-08 18:42:43'),
(5, 'numero_venda', '6', 'Próximo número de venda', '2026-04-15 13:20:48'),
(6, 'prefix_venda', 'VND', 'Prefixo do número de venda', '2026-04-08 18:42:43'),
(7, 'rendimento_barril_padrao', '100', 'Rendimento padrão de barris (%)', '2026-04-09 04:10:21'),
(8, 'ml_dose_padrao', '400', 'ML padrão por dose de chopp', '2026-04-09 04:10:21'),
(9, 'alerta_estoque_dias', '3', 'Dias para alerta antecipado de estoque', '2026-04-08 18:42:43'),
(10, 'cor_primaria', '#f59e0b', 'Cor primária do sistema', '2026-04-08 18:42:43'),
(11, 'taxa_servico', '0', 'Taxa de serviço (%) — 0 para desativado', '2026-04-08 18:42:43'),
(12, 'ml_dose_destilado_padrao', '60', 'ML por dose padrao de destilados', '2026-04-09 04:10:33'),
(13, 'logo_login', 'img_69dedd058268b4.04128475.png', 'Logo da tela de login (arquivo em assets/uploads/logos/)', '2026-04-15 00:34:13'),
(14, 'logo_pdv', 'img_69dedf1b5c4451.94712868.png', 'Logo do PDV (arquivo em assets/uploads/logos/)', '2026-04-15 00:43:07'),
(15, 'tema', 'dark', 'Tema do sistema: dark ou light', '2026-04-08 18:42:43'),
(16, 'cor_secundaria', '#d97706', 'Cor secundária', '2026-04-08 18:42:43'),
(17, 'mp_access_token', '', 'Access Token Mercado Pago', '2026-04-08 18:42:43'),
(18, 'mp_webhook_secret', '', 'Webhook secret Mercado Pago', '2026-04-08 18:42:43'),
(28, 'ticket_largura_mm', '58', NULL, '2026-04-09 04:08:46'),
(29, 'ticket_mostrar_estabelecimento', '1', NULL, '2026-04-09 04:08:46'),
(30, 'ticket_logo', '1', NULL, '2026-04-09 04:08:46'),
(31, 'ticket_mostrar_data', '1', NULL, '2026-04-09 04:08:46'),
(32, 'ticket_rodape', 'Obrigado pela preferência!', NULL, '2026-04-09 04:08:46'),
(33, 'ticket_colunas', '1', NULL, '2026-04-09 04:08:46'),
(34, 'ticket_borda_estilo', 'simples', NULL, '2026-04-09 04:08:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque_movimentacoes`
--

CREATE TABLE `estoque_movimentacoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `barril_id` int(11) DEFAULT NULL,
  `tipo` enum('entrada','saida','ajuste','perda','abertura_barril') NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `estoque_anterior` decimal(10,3) DEFAULT NULL,
  `estoque_novo` decimal(10,3) DEFAULT NULL,
  `unidade` varchar(20) DEFAULT NULL,
  `custo_unitario` decimal(10,2) DEFAULT 0.00,
  `motivo` varchar(200) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `operador` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `estoque_movimentacoes`
--

INSERT INTO `estoque_movimentacoes` (`id`, `produto_id`, `barril_id`, `tipo`, `quantidade`, `estoque_anterior`, `estoque_novo`, `unidade`, `custo_unitario`, `motivo`, `referencia`, `referencia_id`, `operador`, `created_at`) VALUES
(1, 5, NULL, 'saida', 1.000, 12.000, 11.000, 'unidade', 16.00, 'Venda VND00001', 'venda', 1, 'sistema', '2026-04-08 18:47:58'),
(2, 3, NULL, 'saida', 1.000, 24.000, 23.000, 'unidade', 12.00, 'Venda VND00002', 'venda', 2, 'admin', '2026-04-08 18:55:57'),
(3, 4, NULL, 'saida', 1.000, 48.000, 47.000, 'unidade', 8.00, 'Venda VND00003', 'venda', 3, 'admin', '2026-04-15 00:34:53'),
(4, 4, NULL, 'saida', 1.000, 47.000, 46.000, 'unidade', 8.00, 'Venda VND00004', 'venda', 4, 'admin', '2026-04-15 00:35:01'),
(5, 5, NULL, 'saida', 1.000, 11.000, 10.000, 'unidade', 16.00, 'Venda VND00005', 'venda', 5, 'admin', '2026-04-15 13:20:48'),
(6, 7, NULL, 'ajuste', 12.000, 0.000, 12.000, 'dose', 0.00, 'Entrada: 1 un × 12 dose = 12 dose adicionados', 'ajuste_cadastro', 7, 'admin', '2026-04-15 13:32:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mp_terminais`
--

CREATE TABLE `mp_terminais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `device_id` varchar(150) NOT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('unidade','dose','chopp_lata','chopp_barril','garrafa','drink','combo') DEFAULT 'unidade',
  `capacidade_ml` decimal(10,2) DEFAULT NULL COMMENT 'Para barris: capacidade total em ml',
  `rendimento_pct` decimal(5,2) DEFAULT 85.00 COMMENT 'Percentual útil do barril (padrão 85%)',
  `ml_por_dose` decimal(8,2) DEFAULT NULL COMMENT 'ML por copo/dose servida',
  `preco_custo` decimal(10,2) DEFAULT 0.00,
  `preco_venda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estoque_atual` decimal(10,3) DEFAULT 0.000,
  `estoque_minimo` decimal(10,3) DEFAULT 0.000,
  `unidade_estoque` enum('unidade','litro','ml','dose') DEFAULT 'unidade',
  `codigo_barras` varchar(60) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `composicao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON para drinks e combos' CHECK (json_valid(`composicao`)),
  `ativo` tinyint(1) DEFAULT 1,
  `destaque` tinyint(1) DEFAULT 0,
  `disponivel_pdv` tinyint(1) DEFAULT 1,
  `ordem_pdv` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `categoria_id`, `nome`, `descricao`, `tipo`, `capacidade_ml`, `rendimento_pct`, `ml_por_dose`, `preco_custo`, `preco_venda`, `estoque_atual`, `estoque_minimo`, `unidade_estoque`, `codigo_barras`, `imagem`, `composicao`, `ativo`, `destaque`, `disponivel_pdv`, `ordem_pdv`, `created_at`, `updated_at`) VALUES
(1, 1, 'Chopp Pilsen 300ml', NULL, 'chopp_barril', NULL, 85.00, 300.00, 2.50, 9.00, 0.000, 2.000, 'dose', NULL, NULL, NULL, 1, 1, 1, 1, '2026-04-08 18:42:43', '2026-04-08 18:42:43'),
(2, 1, 'Chopp Weiss 300ml', NULL, 'chopp_barril', NULL, 85.00, 300.00, 3.00, 10.00, 0.000, 2.000, 'dose', NULL, NULL, NULL, 1, 1, 1, 2, '2026-04-08 18:42:43', '2026-04-08 18:42:43'),
(3, 2, 'Heineken Lata 350ml', NULL, 'chopp_lata', NULL, 100.00, 350.00, 4.00, 12.00, 23.000, 6.000, 'unidade', NULL, NULL, NULL, 1, 0, 1, 3, '2026-04-08 18:42:43', '2026-04-08 18:55:57'),
(4, 2, 'Brahma Lata 350ml', NULL, 'chopp_lata', NULL, 100.00, 350.00, 2.50, 8.00, 46.000, 12.000, 'unidade', NULL, NULL, NULL, 1, 0, 1, 4, '2026-04-08 18:42:43', '2026-04-15 00:35:01'),
(5, 2, 'Corona Long Neck 330ml', NULL, 'garrafa', NULL, 100.00, 330.00, 6.00, 16.00, 10.000, 6.000, 'unidade', NULL, NULL, NULL, 1, 0, 1, 5, '2026-04-08 18:42:43', '2026-04-15 13:20:48'),
(6, 3, 'Caipirinha', NULL, 'drink', NULL, NULL, NULL, 3.00, 18.00, 0.000, 0.000, 'unidade', NULL, NULL, NULL, 1, 1, 1, 6, '2026-04-08 18:42:43', '2026-04-08 18:42:43'),
(7, 4, 'Whisky Jack Daniels Dose', '', 'dose', 750.00, 100.00, 60.00, 8.33, 25.00, 12.000, 1.000, 'dose', '', NULL, NULL, 1, 0, 1, 7, '2026-04-08 18:42:43', '2026-04-15 13:32:51'),
(8, 5, 'Água Mineral 500ml', NULL, 'unidade', NULL, NULL, NULL, 1.00, 5.00, 24.000, 12.000, 'unidade', NULL, NULL, NULL, 1, 0, 1, 8, '2026-04-08 18:42:43', '2026-04-08 18:42:43'),
(9, 5, 'Refrigerante Lata 350ml', NULL, 'unidade', NULL, NULL, NULL, 2.00, 7.00, 24.000, 12.000, 'unidade', NULL, NULL, NULL, 1, 0, 1, 9, '2026-04-08 18:42:43', '2026-04-08 18:42:43'),
(10, 6, 'Porção de Batata Frita', NULL, 'unidade', NULL, NULL, NULL, 8.00, 28.00, 0.000, 0.000, 'unidade', NULL, NULL, NULL, 1, 0, 1, 10, '2026-04-08 18:42:43', '2026-04-08 18:42:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `produto_nome` varchar(150) NOT NULL,
  `status` enum('pendente','utilizado','cancelado') DEFAULT 'pendente',
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `utilizado_em` datetime DEFAULT NULL,
  `operador_utilizou` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `tickets`
--

INSERT INTO `tickets` (`id`, `codigo`, `venda_id`, `produto_id`, `produto_nome`, `status`, `criado_em`, `utilizado_em`, `operador_utilizou`) VALUES
(1, 'TKT-QXTU5B', 1, 5, 'Corona Long Neck 330ml', 'pendente', '2026-04-08 18:47:58', NULL, NULL),
(2, 'TKT-0SB8UR', 2, 3, 'Heineken Lata 350ml', 'pendente', '2026-04-08 18:55:57', NULL, NULL),
(3, 'TKT-SGRJKL', 3, 4, 'Brahma Lata 350ml', 'pendente', '2026-04-15 00:34:53', NULL, NULL),
(4, 'TKT-YMK1T8', 4, 4, 'Brahma Lata 350ml', 'pendente', '2026-04-15 00:35:01', NULL, NULL),
(5, 'TKT-WEFSF1', 5, 5, 'Corona Long Neck 330ml', 'pendente', '2026-04-15 13:20:48', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','caixa_bar','caixa_totem') NOT NULL DEFAULT 'caixa_bar',
  `formas_pagamento` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Formas permitidas - NULL significa todas' CHECK (json_valid(`formas_pagamento`)),
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_acesso` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `login`, `senha`, `perfil`, `formas_pagamento`, `ativo`, `ultimo_acesso`, `created_at`) VALUES
(1, 'Administrador', 'admin', '$2y$10$.cBF1zJuQJVsR6Cf/RrZbOmD6stQx3ceSUlY0muOq/zadIUAzBd6C', 'admin', NULL, 1, '2026-04-15 09:52:21', '2026-04-08 18:43:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `caixa_id` int(11) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `data_venda` datetime NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` enum('dinheiro','mercadopago','cortesia','ficha','outro') DEFAULT 'dinheiro',
  `status` enum('pendente','pago','cancelado') DEFAULT 'pendente',
  `mp_order_id` varchar(100) DEFAULT NULL,
  `mp_intent_id` varchar(100) DEFAULT NULL,
  `mp_device_id` varchar(100) DEFAULT NULL,
  `mp_status` varchar(50) DEFAULT NULL,
  `mp_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mp_response`)),
  `mesa` varchar(30) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `caixa_id`, `numero`, `data_venda`, `subtotal`, `desconto`, `total`, `forma_pagamento`, `status`, `mp_order_id`, `mp_intent_id`, `mp_device_id`, `mp_status`, `mp_response`, `mesa`, `observacoes`, `created_at`) VALUES
(1, 1, 'VND00001', '2026-04-08 15:47:58', 16.00, 0.00, 16.00, 'dinheiro', 'pago', NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-04-08 18:47:58'),
(2, 5, 'VND00002', '2026-04-08 15:55:57', 12.00, 0.00, 12.00, 'dinheiro', 'pago', NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-04-08 18:55:57'),
(3, 6, 'VND00003', '2026-04-14 21:34:53', 8.00, 0.00, 8.00, 'dinheiro', 'pago', NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-04-15 00:34:53'),
(4, 6, 'VND00004', '2026-04-14 21:35:01', 8.00, 0.00, 8.00, 'dinheiro', 'pago', NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-04-15 00:35:01'),
(5, 9, 'VND00005', '2026-04-15 10:20:48', 16.00, 0.00, 16.00, 'dinheiro', 'pago', NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-04-15 13:20:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `venda_itens`
--

CREATE TABLE `venda_itens` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `barril_id` int(11) DEFAULT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `quantidade` decimal(10,3) DEFAULT 1.000,
  `preco_unitario` decimal(10,2) NOT NULL,
  `desconto_item` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `venda_itens`
--

INSERT INTO `venda_itens` (`id`, `venda_id`, `produto_id`, `barril_id`, `descricao`, `quantidade`, `preco_unitario`, `desconto_item`, `total`) VALUES
(1, 1, 5, NULL, 'Corona Long Neck 330ml', 1.000, 16.00, 0.00, 16.00),
(2, 2, 3, NULL, 'Heineken Lata 350ml', 1.000, 12.00, 0.00, 12.00),
(3, 3, 4, NULL, 'Brahma Lata 350ml', 1.000, 8.00, 0.00, 8.00),
(4, 4, 4, NULL, 'Brahma Lata 350ml', 1.000, 8.00, 0.00, 8.00),
(5, 5, 5, NULL, 'Corona Long Neck 330ml', 1.000, 16.00, 0.00, 16.00);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `barris`
--
ALTER TABLE `barris`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `caixas`
--
ALTER TABLE `caixas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `caixa_movimentos`
--
ALTER TABLE `caixa_movimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caixa_id` (`caixa_id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `barril_id` (`barril_id`);

--
-- Índices de tabela `mp_terminais`
--
ALTER TABLE `mp_terminais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `venda_id` (`venda_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `caixa_id` (`caixa_id`);

--
-- Índices de tabela `venda_itens`
--
ALTER TABLE `venda_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `barril_id` (`barril_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `barris`
--
ALTER TABLE `barris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `caixas`
--
ALTER TABLE `caixas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `caixa_movimentos`
--
ALTER TABLE `caixa_movimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de tabela `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `mp_terminais`
--
ALTER TABLE `mp_terminais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `venda_itens`
--
ALTER TABLE `venda_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `barris`
--
ALTER TABLE `barris`
  ADD CONSTRAINT `barris_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `caixa_movimentos`
--
ALTER TABLE `caixa_movimentos`
  ADD CONSTRAINT `caixa_movimentos_ibfk_1` FOREIGN KEY (`caixa_id`) REFERENCES `caixas` (`id`);

--
-- Restrições para tabelas `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  ADD CONSTRAINT `estoque_movimentacoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `estoque_movimentacoes_ibfk_2` FOREIGN KEY (`barril_id`) REFERENCES `barris` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`caixa_id`) REFERENCES `caixas` (`id`);

--
-- Restrições para tabelas `venda_itens`
--
ALTER TABLE `venda_itens`
  ADD CONSTRAINT `venda_itens_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `venda_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `venda_itens_ibfk_3` FOREIGN KEY (`barril_id`) REFERENCES `barris` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
