-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: 179.188.16.99
-- Generation Time: 08-Abr-2026 às 09:13
-- Versão do servidor: 5.7.32-35-log
-- PHP Version: 5.6.40-0+deb8u12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ossystem`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `caixa`
--

CREATE TABLE `caixa` (
  `id` int(11) NOT NULL,
  `data_abertura` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_fechamento` timestamp NULL DEFAULT NULL,
  `saldo_inicial` decimal(10,2) NOT NULL,
  `saldo_final` decimal(10,2) DEFAULT NULL,
  `total_vendas` decimal(10,2) DEFAULT '0.00',
  `total_sangrias` decimal(10,2) DEFAULT '0.00',
  `total_suprimentos` decimal(10,2) DEFAULT '0.00',
  `status` enum('aberto','fechado') DEFAULT 'aberto',
  `usuario_abertura` int(11) DEFAULT NULL,
  `usuario_fechamento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `caixa`
--

INSERT INTO `caixa` (`id`, `data_abertura`, `data_fechamento`, `saldo_inicial`, `saldo_final`, `total_vendas`, `total_sangrias`, `total_suprimentos`, `status`, `usuario_abertura`, `usuario_fechamento`) VALUES
(1, '2026-03-23 18:21:39', '2026-03-24 18:21:39', 100.00, 170.00, 70.00, 0.00, 0.00, 'fechado', 4, 4),
(2, '2026-03-28 18:21:39', '2026-03-29 18:21:39', 100.00, 140.00, 40.00, 0.00, 0.00, 'fechado', 4, 4),
(3, '2026-04-02 18:21:39', NULL, 200.00, NULL, 1403.00, 0.00, 0.00, 'aberto', 4, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `caixa_movimentacoes`
--

CREATE TABLE `caixa_movimentacoes` (
  `id` int(11) NOT NULL,
  `caixa_id` int(11) NOT NULL,
  `tipo` enum('venda','sangria','suprimento') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `caixa_movimentacoes`
--

INSERT INTO `caixa_movimentacoes` (`id`, `caixa_id`, `tipo`, `valor`, `descricao`, `venda_id`, `created_at`) VALUES
(1, 3, 'venda', 120.00, 'Venda: VENDA-20260402155736', 6, '2026-04-02 18:57:36'),
(2, 3, 'venda', 18.00, 'Venda: VENDA-20260407104630', 7, '2026-04-07 13:46:30'),
(3, 3, 'venda', 45.00, 'Venda: VENDA-20260407114439', 8, '2026-04-07 14:44:39'),
(4, 3, 'venda', 540.00, 'Venda: VENDA-20260407121845', 9, '2026-04-07 15:18:45'),
(5, 3, 'venda', 180.00, 'Venda: VENDA-20260407214631', 10, '2026-04-08 00:46:31'),
(6, 3, 'venda', 45.00, 'Venda: VENDA-20260407214649', 11, '2026-04-08 00:46:49'),
(7, 3, 'venda', 250.00, 'Venda: VENDA-20260407214916', 12, '2026-04-08 00:49:16'),
(8, 3, 'venda', 180.00, 'Venda: VENDA-20260407215636', 13, '2026-04-08 00:56:36'),
(9, 3, 'venda', 25.00, 'Venda: VENDA-20260407220317', 14, '2026-04-08 01:03:17');

-- --------------------------------------------------------

--
-- Estrutura da tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `cpf_cnpj`, `telefone`, `email`, `endereco`, `created_at`) VALUES
(1, 'João Silva', '123.456.789-00', '(11) 99999-9999', 'joao.silva@email.com', 'Rua das Flores, 123 - São Paulo/SP', '2026-04-02 18:21:39'),
(2, 'Maria Santos', '987.654.321-00', '(11) 88888-8888', 'maria.santos@email.com', 'Av. Brasil, 456 - Rio de Janeiro/RJ', '2026-04-02 18:21:39'),
(3, 'Carlos Oliveira', '111.222.333-44', '(21) 77777-7777', 'carlos.oliveira@email.com', 'Rua Augusta, 789 - Belo Horizonte/MG', '2026-04-02 18:21:39'),
(4, 'Ana Souza', '555.666.777-88', '(31) 66666-6666', 'ana.souza@email.com', 'Av. Paulista, 1000 - São Paulo/SP', '2026-04-02 18:21:39'),
(5, 'Roberto Lima', '999.888.777-66', '(41) 55555-5555', 'roberto.lima@email.com', 'Rua XV de Novembro, 200 - Curitiba/PR', '2026-04-02 18:21:39');

-- --------------------------------------------------------

--
-- Estrutura da tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome`, `cnpj`, `telefone`, `email`, `endereco`, `created_at`) VALUES
(1, 'Peças Honda', '12.345.678/0001-90', '(11) 4000-0000', 'vendas@pecashonda.com.br', 'Av. Honda, 100 - São Paulo/SP', '2026-04-02 18:21:39'),
(2, 'Peças Yamaha', '98.765.432/0001-10', '(11) 5000-0000', 'vendas@pecasyamaha.com.br', 'Rua Yamaha, 200 - São Paulo/SP', '2026-04-02 18:21:39'),
(3, 'Motoparts Brasil', '11.222.333/0001-44', '(11) 6000-0000', 'contato@motoparts.com.br', 'Av. das Peças, 300 - São Paulo/SP', '2026-04-02 18:21:39');

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs_atividades`
--

CREATE TABLE `logs_atividades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  `tabela` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `mao_de_obra`
--

