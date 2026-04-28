-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 28/04/2026 às 19:52
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
-- Banco de dados: `ossystem`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixa`
--

CREATE TABLE `caixa` (
  `id` int(11) NOT NULL,
  `data_abertura` timestamp NULL DEFAULT current_timestamp(),
  `data_fechamento` timestamp NULL DEFAULT NULL,
  `saldo_inicial` decimal(10,2) NOT NULL,
  `saldo_final` decimal(10,2) DEFAULT NULL,
  `total_vendas` decimal(10,2) DEFAULT 0.00,
  `total_sangrias` decimal(10,2) DEFAULT 0.00,
  `total_suprimentos` decimal(10,2) DEFAULT 0.00,
  `status` enum('aberto','fechado') DEFAULT 'aberto',
  `usuario_abertura` int(11) DEFAULT NULL,
  `usuario_fechamento` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `caixa`
--

INSERT INTO `caixa` (`id`, `data_abertura`, `data_fechamento`, `saldo_inicial`, `saldo_final`, `total_vendas`, `total_sangrias`, `total_suprimentos`, `status`, `usuario_abertura`, `usuario_fechamento`, `created_by`) VALUES
(1, '2026-03-23 18:21:39', '2026-03-24 18:21:39', 100.00, 170.00, 70.00, 0.00, 0.00, 'fechado', 4, 4, 4),
(2, '2026-03-28 18:21:39', '2026-03-29 18:21:39', 100.00, 140.00, 40.00, 0.00, 0.00, 'fechado', 4, 4, 4),
(3, '2026-04-02 18:21:39', '2026-04-12 20:48:13', 200.00, 1603.00, 1403.00, 0.00, 0.00, 'fechado', 4, 1, 4),
(4, '2026-04-15 13:21:00', '2026-04-16 15:00:44', 10000.00, 1048000.00, 480.00, 0.00, 0.00, 'fechado', 1, 1, 1),
(5, '2026-04-18 23:03:33', '2026-04-18 23:06:42', 10000.00, 1002500.00, 25.00, 0.00, 0.00, 'fechado', 1, 1, NULL),
(6, '2026-04-18 23:06:48', '2026-04-20 00:20:00', 100.00, 12500.00, 25.00, 0.00, 0.00, 'fechado', 1, 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixa_movimentacoes`
--