CREATE TABLE `mao_de_obra` (
  `id` int(11) NOT NULL,
  `valor_hora` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descricao` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Extraindo dados da tabela `mao_de_obra`
--

INSERT INTO `mao_de_obra` (`id`, `valor_hora`, `descricao`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 100.00, '', 1, '2026-04-07 19:15:52', '2026-04-08 00:35:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `motos`
--

CREATE TABLE `motos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `ano` int(4) DEFAULT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `chassi` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `motos`
--

INSERT INTO `motos` (`id`, `cliente_id`, `placa`, `modelo`, `marca`, `ano`, `cor`, `chassi`, `created_at`) VALUES
(1, 1, 'ABC-1234', 'CG 160', 'Honda', 2022, 'Vermelha', '9C2JE0810NR000001', '2026-04-02 18:21:39'),
(2, 1, 'DEF-5678', 'Factor 150', 'Yamaha', 2021, 'Azul', '9C2JE0810NR000002', '2026-04-02 18:21:39'),
(4, 2, 'JKL-3456', 'Pop 100', 'Honda', 2020, 'Branca', '9C2JE0810NR000004', '2026-04-02 18:21:39'),
(5, 3, 'MNO-7890', 'Fazer 250', 'Yamaha', 2022, 'Preta', '9C2JE0810NR000005', '2026-04-02 18:21:39'),
(6, 3, 'PQR-1234', 'Titan 150', 'Honda', 2021, 'Cinza', '9C2JE0810NR000006', '2026-04-02 18:21:39'),
(7, 4, 'STU-5678', 'Bros 160', 'Honda', 2023, 'Vermelha', '9C2JE0810NR000007', '2026-04-02 18:21:39'),
(8, 5, 'VWX-9012', 'XRE 300', 'Honda', 2022, 'Preta', '9C2JE0810NR000008', '2026-04-02 18:21:39'),
(9, 5, 'DYY7J21', '883', 'Harley', 0, '', '', '2026-04-07 16:00:44');

-- --------------------------------------------------------

--
-- Estrutura da tabela `movimentacoes_estoque`
--

CREATE TABLE `movimentacoes_estoque` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(100) DEFAULT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `movimentacoes_estoque`
--

INSERT INTO `movimentacoes_estoque` (`id`, `produto_id`, `tipo`, `quantidade`, `motivo`, `documento`, `created_by`, `created_at`) VALUES
(1, 1, 'entrada', 50, 'Compra inicial', 'NF-001', 1, '2026-04-02 18:21:39'),
(2, 2, 'entrada', 30, 'Compra inicial', 'NF-001', 1, '2026-04-02 18:21:39'),
(3, 3, 'entrada', 20, 'Compra inicial', 'NF-001', 1, '2026-04-02 18:21:39'),
(4, 1, 'saida', 2, 'Venda PDV', 'VENDA-20250101', 4, '2026-04-02 18:21:39'),
(5, 2, 'saida', 1, 'Venda PDV', 'VENDA-20250101', 4, '2026-04-02 18:21:39'),
(6, 1, 'entrada', 10, 'Compra', 'NF-002', 1, '2026-04-02 18:21:39'),
(7, 3, 'saida', 1, 'OS-00003', 'OS-00003', 3, '2026-04-02 18:21:39'),
(8, 4, 'saida', 1, 'OS-00004', 'OS-00004', 3, '2026-04-02 18:21:39'),
(9, 8, 'entrada', 1, 'Compra', '', 1, '2026-04-02 18:57:15'),
(10, 4, 'saida', 1, 'Venda PDV', 'VENDA-20260402155736', 1, '2026-04-02 18:57:36'),
(11, 6, 'entrada', 16, 'Compra', '', 1, '2026-04-07 07:37:48'),
(12, 5, 'saida', 1, 'Venda PDV', 'VENDA-20260407104630', 1, '2026-04-07 13:46:30'),
(13, 3, 'saida', 1, 'Venda PDV', 'VENDA-20260407114439', 1, '2026-04-07 14:44:39'),
(14, 1, 'entrada', 20, 'Compra', '', 1, '2026-04-07 14:48:24'),
(15, 7, 'entrada', 10, 'Compra', '', 1, '2026-04-07 14:48:40'),
(16, 8, 'saida', 3, 'Venda PDV', 'VENDA-20260407121845', 1, '2026-04-07 15:18:45'),
(17, 8, 'saida', 1, 'Venda PDV', 'VENDA-20260407214631', 1, '2026-04-08 00:46:31'),
(18, 3, 'saida', 1, 'Venda PDV', 'VENDA-20260407214649', 1, '2026-04-08 00:46:49'),
(19, 6, 'saida', 1, 'Venda PDV', 'VENDA-20260407214916', 1, '2026-04-08 00:49:16'),
(20, 8, 'saida', 1, 'Venda PDV', 'VENDA-20260407215636', 1, '2026-04-08 00:56:36'),
(21, 2, 'saida', 1, 'Venda PDV', 'VENDA-20260407220317', 1, '2026-04-08 01:03:17');

-- --------------------------------------------------------

--
-- Estrutura da tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL,
  `numero_orcamento` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_validade` date NOT NULL,
  `status` enum('ativo','aprovado','rejeitado','convertido') DEFAULT 'ativo',
  `observacoes` text,
  `convertido_os_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `orcamentos`
--

INSERT INTO `orcamentos` (`id`, `numero_orcamento`, `cliente_id`, `moto_id`, `data_criacao`, `data_validade`, `status`, `observacoes`, `convertido_os_id`) VALUES
(1, 'ORC-00001', 1, 1, '2026-04-02 18:21:39', '2026-05-02', 'convertido', 'Orçamento convertido em OS-00001', NULL),
(2, 'ORC-00002', 2, 3, '2026-04-02 18:21:39', '2026-05-02', 'convertido', 'Aguardando aprovação', 7),
(3, 'ORC-00003', 3, 5, '2026-04-02 18:21:39', '2026-05-02', 'ativo', 'Cliente vai retornar', NULL),
(4, 'ORC-00004', 4, 7, '2026-04-02 18:21:39', '2026-05-02', 'aprovado', 'Aprovado pelo cliente', NULL),
(5, 'ORC-00005', 5, 8, '2026-04-02 18:21:39', '2026-05-02', 'rejeitado', 'Cliente achou caro', NULL),
(6, 'ORC-00006', 5, 9, '2026-04-07 16:01:29', '2026-04-10', 'convertido', '', 8),
(7, 'ORC-00007', 5, 9, '2026-04-07 19:17:26', '2026-04-10', 'convertido', '', 9);

-- --------------------------------------------------------

--
-- Estrutura da tabela `orcamento_itens`
--

CREATE TABLE `orcamento_itens` (
  `id` int(11) NOT NULL,
  `orcamento_id` int(11) NOT NULL,
  `tipo` enum('servico','produto') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `orcamento_itens`
--

INSERT INTO `orcamento_itens` (`id`, `orcamento_id`, `tipo`, `item_id`, `quantidade`, `valor_unitario`) VALUES
(1, 1, 'servico', 1, 1, 50.00),
(2, 1, 'servico', 2, 1, 200.00),
(3, 1, 'produto', 1, 2, 35.00),
(4, 2, 'servico', 2, 1, 200.00),
(5, 2, 'servico', 4, 1, 60.00),
(6, 2, 'produto', 1, 1, 35.00),
(7, 3, 'servico', 3, 1, 80.00),
(8, 4, 'servico', 5, 1, 40.00),
(9, 4, 'produto', 4, 1, 120.00),
(10, 5, 'servico', 1, 1, 50.00),
(11, 5, 'produto', 1, 1, 35.00),
(12, 6, 'servico', 10, 1, 40.00),
(13, 7, 'servico', 2, 1, 200.00);

-- --------------------------------------------------------

--
-- Estrutura da tabela `ordens_servico`
--

CREATE TABLE `ordens_servico` (
  `id` int(11) NOT NULL,
  `numero_os` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `status` enum('aberta','em_andamento','aguardando_pecas','finalizada','cancelada') DEFAULT 'aberta',
  `data_abertura` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_previsao` date DEFAULT NULL,
  `data_finalizacao` date DEFAULT NULL,
  `observacoes` text,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `ordens_servico`
--

INSERT INTO `ordens_servico` (`id`, `numero_os`, `cliente_id`, `moto_id`, `status`, `data_abertura`, `data_previsao`, `data_finalizacao`, `observacoes`, `created_by`) VALUES
(1, 'OS-00001', 1, 1, 'finalizada', '2026-03-18 18:21:39', '2026-03-21', NULL, 'Troca de óleo e revisão básica', 3),
(2, 'OS-00002', 2, 3, 'em_andamento', '2026-03-28 18:21:39', '2026-04-04', NULL, 'Revisão completa agendada', 3),
(3, 'OS-00003', 3, 5, 'finalizada', '2026-03-31 18:21:39', '2026-04-07', '2026-04-07', 'Troca de pastilhas de freio', 3),
(4, 'OS-00004', 4, 7, 'aguardando_pecas', '2026-03-26 18:21:39', '2026-04-05', NULL, 'Aguardando chegada do pneu', 3),
(5, 'OS-00005', 5, 8, 'finalizada', '2026-03-13 18:21:39', '2026-03-15', NULL, 'Troca de óleo e filtro', 3),
(6, 'OS-00006', 1, 2, 'cancelada', '2026-04-01 18:21:39', '2026-04-09', NULL, 'Barulho no motor', 3),
(7, 'OS-00007', 2, 3, 'aberta', '2026-04-07 14:47:30', NULL, NULL, 'Aguardando aprovação', 1),
(8, 'OS-00008', 5, 9, 'finalizada', '2026-04-07 16:01:38', NULL, '2026-04-07', '', 1),
(9, 'OS-00009', 5, 9, 'em_andamento', '2026-04-07 19:17:47', NULL, NULL, '', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `os_produtos`
--

CREATE TABLE `os_produtos` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `os_produtos`
--

INSERT INTO `os_produtos` (`id`, `os_id`, `produto_id`, `quantidade`, `valor_unitario`) VALUES
(1, 1, 1, 2, 35.00),
(2, 1, 2, 1, 25.00),
(3, 2, 1, 1, 35.00),
(4, 2, 3, 2, 45.00),
(5, 3, 3, 1, 45.00),
(6, 4, 4, 1, 120.00),
(7, 5, 1, 1, 35.00),
(8, 5, 2, 1, 25.00),
(9, 6, 1, 1, 35.00),
(10, 6, 8, 1, 4444.00),
(11, 7, 1, 1, 35.00),
(12, 8, 1, 4, 35.00);

-- --------------------------------------------------------

--
-- Estrutura da tabela `os_servicos`
--

CREATE TABLE `os_servicos` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `mecanico_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT '1',
  `valor_unitario` decimal(10,2) NOT NULL,
  `tempo_gasto` int(11) DEFAULT NULL,
  `garantia_dias` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `os_servicos`
--

INSERT INTO `os_servicos` (`id`, `os_id`, `servico_id`, `mecanico_id`, `quantidade`, `valor_unitario`, `tempo_gasto`, `garantia_dias`) VALUES
(1, 1, 1, 3, 1, 50.00, NULL, NULL),
(2, 1, 2, 3, 1, 200.00, NULL, NULL),
(3, 2, 2, 3, 1, 200.00, NULL, NULL),
(4, 2, 4, 3, 1, 60.00, NULL, NULL),
(5, 3, 3, 3, 1, 80.00, NULL, NULL),
(6, 4, 5, 3, 1, 40.00, NULL, NULL),
(7, 5, 1, 3, 1, 50.00, NULL, NULL),
(8, 6, 1, 3, 1, 50.00, NULL, NULL),
(9, 6, 6, 3, 1, 150.00, NULL, NULL),
(10, 6, 4, NULL, 1, 4444.00, NULL, NULL),
(11, 7, 2, NULL, 1, 200.00, NULL, NULL),
(12, 7, 4, NULL, 1, 60.00, NULL, NULL),
(13, 8, 10, NULL, 1, 40.00, NULL, NULL),
(14, 8, 8, NULL, 1, 100.00, NULL, NULL),
(20, 9, 2, 13, 1, 200.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `os_status_log`
--

CREATE TABLE `os_status_log` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `status_anterior` varchar(20) DEFAULT NULL,
  `status_novo` varchar(20) NOT NULL,
  `observacao` text,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `os_status_log`
--

INSERT INTO `os_status_log` (`id`, `os_id`, `status_anterior`, `status_novo`, `observacao`, `usuario_id`, `created_at`) VALUES
(1, 6, 'aberta', 'em_andamento', '', 1, '2026-04-07 07:34:53'),
(2, 6, 'em_andamento', 'aguardando_pecas', '', 1, '2026-04-07 07:34:56'),
(3, 3, 'aberta', 'em_andamento', '', 1, '2026-04-07 14:45:46'),
(4, 3, 'em_andamento', 'finalizada', '', 1, '2026-04-07 14:46:06'),
(5, 8, 'aberta', 'em_andamento', '', 1, '2026-04-07 16:02:21'),
(6, 8, 'em_andamento', 'finalizada', '', 1, '2026-04-07 16:03:05'),
(7, 6, 'aguardando_pecas', 'cancelada', '', 1, '2026-04-07 16:19:48'),
(8, 9, 'aberta', 'em_andamento', '', 1, '2026-04-07 19:18:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL,
  `perfil` varchar(20) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `acao` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `permissoes`
--

INSERT INTO `permissoes` (`id`, `perfil`, `modulo`, `acao`) VALUES
(33, 'admin', 'caixa', 'abrir'),
(34, 'admin', 'caixa', 'fechar'),
(32, 'admin', 'caixa', 'ver'),
(18, 'admin', 'clientes', 'criar'),
(19, 'admin', 'clientes', 'editar'),
(20, 'admin', 'clientes', 'excluir'),
(17, 'admin', 'clientes', 'ver'),
(1, 'admin', 'dashboard', 'ver'),
(26, 'admin', 'estoque', 'entrada'),
(25, 'admin', 'estoque', 'ver'),
(12, 'admin', 'orcamentos', 'converter'),
(9, 'admin', 'orcamentos', 'criar'),
(10, 'admin', 'orcamentos', 'editar'),
(11, 'admin', 'orcamentos', 'excluir'),
(8, 'admin', 'orcamentos', 'ver'),
(5, 'admin', 'os', 'criar'),
(6, 'admin', 'os', 'editar'),
(7, 'admin', 'os', 'excluir'),
(4, 'admin', 'os', 'ver'),
(3, 'admin', 'pdv', 'finalizar'),
(2, 'admin', 'pdv', 'ver'),
(14, 'admin', 'produtos', 'criar'),
(15, 'admin', 'produtos', 'editar'),
(16, 'admin', 'produtos', 'excluir'),
(13, 'admin', 'produtos', 'ver'),
(27, 'admin', 'relatorios', 'ver'),
(22, 'admin', 'servicos', 'criar'),
(23, 'admin', 'servicos', 'editar'),
(24, 'admin', 'servicos', 'excluir'),
(21, 'admin', 'servicos', 'ver'),
(29, 'admin', 'usuarios', 'criar'),
(30, 'admin', 'usuarios', 'editar'),
(31, 'admin', 'usuarios', 'excluir'),
(28, 'admin', 'usuarios', 'ver'),
(72, 'caixa', 'caixa', 'abrir'),
(73, 'caixa', 'caixa', 'fechar'),
(71, 'caixa', 'caixa', 'ver'),
(74, 'caixa', 'clientes', 'ver'),
(68, 'caixa', 'dashboard', 'ver'),
(70, 'caixa', 'pdv', 'finalizar'),
(69, 'caixa', 'pdv', 'ver'),
(75, 'caixa', 'relatorios', 'ver'),
(63, 'gerente', 'caixa', 'abrir'),
(64, 'gerente', 'caixa', 'fechar'),
(62, 'gerente', 'caixa', 'ver'),
(52, 'gerente', 'clientes', 'criar'),
(53, 'gerente', 'clientes', 'editar'),
(54, 'gerente', 'clientes', 'excluir'),
(51, 'gerente', 'clientes', 'ver'),
(35, 'gerente', 'dashboard', 'ver'),
(60, 'gerente', 'estoque', 'entrada'),
(59, 'gerente', 'estoque', 'ver'),
(46, 'gerente', 'orcamentos', 'converter'),
(43, 'gerente', 'orcamentos', 'criar'),
(44, 'gerente', 'orcamentos', 'editar'),
(45, 'gerente', 'orcamentos', 'excluir'),
(42, 'gerente', 'orcamentos', 'ver'),
(39, 'gerente', 'os', 'criar'),
(40, 'gerente', 'os', 'editar'),
(41, 'gerente', 'os', 'excluir'),
(38, 'gerente', 'os', 'ver'),
(37, 'gerente', 'pdv', 'finalizar'),
(36, 'gerente', 'pdv', 'ver'),
(48, 'gerente', 'produtos', 'criar'),
(49, 'gerente', 'produtos', 'editar'),
(50, 'gerente', 'produtos', 'excluir'),
(47, 'gerente', 'produtos', 'ver'),
(61, 'gerente', 'relatorios', 'ver'),
(56, 'gerente', 'servicos', 'criar'),
(57, 'gerente', 'servicos', 'editar'),
(58, 'gerente', 'servicos', 'excluir'),
(55, 'gerente', 'servicos', 'ver'),
(65, 'mecanico', 'dashboard', 'ver'),
(67, 'mecanico', 'os', 'editar'),
(66, 'mecanico', 'os', 'ver'),
(82, 'vendedor', 'clientes', 'criar'),
(81, 'vendedor', 'clientes', 'ver'),
(76, 'vendedor', 'dashboard', 'ver'),
(80, 'vendedor', 'orcamentos', 'criar'),
(79, 'vendedor', 'orcamentos', 'ver'),
(78, 'vendedor', 'pdv', 'finalizar'),
(77, 'vendedor', 'pdv', 'ver');

-- --------------------------------------------------------

--
-- Estrutura da tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `preco_compra` decimal(10,2) DEFAULT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `estoque_atual` int(11) DEFAULT '0',
  `estoque_minimo` int(11) DEFAULT '5',
  `unidade` varchar(5) DEFAULT 'UN',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `produtos`
--

INSERT INTO `produtos` (`id`, `codigo_barras`, `nome`, `descricao`, `preco_compra`, `preco_venda`, `estoque_atual`, `estoque_minimo`, `unidade`, `created_at`) VALUES
(1, '7891234560010', 'Óleo Motor 20W50 1L', 'Óleo para motor 20W50 - 1 litro', 25.00, 35.00, 65, 10, 'LT', '2026-04-02 18:21:39'),
(2, '7891234560027', 'Filtro de Óleo', 'Filtro de óleo para motos', 15.00, 25.00, 29, 8, 'PC', '2026-04-02 18:21:39'),
(3, '7891234560034', 'Pastilha de Freio Dianteira', 'Pastilha de freio dianteira', 30.00, 45.00, 18, 5, 'PC', '2026-04-02 18:21:39'),
(4, '7891234560041', 'Corrente de Transmissão', 'Corrente para transmissão', 80.00, 120.00, 14, 5, 'PC', '2026-04-02 18:21:39'),
(5, '7891234560058', 'Vela de Ignição', 'Vela de ignição NGK', 10.00, 18.00, 39, 10, 'PC', '2026-04-02 18:21:39'),
(6, '7891234560065', 'Pneu Dianteiro', 'Pneu dianteiro 90/90-18', 180.00, 250.00, 24, 5, 'PC', '2026-04-02 18:21:39'),
(7, '7891234560072', 'Pneu Traseiro', 'Pneu traseiro 110/90-18', 200.00, 280.00, 18, 5, 'PC', '2026-04-02 18:21:39'),
(8, '7891234560089', 'Bateria 12V', 'Bateria para moto 12V', 120.00, 180.00, 7, 5, 'PC', '2026-04-02 18:21:39'),
(9, '7891234560096', 'Farol LED', 'Farol em LED', 90.00, 150.00, 25, 10, 'PC', '2026-04-02 18:21:39'),
(10, '7891234560102', 'Retrovisor', 'Par de retrovisores', 35.00, 60.00, 18, 8, 'PAR', '2026-04-02 18:21:39');

-- --------------------------------------------------------

--
-- Estrutura da tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `valor` decimal(10,2) DEFAULT '0.00',
  `tempo_estimado` int(11) DEFAULT NULL,
  `garantia_dias` int(11) DEFAULT '30',
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `valor`, `tempo_estimado`, `garantia_dias`, `ativo`, `created_at`) VALUES
(1, 'Troca de Óleo', 'Troca de óleo do motor + filtro', 0.00, 30, 1000, 1, '2026-04-02 18:21:39'),
(2, 'Revisão Completa', 'Revisão completa da moto', 0.00, 120, 30, 1, '2026-04-02 18:21:39'),
(3, 'Troca de Pastilhas de Freio', 'Substituição das pastilhas de freio', 0.00, 60, 30, 1, '2026-04-02 18:21:39'),
(4, 'Alinhamento e Balanceamento', 'Alinhamento e balanceamento das rodas', 0.00, 45, 30, 1, '2026-04-02 18:21:39'),
(5, 'Troca de Corrente', 'Substituição da corrente de transmissão', 0.00, 50, 30, 1, '2026-04-02 18:21:39'),
(6, 'Revisão Preventiva', 'Revisão preventiva completa', 0.00, 90, 60, 1, '2026-04-02 18:21:39'),
(7, 'Troca de Pneus', 'Troca do par de pneus', 0.00, 60, 30, 1, '2026-04-02 18:21:39'),
(8, 'Regulagem de Motor', 'Regulagem completa do motor', 0.00, 120, 30, 1, '2026-04-02 18:21:39'),
(9, 'Troca de Bateria', 'Substituição da bateria', 0.00, 20, 30, 1, '2026-04-02 18:21:39'),
(10, 'Lavagem Especial', 'Lavagem completa da moto', 0.00, 60, 0, 1, '2026-04-02 18:21:39'),
(11, 'teste', '', 0.00, 7, 30, 1, '2026-04-07 07:37:20');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','gerente','mecanico','caixa','vendedor') NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`) VALUES
(1, 'Administrador', 'admin@os-system.com', '$2y$10$7XFWoIjz1XcJHLUf1NRqC.Ql7oh8qW3e69LlpQP3M6tKiwHXAOwzm', 'admin', 1, '2026-04-02 17:47:25'),
(12, 'João Gerente', 'gerente@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente', 1, '2026-04-02 18:21:39'),
(13, 'Carlos Mecânico', 'mecanico@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mecanico', 1, '2026-04-02 18:21:39'),
(14, 'Ana Caixa', 'caixa@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caixa', 1, '2026-04-02 18:21:39'),
(15, 'Pedro Vendedor', 'vendedor@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 1, '2026-04-02 18:21:39');

-- --------------------------------------------------------

--
-- Estrutura da tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `numero_venda` varchar(20) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `data_venda` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','cartao_credito','cartao_debito','boleto','mix') NOT NULL,
  `parcelas` int(11) DEFAULT '1',
  `status` enum('finalizada','cancelada') DEFAULT 'finalizada',
  `caixa_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `mp_payment_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `vendas`
--

INSERT INTO `vendas` (`id`, `numero_venda`, `cliente_id`, `data_venda`, `subtotal`, `desconto`, `total`, `forma_pagamento`, `parcelas`, `status`, `caixa_id`, `created_by`, `mp_payment_id`) VALUES
(1, 'VENDA-20250101001', 1, '2026-03-23 18:21:39', 70.00, 0.00, 70.00, 'pix', 1, 'finalizada', 1, 4, NULL),
(2, 'VENDA-20250102002', 2, '2026-03-25 18:21:39', 45.00, 5.00, 40.00, 'dinheiro', 1, 'finalizada', 1, 4, NULL),
(3, 'VENDA-20250103003', NULL, '2026-03-28 18:21:39', 35.00, 0.00, 35.00, 'cartao_credito', 1, 'finalizada', 1, 4, NULL),
(4, 'VENDA-20250104004', 3, '2026-03-30 18:21:39', 120.00, 10.00, 110.00, 'pix', 1, 'finalizada', 1, 4, NULL),
(5, 'VENDA-20250105005', 4, '2026-04-01 18:21:39', 60.00, 0.00, 60.00, 'dinheiro', 1, 'finalizada', 1, 4, NULL),
(6, 'VENDA-20260402155736', NULL, '2026-04-02 18:57:36', 120.00, 0.00, 120.00, 'dinheiro', 1, 'finalizada', 3, 1, NULL),
(7, 'VENDA-20260407104630', NULL, '2026-04-07 13:46:30', 18.00, 0.00, 18.00, 'dinheiro', 1, 'finalizada', 3, 1, NULL),
(8, 'VENDA-20260407114439', NULL, '2026-04-07 14:44:39', 45.00, 0.00, 45.00, 'dinheiro', 1, 'finalizada', 3, 1, NULL),
(9, 'VENDA-20260407121845', NULL, '2026-04-07 15:18:45', 540.00, 0.00, 540.00, 'dinheiro', 1, 'finalizada', 3, 1, NULL),
(10, 'VENDA-20260407214631', NULL, '2026-04-08 00:46:31', 180.00, 0.00, 180.00, 'dinheiro', 1, 'finalizada', 3, 1, NULL),
(11, 'VENDA-20260407214649', NULL, '2026-04-08 00:46:49', 45.00, 0.00, 45.00, 'cartao_credito', 1, 'finalizada', 3, 1, NULL),
(12, 'VENDA-20260407214916', NULL, '2026-04-08 00:49:16', 250.00, 0.00, 250.00, 'cartao_credito', 1, 'finalizada', 3, 1, NULL),
(13, 'VENDA-20260407215636', NULL, '2026-04-08 00:56:36', 180.00, 0.00, 180.00, 'dinheiro', 1, 'finalizada', 3, 1, NULL),
(14, 'VENDA-20260407220317', NULL, '2026-04-08 01:03:17', 25.00, 0.00, 25.00, 'pix', 1, 'finalizada', 3, 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `venda_itens`
--

CREATE TABLE `venda_itens` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `venda_itens`
--

INSERT INTO `venda_itens` (`id`, `venda_id`, `produto_id`, `quantidade`, `valor_unitario`, `total`) VALUES
(1, 1, 1, 2, 35.00, 70.00),
(2, 2, 3, 1, 45.00, 45.00),
(3, 3, 1, 1, 35.00, 35.00),
(4, 4, 4, 1, 120.00, 120.00),
(5, 5, 5, 2, 18.00, 36.00),
(6, 5, 6, 1, 250.00, 250.00),
(7, 6, 4, 1, 120.00, 120.00),
(8, 7, 5, 1, 18.00, 18.00),
(9, 8, 3, 1, 45.00, 45.00),
(10, 9, 8, 3, 180.00, 540.00),
(11, 10, 8, 1, 180.00, 180.00),
(12, 11, 3, 1, 45.00, 45.00),
(13, 12, 6, 1, 250.00, 250.00),
(14, 13, 8, 1, 180.00, 180.00),
(15, 14, 2, 1, 25.00, 25.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `caixa`
--
ALTER TABLE `caixa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `caixa_movimentacoes`
--
ALTER TABLE `caixa_movimentacoes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs_atividades`
--
ALTER TABLE `logs_atividades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mao_de_obra`
--
ALTER TABLE `mao_de_obra`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `motos`
--
ALTER TABLE `motos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_orcamento` (`numero_orcamento`);

--
-- Indexes for table `orcamento_itens`
--
ALTER TABLE `orcamento_itens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_os` (`numero_os`);

--
-- Indexes for table `os_produtos`
--
ALTER TABLE `os_produtos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `os_servicos`
--
ALTER TABLE `os_servicos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `os_status_log`
--
ALTER TABLE `os_status_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permissao` (`perfil`,`modulo`,`acao`);

--
-- Indexes for table `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`);

--
-- Indexes for table `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_venda` (`numero_venda`);

--
-- Indexes for table `venda_itens`
--
ALTER TABLE `venda_itens`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `caixa`
--
ALTER TABLE `caixa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `caixa_movimentacoes`
--
ALTER TABLE `caixa_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `logs_atividades`
--
ALTER TABLE `logs_atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mao_de_obra`
--
ALTER TABLE `mao_de_obra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `motos`
--
ALTER TABLE `motos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orcamento_itens`
--
ALTER TABLE `orcamento_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ordens_servico`
--
ALTER TABLE `ordens_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `os_produtos`
--
ALTER TABLE `os_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `os_servicos`
--
ALTER TABLE `os_servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `os_status_log`
--
ALTER TABLE `os_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `venda_itens`
--
ALTER TABLE `venda_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