CREATE TABLE `caixa_movimentacoes` (
  `id` int(11) NOT NULL,
  `caixa_id` int(11) NOT NULL,
  `tipo` enum('venda','sangria','suprimento') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `caixa_movimentacoes`
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
(9, 3, 'venda', 25.00, 'Venda: VENDA-20260407220317', 14, '2026-04-08 01:03:17'),
(10, 4, 'venda', 480.00, 'Venda: VENDA-2026041515080566', 15, '2026-04-15 18:08:05'),
(11, 5, 'venda', 25.00, 'Venda: VD260418200338', 16, '2026-04-18 23:03:38'),
(12, 6, 'venda', 25.00, 'Venda: VD260419193052', 17, '2026-04-19 22:30:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_produtos`
--

CREATE TABLE `categorias_produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `cor` varchar(7) DEFAULT '#f59e0b' COMMENT 'Hex color for UI badge',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias_produtos`
--

INSERT INTO `categorias_produtos` (`id`, `nome`, `descricao`, `cor`, `ativo`, `created_at`) VALUES
(1, 'Óleo e Lubrificantes', NULL, '#f59e0b', 1, '2026-04-15 17:54:32'),
(2, 'Filtros', NULL, '#22c55e', 1, '2026-04-15 17:54:32'),
(3, 'Freios', NULL, '#ef4444', 1, '2026-04-15 17:54:32'),
(4, 'Pneus e Rodas', NULL, '#3b82f6', 1, '2026-04-15 17:54:32'),
(5, 'Elétrica', NULL, '#a855f7', 1, '2026-04-15 17:54:32'),
(6, 'Motor', NULL, '#f97316', 1, '2026-04-15 17:54:32'),
(7, 'Transmissão', NULL, '#06b6d4', 1, '2026-04-15 17:54:32'),
(8, 'Escapamento', NULL, '#64748b', 1, '2026-04-15 17:54:32'),
(9, 'Carroceria e Acessórios', NULL, '#ec4899', 1, '2026-04-15 17:54:32'),
(10, 'Parafusos e Fixadores', NULL, '#84cc16', 1, '2026-04-15 17:54:32'),
(11, 'Baterias', NULL, '#eab308', 1, '2026-04-15 17:54:32'),
(12, 'Outros', NULL, '#94a3b8', 1, '2026-04-15 17:54:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `tipo` enum('pf','pj') NOT NULL DEFAULT 'pf',
  `nome` varchar(100) NOT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `rg_ie` varchar(30) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `tipo`, `nome`, `cpf_cnpj`, `rg_ie`, `telefone`, `celular`, `email`, `endereco`, `cep`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `observacoes`, `created_at`) VALUES
(1, 'pf', 'João Silva', '123.456.789-00', NULL, '(11) 99999-9999', NULL, 'joao.silva@email.com', 'Rua das Flores, 123 - São Paulo/SP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 18:21:39'),
(2, 'pf', 'Maria Santos', '987.654.321-00', NULL, '(11) 88888-8888', NULL, 'maria.santos@email.com', 'Av. Brasil, 456 - Rio de Janeiro/RJ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 18:21:39'),
(3, 'pf', 'Carlos Oliveira', '111.222.333-44', NULL, '(21) 77777-7777', NULL, 'carlos.oliveira@email.com', 'Rua Augusta, 789 - Belo Horizonte/MG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 18:21:39'),
(4, 'pf', 'Ana Souza', '555.666.777-88', NULL, '(31) 66666-6666', NULL, 'ana.souza@email.com', 'Av. Paulista, 1000 - São Paulo/SP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 18:21:39'),
(5, 'pf', 'Roberto Lima', '999.888.777-66', NULL, '(41) 55555-5555', NULL, 'roberto.lima@email.com', 'Rua XV de Novembro, 200 - Curitiba/PR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 18:21:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `endereco` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome`, `cnpj`, `telefone`, `email`, `contato`, `ativo`, `endereco`, `created_at`, `updated_at`) VALUES
(1, 'Peças Honda', '12.345.678/0001-90', '(11) 4000-0000', 'vendas@pecashonda.com.br', NULL, 1, 'Av. Honda, 100 - São Paulo/SP', '2026-04-02 18:21:39', '2026-04-15 12:55:16'),
(2, 'Peças Yamaha', '98.765.432/0001-10', '(11) 5000-0000', 'vendas@pecasyamaha.com.br', NULL, 1, 'Rua Yamaha, 200 - São Paulo/SP', '2026-04-02 18:21:39', '2026-04-15 12:55:16'),
(3, 'Motoparts Brasil', '11.222.333/0001-44', '(11) 6000-0000', 'contato@motoparts.com.br', NULL, 1, 'Av. das Peças, 300 - São Paulo/SP', '2026-04-02 18:21:39', '2026-04-15 12:55:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `laudos_tecnicos`
--

CREATE TABLE `laudos_tecnicos` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `tipo_manutencao` enum('preventiva','corretiva') NOT NULL DEFAULT 'corretiva',
  `objetivo` text DEFAULT NULL,
  `km_revisao` int(10) UNSIGNED DEFAULT NULL,
  `conclusao_tecnica` text DEFAULT NULL,
  `status_veiculo` enum('apta','em_revisao','aguardando_pecas','inapta') NOT NULL DEFAULT 'apta',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `laudos_tecnicos`
--

INSERT INTO `laudos_tecnicos` (`id`, `os_id`, `tipo_manutencao`, `objetivo`, `km_revisao`, `conclusao_tecnica`, `status_veiculo`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 9, 'corretiva', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.  Ut vel lorem venenatis, porttitor sem non, vehicula libero. Sed venenatis elit non aliquet vulputate. Ut id lacus ac ex porttitor pulvinar ut a lorem. Fusce aliquet semper justo porta fermentum. Nunc eu nulla augue. Quisque purus magna, tincidunt quis hendrerit nec, finibus sit amet velit. Integer fringilla lacus ac viverra bibendum. Vestibulum malesuada mauris odio, ac convallis leo ultrices ut. Nam sed nisi in mi tincidunt gravida.  Duis et nisi tempus, lobortis leo eu, mattis libero. Nam pellentesque a mi ac maximus. Proin gravida erat eget dignissim dignissim. Vestibulum pretium lobortis dolor id suscipit. Cras magna mauris, pretium suscipit erat quis, bibendum commodo enim. Sed eu suscipit risus. Suspendisse lectus dolor, suscipit a accumsan at, tempus in diam.  Nullam varius gravida dapibus. Integer eros lacus, viverra at tempor at, venenatis ut metus. Suspendisse sit amet tortor lectus. Mauris pharetra sagittis efficitur. Vivamus sit amet quam nec ex convallis condimentum. Phasellus vitae mauris in felis consequat fermentum eu ut augue. Aenean fermentum libero a auctor faucibus. Nulla et ex eget turpis cursus elementum eget nec enim.  Integer ligula mauris, facilisis ut massa ac, accumsan porta elit. Ut ultricies, elit non accumsan imperdiet, dolor eros porttitor arcu, non eleifend nisl nibh eu purus. Vestibulum sodales mi mauris, interdum scelerisque purus venenatis vehicula. Aliquam egestas arcu ligula, vel euismod velit tristique ac. Vivamus porta, purus ac scelerisque sodales, metus dolor varius lacus, id laoreet lectus ex non ex. Nullam id nunc maximus, rhoncus leo id, placerat nunc. In id nibh ac ipsum mattis sagittis. Donec faucibus semper dictum. Phasellus luctus metus vitae nulla egestas, ut lobortis lectus auctor. Donec odio eros, scelerisque ac dui nec, ullamcorper tempus leo. Ut vestibulum congue lobortis. Etiam eu placerat quam. Quisque volutpat arcu vel mauris malesuada facilisis quis et arcu. Aenean sollicitudin metus quis tellus consectetur tempor. Suspendisse potenti.', NULL, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.\r\n\r\nUt vel lorem venenatis, porttitor sem non, vehicula libero. Sed venenatis elit non aliquet vulputate. Ut id lacus ac ex porttitor pulvinar ut a lorem. Fusce aliquet semper justo porta fermentum. Nunc eu nulla augue. Quisque purus magna, tincidunt quis hendrerit nec, finibus sit amet velit. Integer fringilla lacus ac viverra bibendum. Vestibulum malesuada mauris odio, ac convallis leo ultrices ut. Nam sed nisi in mi tincidunt gravida.\r\n\r\nDuis et nisi tempus, lobortis leo eu, mattis libero. Nam pellentesque a mi ac maximus. Proin gravida erat eget dignissim dignissim. Vestibulum pretium lobortis dolor id suscipit. Cras magna mauris, pretium suscipit erat quis, bibendum commodo enim. Sed eu suscipit risus. Suspendisse lectus dolor, suscipit a accumsan at, tempus in diam.\r\n\r\nNullam varius gravida dapibus. Integer eros lacus, viverra at tempor at, venenatis ut metus. Suspendisse sit amet tortor lectus. Mauris pharetra sagittis efficitur. Vivamus sit amet quam nec ex convallis condimentum. Phasellus vitae mauris in felis consequat fermentum eu ut augue. Aenean fermentum libero a auctor faucibus. Nulla et ex eget turpis cursus elementum eget nec enim.\r\n\r\nInteger ligula mauris, facilisis ut massa ac, accumsan porta elit. Ut ultricies, elit non accumsan imperdiet, dolor eros porttitor arcu, non eleifend nisl nibh eu purus. Vestibulum sodales mi mauris, interdum scelerisque purus venenatis vehicula. Aliquam egestas arcu ligula, vel euismod velit tristique ac. Vivamus porta, purus ac scelerisque sodales, metus dolor varius lacus, id laoreet lectus ex non ex. Nullam id nunc maximus, rhoncus leo id, placerat nunc. In id nibh ac ipsum mattis sagittis. Donec faucibus semper dictum. Phasellus luctus metus vitae nulla egestas, ut lobortis lectus auctor. Donec odio eros, scelerisque ac dui nec, ullamcorper tempus leo. Ut vestibulum congue lobortis. Etiam eu placerat quam. Quisque volutpat arcu vel mauris malesuada facilisis quis et arcu. Aenean sollicitudin metus quis tellus consectetur tempor. Suspendisse potenti.', 'apta', 1, '2026-04-18 23:14:51', '2026-04-19 12:00:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `laudo_secoes`
--

CREATE TABLE `laudo_secoes` (
  `id` int(11) NOT NULL,
  `laudo_id` int(11) NOT NULL,
  `secao` tinyint(4) NOT NULL COMMENT '1=Motor/Lubrificação 2=Arrefecimento 3=Alimentação 4=Transmissão 5=Freios 6=Rodas/Vedações 7=Suspensão/Direção 8=Comandos 9=Serviços Complementares',
  `item` text NOT NULL,
  `resultado` enum('ok','atencao','critico','substituido','nao_aplicavel') NOT NULL DEFAULT 'ok',
  `observacao` text DEFAULT NULL,
  `ordem` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `laudo_secoes`
--

INSERT INTO `laudo_secoes` (`id`, `laudo_id`, `secao`, `item`, `resultado`, `observacao`, `ordem`) VALUES
(83, 1, 1, 'Nível de óleo do motor', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 0),
(84, 1, 1, 'Qualidade do óleo', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.  Ut vel lorem venenatis, porttitor sem non, vehicula libero. Sed venenatis elit non aliquet vulputate. Ut id lacus ac ex porttitor pulvinar ut a lorem. Fusce aliquet semper justo porta fermentum. Nunc eu nulla augue. Quisque purus magna, tincidunt quis hendrerit nec, finibus sit amet velit. Integer fringilla lacus ac viverra bibendum. Vestibulum malesuada mauris odio, ac convallis leo ultrices ut. Nam sed nisi in mi tincidunt gravida.  Duis et nisi tempus, lobortis leo eu, mattis libero. Nam pellentesque a mi ac maximus. Proin gravida erat eget dignissim dignissim. Vestibulum pretium lobortis dolor id suscipit. Cras magna mauris, pretium suscipit erat quis, bibendum commodo enim. Sed eu suscipit risus. Suspendisse lectus dolor, suscipit a accumsan at, tempus in diam.  Nullam varius gravida dapibus. Integer eros lacus, viverra at tempor at, venenatis ut metus. Suspendisse sit amet tortor lectus. Mauris pharetra sagittis efficitur. Vivamus sit amet quam nec ex convallis condimentum. Phasellus vitae mauris in felis consequat fermentum eu ut augue. Aenean fermentum libero a auctor faucibus. Nulla et ex eget turpis cursus elementum eget nec enim.  Integer ligula mauris, facilisis ut massa ac, accumsan porta elit. Ut ultricies, elit non accumsan imperdiet, dolor eros porttitor arcu, non eleifend nisl nibh eu purus. Vestibulum sodales mi mauris, interdum scelerisque purus venenatis vehicula. Aliquam egestas arcu ligula, vel euismod velit tristique ac. Vivamus porta, purus ac scelerisque sodales, metus dolor varius lacus, id laoreet lectus ex non ex. Nullam id nunc maximus, rhoncus leo id, placerat nunc. In id nibh ac ipsum mattis sagittis. Donec faucibus semper dictum. Phasellus luctus metus vitae nulla egestas, ut lobortis lectus auctor. Donec odio eros, scelerisque ac dui nec, ullamcorper tempus leo. Ut vestibulum congue lobortis. Etiam eu placerat quam. Quisque volutpat arcu vel mauris malesuada facilisis quis et arcu. Aenean sollicitudin metus quis tellus consectetur tempor. Suspendisse potenti.', 1),
(85, 1, 1, 'Filtro de óleo', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 2),
(86, 1, 2, 'Nível de fluido de arrefecimento', 'ok', '', 0),
(87, 1, 2, 'Tampas e radiador', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 1),
(88, 1, 3, 'Filtro de ar', 'ok', '', 0),
(89, 1, 4, 'Coroa e pinhão', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 0),
(90, 1, 4, 'Cabo de embreagem', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 1),
(91, 1, 5, 'Fluido de freio dianteiro', 'ok', '', 0),
(92, 1, 5, 'Fluido de freio traseiro', 'ok', '', 1),
(93, 1, 5, 'Pastilhas/lonas dianteiras', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 2),
(94, 1, 6, 'Rolamentos de roda', 'ok', '', 0),
(95, 1, 6, 'Raios (se aplicável)', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 1),
(96, 1, 7, 'Alinhamento de direção', 'ok', '', 0),
(97, 1, 7, 'Rolamento de direção', 'ok', '', 1),
(98, 1, 8, 'Manetes e punhos', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 0),
(99, 1, 8, 'Cabo do freio traseiro', 'ok', '', 1),
(100, 1, 9, 'Lubrificação de cabos', 'ok', '', 0),
(101, 1, 9, 'Aperto geral de parafusos', 'ok', '', 1),
(102, 1, 9, 'Regulagem de válvulas', 'ok', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam suscipit lorem dictum, fringilla leo sit amet, dictum magna. Duis leo metus, auctor eget lacus eget, dictum tempus tellus. Cras fringilla neque et dignissim feugiat. Integer ut euismod lectus, in ullamcorper erat. Nunc nec metus neque. Fusce malesuada leo vel urna scelerisque, a luctus augue facilisis. Donec a cursus dolor. Integer sed dolor nisi. Suspendisse tempor lectus ac molestie vestibulum. Integer non sem non justo dictum suscipit. Aliquam nec arcu at tortor efficitur venenatis. Proin quis dapibus nulla. Etiam posuere ipsum justo, ut gravida sapien pellentesque at. Ut ullamcorper mollis elit, vitae tristique augue mattis non.', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `licencas`
--

CREATE TABLE `licencas` (
  `id` int(11) NOT NULL,
  `chave_hash` varchar(255) NOT NULL COMMENT 'SHA256 da chave ativada',
  `dominio` varchar(255) NOT NULL COMMENT 'Domínio no momento da ativação',
  `data_ativacao` datetime NOT NULL,
  `data_expiracao` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_atividades`
--

CREATE TABLE `logs_atividades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  `tabela` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mao_de_obra`
--

CREATE TABLE `mao_de_obra` (
  `id` int(11) NOT NULL,
  `valor_hora` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Despejando dados para a tabela `mao_de_obra`
--

INSERT INTO `mao_de_obra` (`id`, `valor_hora`, `descricao`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 100.00, '', 1, '2026-04-07 19:15:52', '2026-04-16 13:53:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `motos`
--

CREATE TABLE `motos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `ano` int(4) DEFAULT NULL,
  `cilindrada` varchar(10) DEFAULT NULL,
  `km_atual` int(10) UNSIGNED DEFAULT 0,
  `combustivel` enum('gasolina','alcool','flex','eletrico') DEFAULT 'gasolina',
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `cor` varchar(20) DEFAULT NULL,
  `chassi` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motos`
--

INSERT INTO `motos` (`id`, `cliente_id`, `placa`, `modelo`, `marca`, `ano`, `cilindrada`, `km_atual`, `combustivel`, `observacoes`, `ativo`, `cor`, `chassi`, `created_at`) VALUES
(1, 1, 'ABC-1234', 'CG 160', 'Honda', 2022, NULL, 0, 'gasolina', NULL, 1, 'Vermelha', '9C2JE0810NR000001', '2026-04-02 18:21:39'),
(2, 1, 'DEF-5678', 'Factor 150', 'Yamaha', 2021, NULL, 0, 'gasolina', NULL, 1, 'Azul', '9C2JE0810NR000002', '2026-04-02 18:21:39'),
(4, 2, 'JKL-3456', 'Pop 100', 'Honda', 2020, NULL, 0, 'gasolina', NULL, 1, 'Branca', '9C2JE0810NR000004', '2026-04-02 18:21:39'),
(5, 3, 'MNO-7890', 'Fazer 250', 'Yamaha', 2022, NULL, 0, 'gasolina', NULL, 1, 'Preta', '9C2JE0810NR000005', '2026-04-02 18:21:39'),
(6, 3, 'PQR-1234', 'Titan 150', 'Honda', 2021, NULL, 0, 'gasolina', NULL, 1, 'Cinza', '9C2JE0810NR000006', '2026-04-02 18:21:39'),
(7, 4, 'STU-5678', 'Bros 160', 'Honda', 2023, NULL, 0, 'gasolina', NULL, 1, 'Vermelha', '9C2JE0810NR000007', '2026-04-02 18:21:39'),
(8, 5, 'VWX-9012', 'XRE 300', 'Honda', 2022, NULL, 0, 'gasolina', NULL, 1, 'Preta', '9C2JE0810NR000008', '2026-04-02 18:21:39'),
(9, 5, 'DYY7J21', '883', 'Harley', 0, NULL, 0, 'gasolina', NULL, 1, '', '', '2026-04-07 16:00:44'),
(10, 4, 'DYY7J21', 'cg', 'Honda', NULL, '', 0, 'gasolina', NULL, 1, '', '', '2026-04-15 13:05:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_estoque`
--

CREATE TABLE `movimentacoes_estoque` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `custo_unitario` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(100) DEFAULT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `movimentacoes_estoque`
--

INSERT INTO `movimentacoes_estoque` (`id`, `produto_id`, `tipo`, `quantidade`, `custo_unitario`, `motivo`, `documento`, `created_by`, `created_at`) VALUES
(1, 1, 'entrada', 50, NULL, 'Compra inicial', 'NF-001', 1, '2026-04-02 18:21:39'),
(2, 2, 'entrada', 30, NULL, 'Compra inicial', 'NF-001', 1, '2026-04-02 18:21:39'),
(3, 3, 'entrada', 20, NULL, 'Compra inicial', 'NF-001', 1, '2026-04-02 18:21:39'),
(4, 1, 'saida', 2, NULL, 'Venda PDV', 'VENDA-20250101', 4, '2026-04-02 18:21:39'),
(5, 2, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20250101', 4, '2026-04-02 18:21:39'),
(6, 1, 'entrada', 10, NULL, 'Compra', 'NF-002', 1, '2026-04-02 18:21:39'),
(7, 3, 'saida', 1, NULL, 'OS-00003', 'OS-00003', 3, '2026-04-02 18:21:39'),
(8, 4, 'saida', 1, NULL, 'OS-00004', 'OS-00004', 3, '2026-04-02 18:21:39'),
(9, 8, 'entrada', 1, NULL, 'Compra', '', 1, '2026-04-02 18:57:15'),
(10, 4, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260402155736', 1, '2026-04-02 18:57:36'),
(11, 6, 'entrada', 16, NULL, 'Compra', '', 1, '2026-04-07 07:37:48'),
(12, 5, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407104630', 1, '2026-04-07 13:46:30'),
(13, 3, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407114439', 1, '2026-04-07 14:44:39'),
(14, 1, 'entrada', 20, NULL, 'Compra', '', 1, '2026-04-07 14:48:24'),
(15, 7, 'entrada', 10, NULL, 'Compra', '', 1, '2026-04-07 14:48:40'),
(16, 8, 'saida', 3, NULL, 'Venda PDV', 'VENDA-20260407121845', 1, '2026-04-07 15:18:45'),
(17, 8, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407214631', 1, '2026-04-08 00:46:31'),
(18, 3, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407214649', 1, '2026-04-08 00:46:49'),
(19, 6, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407214916', 1, '2026-04-08 00:49:16'),
(20, 8, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407215636', 1, '2026-04-08 00:56:36'),
(21, 2, 'saida', 1, NULL, 'Venda PDV', 'VENDA-20260407220317', 1, '2026-04-08 01:03:17'),
(22, 9, 'saida', 1, NULL, 'Venda PDV', 'VENDA-2026041515080566', 1, '2026-04-15 18:08:05'),
(23, 1, 'saida', 1, NULL, 'Venda PDV', 'VENDA-2026041515080566', 1, '2026-04-15 18:08:05'),
(24, 3, 'saida', 1, NULL, 'Venda PDV', 'VENDA-2026041515080566', 1, '2026-04-15 18:08:05'),
(25, 6, 'saida', 1, NULL, 'Venda PDV', 'VENDA-2026041515080566', 1, '2026-04-15 18:08:05'),
(26, 2, 'saida', 1, NULL, 'Venda PDV', 'VD260418200338', 1, '2026-04-18 23:03:38'),
(27, 2, 'saida', 1, NULL, 'Venda PDV', 'VD260419193052', 1, '2026-04-19 22:30:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL,
  `numero_orcamento` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_validade` date NOT NULL,
  `status` enum('ativo','aprovado','rejeitado','convertido') DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `convertido_os_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `orcamentos`
--

INSERT INTO `orcamentos` (`id`, `numero_orcamento`, `cliente_id`, `moto_id`, `data_criacao`, `data_validade`, `status`, `observacoes`, `convertido_os_id`) VALUES
(1, 'ORC-00001', 1, 1, '2026-04-02 18:21:39', '2026-05-02', 'convertido', 'Orçamento convertido em OS-00001', NULL),
(2, 'ORC-00002', 2, 3, '2026-04-02 18:21:39', '2026-05-02', 'convertido', 'Aguardando aprovação', 7),
(3, 'ORC-00003', 3, 5, '2026-04-02 18:21:39', '2026-05-02', 'ativo', 'Cliente vai retornar', NULL),
(4, 'ORC-00004', 4, 7, '2026-04-02 18:21:39', '2026-05-02', 'aprovado', 'Aprovado pelo cliente', NULL),
(5, 'ORC-00005', 5, 8, '2026-04-02 18:21:39', '2026-05-02', 'rejeitado', 'Cliente achou caro', NULL),
(6, 'ORC-00006', 5, 9, '2026-04-07 16:01:29', '2026-04-10', 'convertido', '', 8),
(7, 'ORC-00007', 5, 9, '2026-04-07 19:17:26', '2026-04-10', 'convertido', '', 9),
(9, 'ORC-00008', 4, 7, '2026-04-15 13:09:29', '2026-04-20', 'convertido', '', 10);

-- --------------------------------------------------------

--
-- Estrutura para tabela `orcamento_itens`
--

CREATE TABLE `orcamento_itens` (
  `id` int(11) NOT NULL,
  `orcamento_id` int(11) NOT NULL,
  `tipo` enum('servico','produto') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `orcamento_itens`
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
(13, 7, 'servico', 2, 1, 200.00),
(14, 9, 'servico', 4, 1, 0.00),
(15, 9, 'produto', 7, 1, 280.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordens_servico`
--

CREATE TABLE `ordens_servico` (
  `id` int(11) NOT NULL,
  `numero_os` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `km_entrada` int(10) UNSIGNED DEFAULT NULL,
  `km_saida` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('aberta','em_andamento','aguardando_pecas','finalizada','cancelada') DEFAULT 'aberta',
  `prioridade` enum('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
  `mecanico_id` int(11) DEFAULT NULL,
  `data_abertura` timestamp NULL DEFAULT current_timestamp(),
  `data_previsao` date DEFAULT NULL,
  `data_finalizacao` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `total_servicos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_produtos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_geral` decimal(10,2) NOT NULL DEFAULT 0.00,
  `desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `problema_relatado` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `data_entrega` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ordens_servico`
--

INSERT INTO `ordens_servico` (`id`, `numero_os`, `cliente_id`, `moto_id`, `km_entrada`, `km_saida`, `status`, `prioridade`, `mecanico_id`, `data_abertura`, `data_previsao`, `data_finalizacao`, `observacoes`, `created_by`, `total_servicos`, `total_produtos`, `total_geral`, `desconto`, `problema_relatado`, `diagnostico`, `data_entrega`, `updated_at`) VALUES
(1, 'OS-00001', 1, 1, NULL, NULL, 'finalizada', 'normal', NULL, '2026-03-18 18:21:39', '2026-03-21', NULL, 'Troca de óleo e revisão básica', 3, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(2, 'OS-00002', 2, 3, NULL, NULL, 'em_andamento', 'normal', NULL, '2026-03-28 18:21:39', '2026-04-04', NULL, 'Revisão completa agendada', 3, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(3, 'OS-00003', 3, 5, NULL, NULL, 'finalizada', 'normal', NULL, '2026-03-31 18:21:39', '2026-04-07', '2026-04-07', 'Troca de pastilhas de freio', 3, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(4, 'OS-00004', 4, 7, NULL, NULL, 'em_andamento', 'normal', NULL, '2026-03-26 18:21:39', '2026-04-05', NULL, 'Aguardando chegada do pneu', 3, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-16 15:19:41'),
(5, 'OS-00005', 5, 8, NULL, NULL, 'finalizada', 'normal', NULL, '2026-03-13 18:21:39', '2026-03-15', NULL, 'Troca de óleo e filtro', 3, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(6, 'OS-00006', 1, 2, NULL, NULL, 'cancelada', 'normal', NULL, '2026-04-01 18:21:39', '2026-04-09', NULL, 'Barulho no motor', 3, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(7, 'OS-00007', 2, 3, NULL, NULL, 'aberta', 'normal', NULL, '2026-04-07 14:47:30', NULL, NULL, 'Aguardando aprovação', 1, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(8, 'OS-00008', 5, 9, NULL, NULL, 'finalizada', 'normal', NULL, '2026-04-07 16:01:38', NULL, '2026-04-07', '', 1, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(9, 'OS-00009', 5, 9, NULL, NULL, 'em_andamento', 'normal', NULL, '2026-04-07 19:17:47', NULL, NULL, '', 1, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 12:55:16'),
(10, 'OS-00010', 4, 7, NULL, NULL, 'finalizada', 'normal', NULL, '2026-04-15 13:10:06', NULL, '2026-04-15', '', 1, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-04-15 13:14:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_produtos`
--

CREATE TABLE `os_produtos` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `os_produtos`
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
(12, 8, 1, 4, 35.00),
(14, 4, 3, 1, 45.00),
(15, 10, 7, 1, 280.00),
(16, 10, 6, 1, 250.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_servicos`
--

CREATE TABLE `os_servicos` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `mecanico_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  `valor_unitario` decimal(10,2) NOT NULL,
  `tempo_gasto` int(11) DEFAULT NULL,
  `garantia_dias` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `os_servicos`
--

INSERT INTO `os_servicos` (`id`, `os_id`, `servico_id`, `mecanico_id`, `quantidade`, `valor_unitario`, `tempo_gasto`, `garantia_dias`) VALUES
(1, 1, 1, 3, 1, 50.00, NULL, NULL),
(2, 1, 2, 3, 1, 200.00, NULL, NULL),
(3, 2, 2, 3, 1, 200.00, NULL, NULL),
(4, 2, 4, 3, 1, 75.00, NULL, NULL),
(5, 3, 3, 3, 1, 80.00, NULL, NULL),
(6, 4, 5, 3, 1, 83.33, NULL, NULL),
(7, 5, 1, 3, 1, 50.00, NULL, NULL),
(8, 6, 1, 3, 1, 50.00, NULL, NULL),
(9, 6, 6, 3, 1, 150.00, NULL, NULL),
(10, 6, 4, NULL, 1, 4444.00, NULL, NULL),
(11, 7, 2, NULL, 1, 200.00, NULL, NULL),
(12, 7, 4, NULL, 1, 75.00, NULL, NULL),
(13, 8, 10, NULL, 1, 40.00, NULL, NULL),
(14, 8, 8, NULL, 1, 100.00, NULL, NULL),
(21, 10, 4, NULL, 1, 0.00, NULL, NULL),
(42, 9, 11, NULL, 1, 1.67, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_status_log`
--

CREATE TABLE `os_status_log` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `status_anterior` varchar(20) DEFAULT NULL,
  `status_novo` varchar(20) NOT NULL,
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `os_status_log`
--

INSERT INTO `os_status_log` (`id`, `os_id`, `status_anterior`, `status_novo`, `observacao`, `usuario_id`, `created_at`) VALUES
(1, 6, 'aberta', 'em_andamento', '', 1, '2026-04-07 07:34:53'),
(2, 6, 'em_andamento', 'aguardando_pecas', '', 1, '2026-04-07 07:34:56'),
(3, 3, 'aberta', 'em_andamento', '', 1, '2026-04-07 14:45:46'),
(4, 3, 'em_andamento', 'finalizada', '', 1, '2026-04-07 14:46:06'),
(5, 8, 'aberta', 'em_andamento', '', 1, '2026-04-07 16:02:21'),
(6, 8, 'em_andamento', 'finalizada', '', 1, '2026-04-07 16:03:05'),
(7, 6, 'aguardando_pecas', 'cancelada', '', 1, '2026-04-07 16:19:48'),
(8, 9, 'aberta', 'em_andamento', '', 1, '2026-04-07 19:18:26'),
(9, 10, 'aberta', 'em_andamento', '', 1, '2026-04-15 13:14:07'),
(10, 10, 'em_andamento', 'finalizada', '', 1, '2026-04-15 13:14:38'),
(11, 4, 'aguardando_pecas', 'em_andamento', '', 1, '2026-04-16 15:19:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL,
  `perfil` varchar(20) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `acao` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `permissoes`
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
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `ncm` varchar(10) DEFAULT NULL,
  `localizacao` varchar(50) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_compra` decimal(10,2) DEFAULT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `estoque_atual` int(11) DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 5,
  `unidade` varchar(5) DEFAULT 'UN',
  `categoria_id` int(11) DEFAULT NULL,
  `exibir_pdv` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `codigo_barras`, `ncm`, `localizacao`, `ativo`, `nome`, `descricao`, `preco_compra`, `preco_venda`, `estoque_atual`, `estoque_minimo`, `unidade`, `categoria_id`, `exibir_pdv`, `created_at`, `updated_at`) VALUES
(1, '7891234560010', NULL, NULL, 1, 'Óleo Motor 20W50 1L', 'Óleo para motor 20W50 - 1 litro', 25.00, 35.00, 64, 10, 'LT', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 18:08:05'),
(2, '7891234560027', NULL, NULL, 1, 'Filtro de Óleo', 'Filtro de óleo para motos', 15.00, 25.00, 27, 8, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-19 22:30:52'),
(3, '7891234560034', NULL, NULL, 1, 'Pastilha de Freio Dianteira', 'Pastilha de freio dianteira', 30.00, 45.00, 16, 5, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 18:08:05'),
(4, '7891234560041', NULL, NULL, 1, 'Corrente de Transmissão', 'Corrente para transmissão', 80.00, 120.00, 12, 5, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-16 13:35:12'),
(5, '7891234560058', NULL, NULL, 1, 'Vela de Ignição', 'Vela de ignição NGK', 10.00, 18.00, 39, 10, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 12:55:16'),
(6, '7891234560065', NULL, NULL, 1, 'Pneu Dianteiro', 'Pneu dianteiro 90/90-18', 180.00, 250.00, 22, 5, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 18:08:05'),
(7, '7891234560072', NULL, NULL, 1, 'Pneu Traseiro', 'Pneu traseiro 110/90-18', 200.00, 280.00, 17, 5, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 13:10:06'),
(8, '7891234560089', '', '', 1, 'Bateria 12V', 'Bateria para moto 12V', 12000.00, 18000.00, 7, 5, 'PC', 11, 1, '2026-04-02 18:21:39', '2026-04-15 18:00:58'),
(9, '7891234560096', NULL, NULL, 1, 'Farol LED', 'Farol em LED', 90.00, 150.00, 24, 10, 'PC', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 18:08:05'),
(10, '7891234560102', NULL, NULL, 1, 'Retrovisor', 'Par de retrovisores', 35.00, 60.00, 18, 8, 'PAR', NULL, 1, '2026-04-02 18:21:39', '2026-04-15 12:55:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT 0.00,
  `tempo_estimado` int(11) DEFAULT NULL,
  `garantia_dias` int(11) DEFAULT 30,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `valor`, `tempo_estimado`, `garantia_dias`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Troca de Óleo', 'Troca de óleo do motor + filtro', 50.00, 30, 1000, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:57'),
(2, 'Revisão Completa', 'Revisão completa da moto', 200.00, 120, 30, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:37'),
(3, 'Troca de Pastilhas de Freio', 'Substituição das pastilhas de freio', 100.00, 60, 30, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:59'),
(4, 'Alinhamento e Balanceamento', 'Alinhamento e balanceamento das rodas', 75.00, 45, 30, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:25'),
(5, 'Troca de Corrente', 'Substituição da corrente de transmissão', 83.33, 50, 30, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:54'),
(6, 'Revisão Preventiva', 'Revisão preventiva completa', 150.00, 90, 60, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:46'),
(7, 'Troca de Pneus', 'Troca do par de pneus', 100.00, 60, 30, 1, '2026-04-02 18:21:39', '2026-04-16 14:00:01'),
(8, 'Regulagem de Motor', 'Regulagem completa do motor', 200.00, 120, 30, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:35'),
(9, 'Troca de Bateria', 'Substituição da bateria', 33.33, 20, 30, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:52'),
(10, 'Lavagem Especial', 'Lavagem completa da moto', 100.00, 60, 31, 1, '2026-04-02 18:21:39', '2026-04-16 13:59:32'),
(11, 'teste', '', 1.67, 1, 30, 1, '2026-04-07 07:37:20', '2026-04-16 13:59:49');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sistema_install`
--

CREATE TABLE `sistema_install` (
  `id` int(11) NOT NULL,
  `install_token` varchar(64) NOT NULL COMMENT 'Token único desta instalação',
  `data_instalacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sistema_install`
--

INSERT INTO `sistema_install` (`id`, `install_token`, `data_instalacao`) VALUES
(1, '99bb36cf068b4c28342d551a1beba2e6fca10b025b67f58e832157505e8a4037', '2026-04-19 18:07:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','gerente','mecanico','caixa','vendedor') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`) VALUES
(1, 'Administrador', 'admin@os-system.com', '$2y$10$t56xcxvrIK1Df8LpRyIcZOCdC4RNCMiKCdxgNeru/w56ILSyR63gC', 'admin', 1, '2026-04-02 17:47:25'),
(12, 'João Gerente', 'gerente@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente', 1, '2026-04-02 18:21:39'),
(13, 'Carlos Mecânico', 'mecanico@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mecanico', 1, '2026-04-02 18:21:39'),
(14, 'Ana Caixa', 'caixa@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caixa', 1, '2026-04-02 18:21:39'),
(15, 'Pedro Vendedor', 'vendedor@os-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 1, '2026-04-02 18:21:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `numero_venda` varchar(30) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `data_venda` timestamp NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','cartao_credito','cartao_debito','boleto','mix') NOT NULL,
  `parcelas` int(11) DEFAULT 1,
  `status` enum('finalizada','cancelada') DEFAULT 'finalizada',
  `caixa_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `mp_payment_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vendas`
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
(14, 'VENDA-20260407220317', NULL, '2026-04-08 01:03:17', 25.00, 0.00, 25.00, 'pix', 1, 'finalizada', 3, 1, NULL),
(15, 'VENDA-2026041515080566', NULL, '2026-04-15 18:08:05', 480.00, 0.00, 480.00, 'dinheiro', 1, 'finalizada', 4, 1, NULL),
(16, 'VD260418200338', NULL, '2026-04-18 23:03:38', 25.00, 0.00, 25.00, 'dinheiro', 1, 'finalizada', 5, 1, NULL),
(17, 'VD260419193052', NULL, '2026-04-19 22:30:52', 25.00, 0.00, 25.00, 'dinheiro', 1, 'finalizada', 6, 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `venda_itens`
--

CREATE TABLE `venda_itens` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `venda_itens`
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
(15, 14, 2, 1, 25.00, 25.00),
(16, 15, 9, 1, 150.00, 150.00),
(17, 15, 1, 1, 35.00, 35.00),
(18, 15, 3, 1, 45.00, 45.00),
(19, 15, 6, 1, 250.00, 250.00),
(20, 16, 2, 1, 25.00, 25.00),
(21, 17, 2, 1, 25.00, 25.00);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `caixa`
--
ALTER TABLE `caixa`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `caixa_movimentacoes`
--
ALTER TABLE `caixa_movimentacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categorias_produtos`
--
ALTER TABLE `categorias_produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_nome_cliente` (`nome`(50));

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `laudos_tecnicos`
--
ALTER TABLE `laudos_tecnicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_os_laudo` (`os_id`),
  ADD KEY `fk_laudo_usuario` (`created_by`);

--
-- Índices de tabela `laudo_secoes`
--
ALTER TABLE `laudo_secoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_laudo_secao` (`laudo_id`,`secao`);

--
-- Índices de tabela `licencas`
--
ALTER TABLE `licencas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chave` (`chave_hash`);

--
-- Índices de tabela `logs_atividades`
--
ALTER TABLE `logs_atividades`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mao_de_obra`
--
ALTER TABLE `mao_de_obra`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `motos`
--
ALTER TABLE `motos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_placa` (`placa`);

--
-- Índices de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_orcamento` (`numero_orcamento`);

--
-- Índices de tabela `orcamento_itens`
--
ALTER TABLE `orcamento_itens`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_os` (`numero_os`);

--
-- Índices de tabela `os_produtos`
--
ALTER TABLE `os_produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `os_servicos`
--
ALTER TABLE `os_servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `os_status_log`
--
ALTER TABLE `os_status_log`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permissao` (`perfil`,`modulo`,`acao`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`),
  ADD KEY `idx_codigo_barras` (`codigo_barras`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_pdv` (`exibir_pdv`,`ativo`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `sistema_install`
--
ALTER TABLE `sistema_install`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_venda` (`numero_venda`),
  ADD KEY `idx_data_venda` (`data_venda`);

--
-- Índices de tabela `venda_itens`
--
ALTER TABLE `venda_itens`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caixa`
--
ALTER TABLE `caixa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `caixa_movimentacoes`
--
ALTER TABLE `caixa_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `categorias_produtos`
--
ALTER TABLE `categorias_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `laudos_tecnicos`
--
ALTER TABLE `laudos_tecnicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `laudo_secoes`
--
ALTER TABLE `laudo_secoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de tabela `licencas`
--
ALTER TABLE `licencas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_atividades`
--
ALTER TABLE `logs_atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mao_de_obra`
--
ALTER TABLE `mao_de_obra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `motos`
--
ALTER TABLE `motos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `orcamento_itens`
--
ALTER TABLE `orcamento_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `os_produtos`
--
ALTER TABLE `os_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `os_servicos`
--
ALTER TABLE `os_servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `os_status_log`
--
ALTER TABLE `os_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `sistema_install`
--
ALTER TABLE `sistema_install`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `venda_itens`
--
ALTER TABLE `venda_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `laudos_tecnicos`
--
ALTER TABLE `laudos_tecnicos`
  ADD CONSTRAINT `fk_laudo_os` FOREIGN KEY (`os_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_laudo_usuario` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `laudo_secoes`
--
ALTER TABLE `laudo_secoes`
  ADD CONSTRAINT `fk_secao_laudo` FOREIGN KEY (`laudo_id`) REFERENCES `laudos_tecnicos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
