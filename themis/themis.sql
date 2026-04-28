-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 28/04/2026 às 19:46
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
-- Banco de dados: `themis`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agenda_eventos`
--

CREATE TABLE `agenda_eventos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`user_ids`)),
  `tipo` enum('audiencia','pericia','reuniao','prazo','diligencia','confraternizacao','outro') NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `descricao` text DEFAULT NULL,
  `local` varchar(500) DEFAULT NULL,
  `link_virtual` varchar(500) DEFAULT NULL,
  `inicio` datetime NOT NULL,
  `fim` datetime DEFAULT NULL,
  `dia_inteiro` tinyint(1) DEFAULT 0,
  `cor` varchar(20) DEFAULT '#3B82F6',
  `recorrencia` enum('nenhuma','diaria','semanal','mensal') DEFAULT 'nenhuma',
  `alerta_minutos` int(11) DEFAULT 60,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `agenda_eventos`
--

INSERT INTO `agenda_eventos` (`id`, `tenant_id`, `processo_id`, `user_ids`, `tipo`, `titulo`, `descricao`, `local`, `link_virtual`, `inicio`, `fim`, `dia_inteiro`, `cor`, `recorrencia`, `alerta_minutos`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '[1]', 'audiencia', 'testa', '', '', NULL, '2026-04-25 10:00:00', '2026-04-25 11:00:00', 0, '#3B82F6', 'nenhuma', 60, 1, '2026-04-24 17:13:05', '2026-04-24 17:13:05'),
(2, 1, NULL, '[1]', 'audiencia', 'fgsdfsd', NULL, 'asdasd', NULL, '2026-04-27 09:00:00', '2026-04-28 09:00:00', 0, '#3B82F6', 'nenhuma', 60, 1, '2026-04-26 22:35:56', '2026-04-26 22:35:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `alvaras_monitoramento`
--

CREATE TABLE `alvaras_monitoramento` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `valor_alvara` decimal(18,2) NOT NULL,
  `status` enum('aguardando','expedido','levantado','cancelado') DEFAULT 'aguardando',
  `data_expedicao` date DEFAULT NULL,
  `data_levantamento` date DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(30) DEFAULT NULL,
  `gatilho_ativo` tinyint(1) DEFAULT 1,
  `alerta_enviado` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `api_retry_queue`
--

CREATE TABLE `api_retry_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `servico` enum('datajud','oab','assinafy','receita_federal','bcb_selic','ibge_ipca') NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `payload` longtext DEFAULT NULL,
  `tentativas` tinyint(3) UNSIGNED DEFAULT 0,
  `max_tentativas` tinyint(3) UNSIGNED DEFAULT 5,
  `proximo_retry` datetime NOT NULL,
  `status` enum('pendente','processando','sucesso','falhou') DEFAULT 'pendente',
  `resposta_json` text DEFAULT NULL,
  `erro_msg` text DEFAULT NULL,
  `contexto_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contexto_json`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `modulo` varchar(100) NOT NULL,
  `entidade_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dados_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_antes`)),
  `dados_depois` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_depois`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `url` varchar(1000) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `tenant_id`, `user_id`, `acao`, `modulo`, `entidade_id`, `dados_antes`, `dados_depois`, `ip_address`, `user_agent`, `url`, `created_at`) VALUES
(1, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-24 16:12:29'),
(2, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-24 17:11:16'),
(3, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-25 04:27:20'),
(4, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-25 10:38:15'),
(5, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-25 11:55:55'),
(6, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 01:57:42'),
(7, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 08:58:03'),
(8, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 10:24:50'),
(9, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 17:02:07'),
(10, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 17:06:20'),
(11, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 19:24:41'),
(12, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-26 23:52:23'),
(13, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-27 09:29:23'),
(14, 1, 1, 'login', 'auth', NULL, NULL, '[]', '170.82.179.210', NULL, '/api/auth/login', '2026-04-27 09:29:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `calculos`
--

CREATE TABLE `calculos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `pericia_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `tipo` enum('atualizacao_monetaria','juros_mora','multa','verbas_trabalhistas','inss','fgts','honorarios','personalizado') NOT NULL,
  `metodo_juros` enum('simples','composto','pro_rata_die') DEFAULT 'simples',
  `indice_correcao` enum('SELIC','IPCA_E','INPC','IGP_M','TR','FIXO') DEFAULT 'SELIC',
  `taxa_juros` decimal(8,4) DEFAULT NULL,
  `valor_base` decimal(18,2) NOT NULL,
  `data_base` date NOT NULL,
  `data_calculo` date NOT NULL,
  `valor_correcao` decimal(18,2) DEFAULT NULL,
  `valor_juros` decimal(18,2) DEFAULT NULL,
  `valor_multa` decimal(18,2) DEFAULT NULL,
  `valor_total` decimal(18,2) DEFAULT NULL,
  `memoria_calculo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`memoria_calculo`)),
  `lei_aplicada` varchar(200) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_alertas`
--

CREATE TABLE `crm_alertas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `stakeholder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('aniversario','followup_vencido','sem_contato','reuniao','personalizado') NOT NULL,
  `mensagem` varchar(500) NOT NULL,
  `data_alerta` date NOT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `lido_em` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_interacoes`
--

CREATE TABLE `crm_interacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `stakeholder_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` enum('email','whatsapp','reuniao','ligacao','cafe','visita','outro') NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `sentimento` enum('positivo','neutro','negativo') DEFAULT 'neutro',
  `data_interacao` datetime NOT NULL,
  `proxima_acao` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `datajud_movimentos`
--

CREATE TABLE `datajud_movimentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `numero_cnj` varchar(30) NOT NULL,
  `codigo_movimento` varchar(20) DEFAULT NULL,
  `nome_movimento` varchar(500) NOT NULL,
  `data_movimento` datetime NOT NULL,
  `complemento` text DEFAULT NULL,
  `raw_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_json`)),
  `importado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `categoria` enum('km','pedagio','alimentacao','hospedagem','passagem','cartorio','copia','pericia_taxa','honorario_externo','outros') NOT NULL,
  `descricao` varchar(500) NOT NULL,
  `data_despesa` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `km_percorrido` decimal(8,2) DEFAULT NULL,
  `valor_km` decimal(6,4) DEFAULT NULL,
  `recibo_path` varchar(500) DEFAULT NULL,
  `recibo_hash` char(40) DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado','reembolsado') DEFAULT 'pendente',
  `aprovado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `reembolsado_em` datetime DEFAULT NULL,
  `observacao` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quilometragem` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `despesas`
--

INSERT INTO `despesas` (`id`, `tenant_id`, `owner_id`, `processo_id`, `user_id`, `categoria`, `descricao`, `data_despesa`, `valor`, `km_percorrido`, `valor_km`, `recibo_path`, `recibo_hash`, `status`, `aprovado_por`, `aprovado_em`, `reembolsado_em`, `observacao`, `created_at`, `updated_at`, `quilometragem`) VALUES
(1, 1, 1, NULL, 1, 'km', 'km', '2026-04-27', 135.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 22:50:18', '2026-04-26 22:50:18', 150.00),
(2, 1, 1, NULL, 1, 'outros', 'outros', '2026-04-27', 135.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 22:53:06', '2026-04-26 22:53:06', 150.00),
(3, 1, 1, NULL, 1, 'km', 'km', '2026-04-27', 90.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 22:57:36', '2026-04-26 22:57:36', 100.00),
(4, 1, 1, NULL, 1, 'alimentacao', 'alimentacao', '2026-04-27', 30.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 23:01:14', '2026-04-26 23:01:14', NULL),
(5, 1, 1, NULL, 1, 'alimentacao', 'alimentacao', '2026-04-27', 40.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 23:04:47', '2026-04-26 23:04:47', NULL),
(6, 1, 1, NULL, 1, 'km', 'km', '2026-04-27', 90.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 23:09:20', '2026-04-26 23:09:20', 100.00),
(7, 1, 1, NULL, 1, 'km', 'km', '2026-04-27', 45.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 23:09:59', '2026-04-26 23:09:59', 50.00),
(8, 1, 1, NULL, 1, 'km', 'km', '2026-04-27', 90.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 23:11:49', '2026-04-26 23:11:49', 100.00),
(9, 1, 1, NULL, 1, 'km', 'km', '2026-04-27', 90.00, NULL, NULL, NULL, NULL, 'pendente', NULL, NULL, NULL, NULL, '2026-04-26 23:12:32', '2026-04-26 23:12:32', 100.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pericia_id` bigint(20) UNSIGNED DEFAULT NULL,
  `despesa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `categoria` enum('peticao','laudo','parecer','contrato','procuracao','decisao','sentenca','acordao','prova','recibo','ata','notificacao','outros') NOT NULL,
  `nome_original` varchar(500) NOT NULL,
  `nome_hash` char(40) NOT NULL,
  `caminho` varchar(1000) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `tamanho_bytes` int(10) UNSIGNED NOT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `assinatura_status` enum('nao_aplicavel','pendente','enviado','assinado','recusado') DEFAULT 'nao_aplicavel',
  `assinafy_doc_id` varchar(255) DEFAULT NULL,
  `assinafy_status` varchar(20) DEFAULT NULL,
  `versao` tinyint(3) UNSIGNED DEFAULT 1,
  `publico_cliente` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `documentos`
--

INSERT INTO `documentos` (`id`, `tenant_id`, `processo_id`, `pericia_id`, `despesa_id`, `user_id`, `categoria`, `nome_original`, `nome_hash`, `caminho`, `mime_type`, `tamanho_bytes`, `metadata_json`, `assinatura_status`, `assinafy_doc_id`, `assinafy_status`, `versao`, `publico_cliente`, `deleted_at`, `created_at`, `updated_at`) VALUES
(60, 1, NULL, NULL, NULL, 1, 'outros', 'Peticao_Inicial_--_Civel_Padrao_20260426_225918.pdf', 'b3ad02264c996ed6020bad85a5934324d2b2f75d', 'processos/0/documentos/f9aad319a9e45530c190bcfbdaee3689e2049920.pdf', 'application/pdf', 153937, NULL, 'nao_aplicavel', NULL, NULL, 1, 0, NULL, '2026-04-26 22:59:18', '2026-04-26 22:59:18'),
(61, 1, NULL, NULL, NULL, 1, 'recibo', 'Gemini_Generated_Image_avf5lavf5lavf5la.jpg', 'f182814f1148b4b8a82b26b5e998ae9e2ba0e4cb', 'docs/0/recibo/f182814f1148b4b8a82b26b5e998ae9e2ba0e4cb.jpg', 'image/jpeg', 1048071, NULL, 'nao_aplicavel', NULL, NULL, 1, 0, NULL, '2026-04-26 23:12:33', '2026-04-26 23:12:33'),
(62, 1, 2, NULL, NULL, 1, 'parecer', 'Parecer-Divergente-2026-04-27.txt', '76dd17a61c7dca77b7c383d015b378a194946740', 'docs/2/parecer/76dd17a61c7dca77b7c383d015b378a194946740.txt', 'text/plain', 174, NULL, 'nao_aplicavel', NULL, NULL, 1, 0, NULL, '2026-04-26 23:52:02', '2026-04-26 23:52:02'),
(63, 1, 2, NULL, NULL, 1, 'parecer', 'Parecer-Divergente-20260427.pdf', '93e0203d31cdbf29376b32dbf9e72de54dbb48cd', 'processos/2/documentos/93e0203d31cdbf29376b32dbf9e72de54dbb48cd.pdf', 'application/pdf', 7599, NULL, 'nao_aplicavel', NULL, NULL, 1, 0, NULL, '2026-04-27 00:00:27', '2026-04-27 00:00:27'),
(64, 1, 2, NULL, NULL, 1, 'parecer', 'Parecer-Divergente-20260427.pdf', '71ee63baddd0f48d79b98d9476df2c2a313ba4e6', 'processos/2/documentos/71ee63baddd0f48d79b98d9476df2c2a313ba4e6.pdf', 'application/pdf', 7599, NULL, 'nao_aplicavel', NULL, NULL, 1, 0, NULL, '2026-04-27 00:02:29', '2026-04-27 00:02:29'),
(65, 1, 2, NULL, NULL, 1, 'parecer', 'Parecer-Divergente-20260427.pdf', '91752e49427ae71dc6a7be4729c84523eb8cfe7a', 'processos/2/documentos/91752e49427ae71dc6a7be4729c84523eb8cfe7a.pdf', 'application/pdf', 152883, NULL, 'nao_aplicavel', NULL, NULL, 1, 0, NULL, '2026-04-27 09:30:05', '2026-04-27 09:30:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `doc_gerados`
--

CREATE TABLE `doc_gerados` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `variaveis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variaveis_json`)),
  `documento_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `doc_gerados`
--

INSERT INTO `doc_gerados` (`id`, `tenant_id`, `template_id`, `processo_id`, `user_id`, `titulo`, `variaveis_json`, `documento_id`, `created_at`) VALUES
(1, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 25/04/2026', '{\"data_hoje\":\"25 de abril de 2026\",\"data_hoje_fmt\":\"25\\/04\\/2026\",\"hora_atual\":\"11:51\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 3, '2026-04-25 11:51:40'),
(2, 1, 7, NULL, 1, 'teste de gravação no ged — 25/04/2026', '{\"data_hoje\":\"25 de abril de 2026\",\"data_hoje_fmt\":\"25\\/04\\/2026\",\"hora_atual\":\"12:07\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 4, '2026-04-25 12:07:15'),
(3, 1, 7, NULL, 1, 'teste de gravação no ged — 25/04/2026', '{\"data_hoje\":\"25 de abril de 2026\",\"data_hoje_fmt\":\"25\\/04\\/2026\",\"hora_atual\":\"12:07\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 5, '2026-04-25 12:07:31'),
(4, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:01\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 6, '2026-04-26 02:01:35'),
(5, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 7, '2026-04-26 02:06:44'),
(6, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 8, '2026-04-26 02:06:48'),
(7, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 9, '2026-04-26 02:06:50'),
(8, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 10, '2026-04-26 02:06:53'),
(9, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 11, '2026-04-26 02:06:57'),
(10, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"02:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 12, '2026-04-26 02:06:59'),
(11, 1, 5, NULL, 1, 'Notificação Extrajudicial — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:14\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 13, '2026-04-26 08:14:30'),
(12, 1, 5, NULL, 1, 'Notificação Extrajudicial — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:15\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 14, '2026-04-26 08:15:56'),
(13, 1, 5, NULL, 1, 'Notificação Extrajudicial — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:17\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 15, '2026-04-26 08:17:43'),
(14, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:40\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 16, '2026-04-26 08:40:22'),
(15, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:42\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 17, '2026-04-26 08:42:25'),
(16, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:45\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 18, '2026-04-26 08:45:08'),
(17, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"08:58\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 19, '2026-04-26 08:58:17'),
(18, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"09:32\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 20, '2026-04-26 09:32:12'),
(19, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"09:46\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 21, '2026-04-26 09:46:51'),
(20, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"09:49\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 22, '2026-04-26 09:49:59'),
(21, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"09:51\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 23, '2026-04-26 09:51:33'),
(22, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:02\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 24, '2026-04-26 10:02:01'),
(23, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:02\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 25, '2026-04-26 10:02:18'),
(24, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:07\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 26, '2026-04-26 10:07:25'),
(25, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:07\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 27, '2026-04-26 10:07:35'),
(26, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:08\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 28, '2026-04-26 10:08:54'),
(27, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:10\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 29, '2026-04-26 10:10:53'),
(28, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:11\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 30, '2026-04-26 10:11:35'),
(29, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:14\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 31, '2026-04-26 10:14:00'),
(30, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:16\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 32, '2026-04-26 10:16:08'),
(31, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:16\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 33, '2026-04-26 10:16:30'),
(32, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:17\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 34, '2026-04-26 10:17:40'),
(33, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:19\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 35, '2026-04-26 10:19:33'),
(34, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:20\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 36, '2026-04-26 10:20:21'),
(35, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:24\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 37, '2026-04-26 10:24:15'),
(36, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:25\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 38, '2026-04-26 10:25:03'),
(37, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:26\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 39, '2026-04-26 10:26:28'),
(38, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"10:28\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 40, '2026-04-26 10:28:53'),
(39, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"15:58\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 41, '2026-04-26 15:58:01'),
(40, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"15:58\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 42, '2026-04-26 15:58:47'),
(41, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":true,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"15:59\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 43, '2026-04-26 15:59:42'),
(42, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:14\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 44, '2026-04-26 16:14:03'),
(43, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:14\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 45, '2026-04-26 16:14:43'),
(44, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:16\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 46, '2026-04-26 16:16:51'),
(45, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:18\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 47, '2026-04-26 16:18:51'),
(46, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:40\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 48, '2026-04-26 16:40:50'),
(47, 1, 7, NULL, 1, 'teste de gravação no ged — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:43\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 49, '2026-04-26 16:43:56'),
(48, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:46\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 50, '2026-04-26 16:46:34'),
(49, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:50\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 51, '2026-04-26 16:50:35'),
(50, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"16:53\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 52, '2026-04-26 16:53:34'),
(51, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:02\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 53, '2026-04-26 17:02:16'),
(52, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:06\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 54, '2026-04-26 17:06:29'),
(53, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:07\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 55, '2026-04-26 17:07:47'),
(54, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:11\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 56, '2026-04-26 17:11:44'),
(55, 1, 2, NULL, 1, 'Contestação Padrão — 26/04/2026', '{\"usar_proprio_usuario\":false,\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:16\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 57, '2026-04-26 17:16:47'),
(56, 1, 2, NULL, 1, 'Contestação Padrão — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:29\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 58, '2026-04-26 17:29:07'),
(57, 1, 4, NULL, 1, 'Laudo Pericial — Estrutura Padrão — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"17:29\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 59, '2026-04-26 17:29:21'),
(58, 1, 1, NULL, 1, 'Petição Inicial — Cível Padrão — 26/04/2026', '{\"data_hoje\":\"26 de abril de 2026\",\"data_hoje_fmt\":\"26\\/04\\/2026\",\"hora_atual\":\"22:59\",\"ano_atual\":\"2026\",\"app_nome\":\"Themis Enterprise\"}', 60, '2026-04-26 22:59:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `doc_templates`
--

CREATE TABLE `doc_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(255) NOT NULL,
  `tipo` enum('peticao','laudo','parecer','contrato','notificacao','relatorio','outro') NOT NULL,
  `subtipo` varchar(100) DEFAULT NULL,
  `conteudo_html` longtext NOT NULL,
  `variaveis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variaveis_json`)),
  `papel_timbrado` tinyint(1) DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1,
  `uso_count` int(10) UNSIGNED DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `doc_templates`
--

INSERT INTO `doc_templates` (`id`, `tenant_id`, `nome`, `tipo`, `subtipo`, `conteudo_html`, `variaveis_json`, `papel_timbrado`, `ativo`, `uso_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Petição Inicial — Cível Padrão', 'peticao', 'inicial', '<h2 style=\"text-align: center; text-transform: uppercase;\">EXCELENT&Iacute;SSIMO(A) SENHOR(A) DOUTOR(A) JUIZ(A) DE DIREITO DA {{processo_vara}}</h2>\n<p>&nbsp;</p>\n<p><strong>{{cliente_nome}}</strong>, {{polo}}, {{cliente_doc}}, residente e domiciliado(a) &agrave; {{cliente_endereco}}, vem, por seu(sua) advogado(a) que esta subscreve, {{advogado_nome}}, inscrito(a) na {{advogado_oab}}, propor a presente</p>\n<h3 style=\"text-align: center;\">A&Ccedil;&Atilde;O {{processo_titulo}}</h3>\n<p>em face de <strong>{{parte_contraria}}</strong>, pelos fatos e fundamentos a seguir expostos:</p>\n<h3>I &mdash; DOS FATOS</h3>\n<p>{{fatos}}</p>\n<h3>II &mdash; DO DIREITO</h3>\n<p>{{fundamentos_juridicos}}</p>\n<h3>III &mdash; DO PEDIDO</h3>\n<p>Ante o exposto, requer a Vossa Excel&ecirc;ncia se digne a:</p>\n<p>{{pedidos}}</p>\n<p>D&aacute;-se &agrave; causa o valor de {{processo_valor_causa}}.</p>\n<p>Nesses termos,<br>pede deferimento.</p>\n<p>{{processo_comarca}}, {{data_hoje}}.</p>\n<p>&nbsp;</p>\n<p style=\"text-align: center;\">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>', '[\"cliente_nome\",\"polo\",\"cliente_doc\",\"cliente_endereco\",\"advogado_nome\",\"advogado_oab\",\"processo_vara\",\"processo_titulo\",\"parte_contraria\",\"fatos\",\"fundamentos_juridicos\",\"pedidos\",\"processo_valor_causa\",\"processo_comarca\",\"data_hoje\"]', 1, 1, 38, 1, '2026-04-24 16:04:04', '2026-04-26 22:59:18'),
(2, 1, 'Contestação Padrão', 'peticao', 'contestacao', '<h2 style=\"text-align: center; text-transform: uppercase;\">EXCELENT&Iacute;SSIMO(A) SENHOR(A) DOUTOR(A) JUIZ(A) DE DIREITO</h2>\n<p style=\"text-align: center;\"><strong>{{processo_vara}}</strong></p>\n<p style=\"text-align: center;\"><strong>Processo n&ordm; {{processo_numero}}</strong></p>\n<p>&nbsp;</p>\n<p><strong>{{cliente_nome}}</strong>, {{polo}}, nos autos da a&ccedil;&atilde;o em ep&iacute;grafe movida por <strong>{{parte_contraria}}</strong>, vem, tempestivamente, por meio de seu(sua) advogado(a) {{advogado_nome}}, {{advogado_oab}}, apresentar</p>\n<h3 style=\"text-align: center;\">CONTESTA&Ccedil;&Atilde;O</h3>\n<p>pelos motivos a seguir expostos:</p>\n<h3>I &mdash; PRELIMINARMENTE</h3>\n<p>{{preliminares}}</p>\n<h3>II &mdash; NO M&Eacute;RITO</h3>\n<p>{{merito}}</p>\n<h3>III &mdash; DOS PEDIDOS</h3>\n<p>Diante do exposto, requer sejam julgados improcedentes os pedidos formulados na inicial, com condena&ccedil;&atilde;o do autor ao pagamento das custas e honor&aacute;rios advocat&iacute;cios.</p>\n<p>{{processo_comarca}}, {{data_hoje}}.</p>\n<p>&nbsp;</p>\n<p style=\"text-align: center;\">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>', '[\"processo_numero\",\"processo_vara\",\"cliente_nome\",\"polo\",\"parte_contraria\",\"advogado_nome\",\"advogado_oab\",\"preliminares\",\"merito\",\"processo_comarca\",\"data_hoje\"]', 1, 1, 2, 1, '2026-04-24 16:04:04', '2026-04-26 17:29:07'),
(3, 1, 'Parecer Técnico Divergente — Padrão', 'parecer', 'divergente', '<h2 style=\"text-align:center;text-transform:uppercase\">PARECER TÉCNICO DIVERGENTE</h2>\n<p style=\"text-align:center\"><strong>Processo nº {{processo_numero}}</strong></p>\n<p style=\"text-align:center\">{{processo_vara}} — {{processo_tribunal}}</p>\n<br>\n<h3>1. OBJETO</h3>\n<p>O presente Parecer Técnico Divergente tem por objeto a análise crítica do Laudo Pericial apresentado pelo perito oficial, apontando as divergências técnicas identificadas pelo Assistente Técnico da parte {{polo}}.</p>\n<h3>2. IDENTIFICAÇÃO DAS PARTES</h3>\n<p>\n<strong>Requerente:</strong> {{parte_contraria}}<br>\n<strong>Requerido:</strong> {{cliente_nome}}<br>\n<strong>Assistente Técnico:</strong> {{advogado_nome}} — {{advogado_oab}}<br>\n<strong>Data:</strong> {{data_hoje}}\n</p>\n<h3>3. DIVERGÊNCIAS IDENTIFICADAS</h3>\n<p>{{divergencias_texto}}</p>\n<h3>4. CONCLUSÃO</h3>\n<p>{{conclusao}}</p>\n<h3>5. DOCUMENTOS CONSULTADOS</h3>\n<p>{{documentos_consultados}}</p>\n<br>\n<p>{{processo_comarca}}, {{data_hoje}}.</p>\n<br>\n<p style=\"text-align:center\">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>', '[\"processo_numero\",\"processo_vara\",\"processo_tribunal\",\"polo\",\"parte_contraria\",\"cliente_nome\",\"advogado_nome\",\"advogado_oab\",\"divergencias_texto\",\"conclusao\",\"documentos_consultados\",\"processo_comarca\",\"data_hoje\"]', 1, 1, 0, 1, '2026-04-24 16:04:04', '2026-04-24 16:04:04'),
(4, 1, 'Laudo Pericial — Estrutura Padrão', 'laudo', 'judicial', '<h1 style=\"text-align: center;\">LAUDO PERICIAL</h1>\n<p style=\"text-align: center;\"><strong>Processo n&ordm; {{processo_numero}}</strong></p>\n<p style=\"text-align: center;\">{{processo_vara}} &mdash; {{processo_tribunal}}</p>\n<p>&nbsp;</p>\n<h2>1. QUESITOS</h2>\n<p>{{quesitos}}</p>\n<h2>2. OBJETO DA PER&Iacute;CIA</h2>\n<p>{{objeto_pericia}}</p>\n<h2>3. METODOLOGIA</h2>\n<p>{{metodologia}}</p>\n<h2>4. AN&Aacute;LISE T&Eacute;CNICA</h2>\n<p>{{analise_tecnica}}</p>\n<p>{{#if ibutg_registrado}}</p>\n<h2>5. REGISTRO DE IBUTG &mdash; NR-15</h2>\n<p><strong>&Iacute;ndice de Bulbo &Uacute;mido Term&ocirc;metro de Globo (IBUTG):</strong> {{ibutg_registrado}} &deg;C<br><strong>Data/Hora da Medi&ccedil;&atilde;o:</strong> {{ibutg_data}}<br><strong>Local:</strong> {{ibutg_local}}<br><strong>Limite NR-15:</strong> {{ibutg_limite}} &deg;C ({{ibutg_regime}})</p>\n<p>{{/if}}</p>\n<h2>6. RESPOSTAS AOS QUESITOS</h2>\n<p>{{respostas_quesitos}}</p>\n<h2>7. CONCLUS&Atilde;O</h2>\n<p>{{conclusao}}</p>\n<h2>8. MEM&Oacute;RIA DE C&Aacute;LCULO</h2>\n<p>{{memoria_calculo}}</p>\n<p>&nbsp;</p>\n<p>{{processo_comarca}}, {{data_hoje}}.</p>\n<p>&nbsp;</p>\n<p style=\"text-align: center;\">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>', '[\"processo_numero\",\"processo_vara\",\"processo_tribunal\",\"quesitos\",\"objeto_pericia\",\"metodologia\",\"analise_tecnica\",\"respostas_quesitos\",\"conclusao\",\"memoria_calculo\",\"ibutg_registrado\",\"ibutg_data\",\"ibutg_local\",\"ibutg_limite\",\"ibutg_regime\",\"processo_comarca\",\"data_hoje\",\"advogado_nome\",\"advogado_oab\"]', 1, 1, 1, 1, '2026-04-24 16:04:04', '2026-04-26 17:29:21'),
(5, 1, 'Notificação Extrajudicial', 'notificacao', 'extrajudicial', '<h2 style=\"text-align: center; text-transform: uppercase;\">NOTIFICA&Ccedil;&Atilde;O EXTRAJUDICIAL</h2>\n<p>&nbsp;</p>\n<p><strong>{{notificante_nome}}</strong>, {{notificante_qualificacao}}, vem, por meio de seu(sua) advogado(a) {{advogado_nome}}, {{advogado_oab}}, NOTIFICAR {{notificado_nome}}, {{notificado_qualificacao}}, nos seguintes termos:</p>\n<h3>DOS FATOS E FUNDAMENTOS</h3>\n<p>{{fatos}}</p>\n<h3>DO PEDIDO</h3>\n<p>Pelo exposto, NOTIFICA-SE o destinat&aacute;rio para que, no prazo de <strong>{{prazo_dias}} ({{prazo_dias_extenso}}) dias</strong> &uacute;teis a contar do recebimento desta, {{pedido_notificacao}}.</p>\n<p>Caso n&atilde;o seja atendida a presente notifica&ccedil;&atilde;o no prazo acima estipulado, o notificante tomar&aacute; as medidas judiciais cab&iacute;veis, sem preju&iacute;zo de eventuais perdas e danos.</p>\n<p>{{processo_comarca}}, {{data_hoje}}.</p>\n<p>&nbsp;</p>\n<p style=\"text-align: center;\">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>', '[\"notificante_nome\",\"notificante_qualificacao\",\"notificado_nome\",\"notificado_qualificacao\",\"advogado_nome\",\"advogado_oab\",\"fatos\",\"prazo_dias\",\"prazo_dias_extenso\",\"pedido_notificacao\",\"processo_comarca\",\"data_hoje\"]', 1, 1, 3, 1, '2026-04-24 16:04:04', '2026-04-26 08:17:43'),
(6, 1, 'Contrato de Honorários Advocatícios', 'contrato', 'honorarios', '<h2 style=\"text-align: center; text-transform: uppercase;\">CONTRATO DE PRESTA&Ccedil;&Atilde;O DE SERVI&Ccedil;OS ADVOCAT&Iacute;CIOS</h2>\n<p>&nbsp;</p>\n<p><strong>CONTRATANTE:</strong> {{cliente_nome}}, {{cliente_qualificacao}}, {{cliente_doc}}, residente e domiciliado(a) &agrave; {{cliente_endereco}}.</p>\n<p><strong>CONTRATADO(A):</strong> {{advogado_nome}}, inscrito(a) na {{advogado_oab}}, com escrit&oacute;rio na {{escritorio_endereco}}.</p>\n<p>&nbsp;</p>\n<h3>CL&Aacute;USULA 1&ordf; &mdash; DO OBJETO</h3>\n<p>O(A) CONTRATADO(A) compromete-se a prestar servi&ccedil;os advocat&iacute;cios ao(&agrave;) CONTRATANTE na seguinte causa: <strong>{{objeto_causa}}</strong>, perante {{processo_vara}}, {{processo_tribunal}}.</p>\n<h3>CL&Aacute;USULA 2&ordf; &mdash; DOS HONOR&Aacute;RIOS</h3>\n<p>Pelos servi&ccedil;os ora contratados, o(a) CONTRATANTE pagar&aacute; ao(&agrave;) CONTRATADO(A) a t&iacute;tulo de honor&aacute;rios advocat&iacute;cios {{descricao_honorarios}}.</p>\n<h3>CL&Aacute;USULA 3&ordf; &mdash; DAS DESPESAS</h3>\n<p>As despesas processuais, custas judiciais, emolumentos e outras despesas necess&aacute;rias ao bom andamento da causa correr&atilde;o por conta do(a) CONTRATANTE, devendo ser reembolsadas ao(&agrave;) CONTRATADO(A) quando adiantadas por este(a).</p>\n<h3>CL&Aacute;USULA 4&ordf; &mdash; DA VIG&Ecirc;NCIA</h3>\n<p>O presente contrato vigorar&aacute; at&eacute; o tr&acirc;nsito em julgado da decis&atilde;o final, incluindo eventuais recursos e fase de execu&ccedil;&atilde;o.</p>\n<h3>CL&Aacute;USULA 5&ordf; &mdash; DO FORO</h3>\n<p>Fica eleito o Foro da Comarca de {{processo_comarca}} para dirimir quaisquer d&uacute;vidas decorrentes do presente instrumento.</p>\n<p>&nbsp;</p>\n<p>Por estarem assim justos e contratados, assinam o presente instrumento em 2 (duas) vias de igual teor e forma.</p>\n<p>{{processo_comarca}}, {{data_hoje}}.</p>\n<p>&nbsp;</p>\n<div style=\"display: flex; justify-content: space-between; margin-top: 40px;\">\n<div style=\"text-align: center;\">______________________________<br><strong>{{cliente_nome}}</strong><br>CONTRATANTE</div>\n<div style=\"text-align: center;\">______________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}<br>CONTRATADO(A)</div>\n</div>', '[\"cliente_nome\",\"cliente_qualificacao\",\"cliente_doc\",\"cliente_endereco\",\"advogado_nome\",\"advogado_oab\",\"escritorio_endereco\",\"objeto_causa\",\"processo_vara\",\"processo_tribunal\",\"descricao_honorarios\",\"processo_comarca\",\"data_hoje\"]', 1, 1, 0, 1, '2026-04-24 16:04:04', '2026-04-24 17:04:51'),
(7, 1, 'teste de gravação no ged', 'outro', '', '<p>{{processo_titulo}}testes de grava&ccedil;&atilde;o no ged{{processo_comarca}}</p>', NULL, 1, 1, 14, 1, '2026-04-25 11:04:56', '2026-04-26 16:43:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_pagamentos`
--

CREATE TABLE `financeiro_pagamentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `receita_id` bigint(20) UNSIGNED DEFAULT NULL,
  `descricao` varchar(500) NOT NULL,
  `valor` decimal(18,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `forma` enum('pix','ted','boleto','cartao','dinheiro','cheque') DEFAULT 'pix',
  `comprovante_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_receitas`
--

CREATE TABLE `financeiro_receitas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` enum('honorario','exito','consultoria','pericia','reembolso','outros') NOT NULL,
  `descricao` varchar(500) NOT NULL,
  `valor_previsto` decimal(18,2) NOT NULL,
  `valor_recebido` decimal(18,2) DEFAULT 0.00,
  `data_prevista` date DEFAULT NULL,
  `data_recebimento` date DEFAULT NULL,
  `nf_numero` varchar(50) DEFAULT NULL,
  `status` enum('previsto','parcial','recebido','cancelado','inadimplente') DEFAULT 'previsto',
  `observacao` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `financeiro_receitas`
--

INSERT INTO `financeiro_receitas` (`id`, `tenant_id`, `owner_id`, `processo_id`, `cliente_id`, `tipo`, `descricao`, `valor_previsto`, `valor_recebido`, `data_prevista`, `data_recebimento`, `nf_numero`, `status`, `observacao`, `created_at`, `updated_at`) VALUES
(1, 1, 0, NULL, NULL, 'honorario', 'testeadsas', 50.00, 0.00, '2026-04-28', NULL, NULL, 'previsto', NULL, '2026-04-26 22:40:32', '2026-04-26 22:40:32'),
(2, 1, 0, NULL, NULL, 'honorario', 'testeadsas', 50.00, 0.00, '2026-04-28', NULL, NULL, 'previsto', NULL, '2026-04-26 22:44:25', '2026-04-26 22:44:25'),
(3, 1, 0, 2, NULL, 'honorario', 'teste', 100.00, 0.00, '2026-04-30', NULL, NULL, 'previsto', NULL, '2026-04-26 22:46:41', '2026-04-26 22:46:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `indices_monetarios`
--

CREATE TABLE `indices_monetarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `indice` enum('SELIC','IPCA_E','IPCA','INPC','IGP_M','CUB_SINDUSCON','TR') NOT NULL,
  `competencia` date NOT NULL,
  `valor` decimal(12,8) NOT NULL,
  `acumulado` decimal(14,8) DEFAULT NULL,
  `fonte_url` varchar(500) DEFAULT NULL,
  `lei_base` varchar(100) DEFAULT NULL,
  `importado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `indices_monetarios`
--

INSERT INTO `indices_monetarios` (`id`, `indice`, `competencia`, `valor`, `acumulado`, `fonte_url`, `lei_base`, `importado_em`) VALUES
(1, 'SELIC', '2023-01-01', 1.12000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(2, 'SELIC', '2023-02-01', 0.84000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(3, 'SELIC', '2023-03-01', 1.17000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(4, 'SELIC', '2023-04-01', 0.83000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(5, 'SELIC', '2023-05-01', 1.14000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(6, 'SELIC', '2023-06-01', 1.07000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(7, 'SELIC', '2023-07-01', 1.07000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(8, 'SELIC', '2023-08-01', 1.02000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(9, 'SELIC', '2023-09-01', 1.07000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(10, 'SELIC', '2023-10-01', 1.02000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(11, 'SELIC', '2023-11-01', 0.92000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(12, 'SELIC', '2023-12-01', 0.97000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(13, 'SELIC', '2024-01-01', 0.97000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(14, 'SELIC', '2024-02-01', 0.92000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(15, 'SELIC', '2024-03-01', 1.05000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(16, 'SELIC', '2024-04-01', 10.65000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(17, 'SELIC', '2024-05-01', 10.46000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(18, 'SELIC', '2024-06-01', 10.40000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(19, 'SELIC', '2024-07-01', 10.40000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(20, 'SELIC', '2024-08-01', 10.40000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(21, 'SELIC', '2024-09-01', 10.50000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(22, 'SELIC', '2024-10-01', 10.65000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(23, 'SELIC', '2024-11-01', 11.04000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(24, 'SELIC', '2024-12-01', 11.77000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(25, 'SELIC', '2025-01-01', 12.24000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(26, 'SELIC', '2025-02-01', 13.15000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(27, 'SELIC', '2025-03-01', 13.57000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(28, 'SELIC', '2025-04-01', 14.15000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(29, 'SELIC', '2025-05-01', 14.55000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(30, 'SELIC', '2025-06-01', 14.74000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-24 16:04:04'),
(31, 'IPCA_E', '2023-01-01', 0.53000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(32, 'IPCA_E', '2023-02-01', 0.76000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(33, 'IPCA_E', '2023-03-01', 0.71000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(34, 'IPCA_E', '2023-04-01', 0.57000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(35, 'IPCA_E', '2023-05-01', 0.36000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(36, 'IPCA_E', '2023-06-01', 0.16000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(37, 'IPCA_E', '2023-07-01', 0.08000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(38, 'IPCA_E', '2023-08-01', 0.23000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(39, 'IPCA_E', '2023-09-01', 0.37000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(40, 'IPCA_E', '2023-10-01', 0.35000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(41, 'IPCA_E', '2023-11-01', 0.29000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(42, 'IPCA_E', '2023-12-01', 0.62000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(43, 'IPCA_E', '2024-01-01', 0.42000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(44, 'IPCA_E', '2024-02-01', 0.78000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(45, 'IPCA_E', '2024-03-01', 0.39000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(46, 'IPCA_E', '2024-04-01', 0.38000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(47, 'IPCA_E', '2024-05-01', 0.46000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(48, 'IPCA_E', '2024-06-01', 0.58000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(49, 'IPCA_E', '2024-07-01', 0.30000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(50, 'IPCA_E', '2024-08-01', 0.44000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(51, 'IPCA_E', '2024-09-01', 0.57000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(52, 'IPCA_E', '2024-10-01', 0.54000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(53, 'IPCA_E', '2024-11-01', 0.45000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(54, 'IPCA_E', '2024-12-01', 0.52000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(55, 'IPCA_E', '2025-01-01', 0.60000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(56, 'IPCA_E', '2025-02-01', 1.31000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(57, 'IPCA_E', '2025-03-01', 0.22000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(58, 'IPCA_E', '2025-04-01', 0.43000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(59, 'IPCA_E', '2025-05-01', 0.40000000, NULL, NULL, 'Tema 810 STF', '2026-04-24 16:04:04'),
(60, 'INPC', '2024-01-01', 0.47000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(61, 'INPC', '2024-02-01', 0.61000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(62, 'INPC', '2024-03-01', 0.43000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(63, 'INPC', '2024-04-01', 0.37000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(64, 'INPC', '2024-05-01', 0.46000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(65, 'INPC', '2024-06-01', 0.25000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(66, 'INPC', '2024-07-01', 0.26000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(67, 'INPC', '2024-08-01', -0.14000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(68, 'INPC', '2024-09-01', 0.48000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(69, 'INPC', '2024-10-01', 0.61000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(70, 'INPC', '2024-11-01', 0.33000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(71, 'INPC', '2024-12-01', 0.48000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(72, 'INPC', '2025-01-01', 0.00000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(73, 'INPC', '2025-02-01', 1.48000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(74, 'INPC', '2025-03-01', 0.51000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(75, 'INPC', '2025-04-01', 0.48000000, NULL, NULL, 'Lei 8.177/91', '2026-04-24 16:04:04'),
(76, 'IGP_M', '2024-01-01', 0.07000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(77, 'IGP_M', '2024-02-01', 0.04000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(78, 'IGP_M', '2024-03-01', 0.18000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(79, 'IGP_M', '2024-04-01', 0.31000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(80, 'IGP_M', '2024-05-01', 0.89000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(81, 'IGP_M', '2024-06-01', 0.81000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(82, 'IGP_M', '2024-07-01', 0.61000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(83, 'IGP_M', '2024-08-01', 0.29000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(84, 'IGP_M', '2024-09-01', 0.62000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(85, 'IGP_M', '2024-10-01', 1.52000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(86, 'IGP_M', '2024-11-01', 1.30000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(87, 'IGP_M', '2024-12-01', 0.94000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(88, 'IGP_M', '2025-01-01', 0.27000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(89, 'IGP_M', '2025-02-01', 1.06000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(90, 'IGP_M', '2025-03-01', -0.34000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(91, 'IGP_M', '2025-04-01', 0.24000000, NULL, NULL, 'Contratual FGV', '2026-04-24 16:04:04'),
(92, 'CUB_SINDUSCON', '2024-01-01', 0.52000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(93, 'CUB_SINDUSCON', '2024-02-01', 0.40000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(94, 'CUB_SINDUSCON', '2024-03-01', 0.29000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(95, 'CUB_SINDUSCON', '2024-04-01', 0.66000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(96, 'CUB_SINDUSCON', '2024-05-01', 0.55000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(97, 'CUB_SINDUSCON', '2024-06-01', 0.48000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(98, 'CUB_SINDUSCON', '2024-07-01', 0.35000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(99, 'CUB_SINDUSCON', '2024-08-01', 0.28000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(100, 'CUB_SINDUSCON', '2024-09-01', 0.31000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(101, 'CUB_SINDUSCON', '2024-10-01', 0.40000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(102, 'CUB_SINDUSCON', '2024-11-01', 0.38000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(103, 'CUB_SINDUSCON', '2024-12-01', 0.42000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(104, 'CUB_SINDUSCON', '2025-01-01', 0.48000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(105, 'CUB_SINDUSCON', '2025-02-01', 0.43000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(106, 'CUB_SINDUSCON', '2025-03-01', 0.38000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(107, 'CUB_SINDUSCON', '2025-04-01', 0.41000000, NULL, NULL, 'ABNT NBR 12721', '2026-04-24 16:04:04'),
(204, 'INPC', '2025-05-01', 0.35000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:29:49'),
(207, 'IGP_M', '2025-05-01', -0.49000000, NULL, NULL, 'FGV', '2026-04-26 23:29:50'),
(216, 'INPC', '2025-06-01', 0.23000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:29:53'),
(218, 'IGP_M', '2025-06-01', -1.67000000, NULL, NULL, 'FGV', '2026-04-26 23:29:54'),
(222, 'SELIC', '2025-07-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:29:56'),
(226, 'INPC', '2025-07-01', 0.21000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:29:58'),
(232, 'IGP_M', '2025-07-01', -0.77000000, NULL, NULL, 'FGV', '2026-04-26 23:29:59'),
(235, 'SELIC', '2025-08-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:00'),
(240, 'INPC', '2025-08-01', -0.21000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:01'),
(243, 'IGP_M', '2025-08-01', 0.36000000, NULL, NULL, 'FGV', '2026-04-26 23:30:02'),
(248, 'SELIC', '2025-09-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:04'),
(273, 'INPC', '2025-09-01', 0.52000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:10'),
(274, 'IGP_M', '2025-09-01', 0.42000000, NULL, NULL, 'FGV', '2026-04-26 23:30:10'),
(279, 'SELIC', '2025-10-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:11'),
(287, 'INPC', '2025-10-01', 0.03000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:13'),
(289, 'IGP_M', '2025-10-01', -0.36000000, NULL, NULL, 'FGV', '2026-04-26 23:30:13'),
(293, 'SELIC', '2025-11-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:14'),
(299, 'INPC', '2025-11-01', 0.03000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:15'),
(301, 'IGP_M', '2025-11-01', 0.27000000, NULL, NULL, 'FGV', '2026-04-26 23:30:15'),
(308, 'SELIC', '2025-12-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:16'),
(315, 'INPC', '2025-12-01', 0.21000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:18'),
(321, 'IGP_M', '2025-12-01', -0.01000000, NULL, NULL, 'FGV', '2026-04-26 23:30:19'),
(323, 'SELIC', '2026-01-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:20'),
(330, 'INPC', '2026-01-01', 0.39000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:21'),
(333, 'IGP_M', '2026-01-01', 0.41000000, NULL, NULL, 'FGV', '2026-04-26 23:30:21'),
(338, 'SELIC', '2026-02-01', 14.90000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:22'),
(343, 'INPC', '2026-02-01', 0.56000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:23'),
(345, 'IGP_M', '2026-02-01', -0.73000000, NULL, NULL, 'FGV', '2026-04-26 23:30:24'),
(351, 'SELIC', '2026-03-01', 14.80000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:25'),
(359, 'INPC', '2026-03-01', 0.91000000, NULL, NULL, 'Lei 8.177/91', '2026-04-26 23:30:27'),
(362, 'IGP_M', '2026-03-01', 0.52000000, NULL, NULL, 'FGV', '2026-04-26 23:30:28'),
(367, 'SELIC', '2026-04-01', 14.65000000, NULL, NULL, 'Lei 14.905/2024', '2026-04-26 23:30:28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `laudos`
--

CREATE TABLE `laudos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `pericia_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('laudo_proprio','laudo_adverso','parecer_divergente','relatorio_preliminar') NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `versao` tinyint(3) UNSIGNED DEFAULT 1,
  `status` enum('rascunho','revisao','aprovado','protocolado','substituido') DEFAULT 'rascunho',
  `autor_id` bigint(20) UNSIGNED NOT NULL,
  `conteudo_json` longtext DEFAULT NULL,
  `conclusao` mediumtext DEFAULT NULL,
  `valor_apurado` decimal(18,2) DEFAULT NULL,
  `documento_path` varchar(500) DEFAULT NULL,
  `assinatura_status` enum('pendente','enviado','assinado','recusado') DEFAULT 'pendente',
  `assinafy_doc_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `laudos`
--

INSERT INTO `laudos` (`id`, `tenant_id`, `pericia_id`, `processo_id`, `tipo`, `titulo`, `versao`, `status`, `autor_id`, `conteudo_json`, `conclusao`, `valor_apurado`, `documento_path`, `assinatura_status`, `assinafy_doc_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 26/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"sdasda\",\"divergencias\":\"asddssssssssssssssssssssssssssssssssssssssssss\",\"conclusao\":\"aaaaaaaaaaaaaaaaaaaaaaaaaaaaa\"}', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', NULL, NULL, 'pendente', NULL, '2026-04-26 23:48:38', '2026-04-26 23:48:38'),
(2, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 26/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"sdasda\",\"divergencias\":\"asddssssssssssssssssssssssssssssssssssssssssss\",\"conclusao\":\"aaaaaaaaaaaaaaaaaaaaaaaaaaaaa\"}', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', NULL, NULL, 'pendente', NULL, '2026-04-26 23:49:02', '2026-04-26 23:49:02'),
(3, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 26/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"sdasda\",\"divergencias\":\"asddssssssssssssssssssssssssssssssssssssssssss\",\"conclusao\":\"aaaaaaaaaaaaaaaaaaaaaaaaaaaaa\"}', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', NULL, NULL, 'pendente', NULL, '2026-04-26 23:50:28', '2026-04-26 23:50:28'),
(4, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 26/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"hhhhhhhhhhhhh\",\"divergencias\":\"fffffffffffffffffffff\",\"conclusao\":\"sssssssssssssssss\"}', 'sssssssssssssssss', NULL, NULL, 'pendente', NULL, '2026-04-26 23:52:02', '2026-04-26 23:52:02'),
(5, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 26/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"bbbbbbbbb\",\"divergencias\":\"dfdfdfgdf\",\"conclusao\":\"dfgdfgdfg\"}', 'dfgdfgdfg', NULL, NULL, 'pendente', NULL, '2026-04-26 23:56:24', '2026-04-26 23:56:24'),
(6, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 26/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"bbbbbbbbb\",\"divergencias\":\"dfdfdfgdf\",\"conclusao\":\"dfgdfgdfg\"}', 'dfgdfgdfg', NULL, NULL, 'pendente', NULL, '2026-04-26 23:56:32', '2026-04-26 23:56:32'),
(7, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 27/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"jjjjjjjjjjjjjjj\",\"divergencias\":\"jjjjjjjjjjjjjjjjjjjjjj\",\"conclusao\":\"jjjjjjjjjjjjjjjj\"}', 'jjjjjjjjjjjjjjjj', NULL, NULL, 'pendente', NULL, '2026-04-27 00:00:27', '2026-04-27 00:00:27'),
(8, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 27/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"jjjjjjjjjjjjjjj\",\"divergencias\":\"jjjjjjjjjjjjjjjjjjjjjj\",\"conclusao\":\"jjjjjjjjjjjjjjjj\"}', 'jjjjjjjjjjjjjjjj', NULL, NULL, 'pendente', NULL, '2026-04-27 00:02:29', '2026-04-27 00:02:29'),
(9, 1, 1, 2, 'parecer_divergente', 'Parecer Divergente — 27/04/2026', 1, 'rascunho', 1, '{\"perito_oficial\":\"jjjjjjjjjjjjjjjjjjjj\",\"divergencias\":\"ggggggggggggggggggggggggg\",\"conclusao\":\"gggggggggggggggggggggggggggggggggggg\"}', 'gggggggggggggggggggggggggggggggggggg', NULL, NULL, 'pendente', NULL, '2026-04-27 09:30:04', '2026-04-27 09:30:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('prazo','tarefa','alerta_crm','datajud','financeiro','sistema','documento','alvara') NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `cor` varchar(20) DEFAULT 'blue',
  `link_url` varchar(500) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `lida_em` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `tenant_id`, `user_id`, `tipo`, `titulo`, `mensagem`, `icone`, `cor`, `link_url`, `lida`, `lida_em`, `created_at`) VALUES
(1, 1, 1, 'tarefa', 'Processo #DJ-5000116 — aguardando_decisao', '2 tarefa(s) criadas automaticamente.', 'bolt', 'blue', '/processos/1', 0, NULL, '2026-04-26 19:22:41'),
(2, 1, 1, 'tarefa', 'Processo #DJ-5000116 — execucao', '1 tarefa(s) criadas automaticamente.', 'bolt', 'blue', '/processos/1', 0, NULL, '2026-04-26 19:23:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `parecer_divergente_checklist`
--

CREATE TABLE `parecer_divergente_checklist` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `laudo_id` bigint(20) UNSIGNED NOT NULL,
  `item_ordem` tinyint(3) UNSIGNED NOT NULL,
  `categoria` enum('metodologia','calculo','norma_tecnica','premissa','conclusao','ibutg','outro') NOT NULL,
  `descricao` varchar(500) NOT NULL,
  `divergencia` mediumtext DEFAULT NULL,
  `severidade` enum('critica','alta','media','baixa') DEFAULT 'media',
  `marcado` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pericias`
--

CREATE TABLE `pericias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('judicial_oficial','judicial_assistencia','extrajudicial','arbitragem') NOT NULL,
  `perito_oficial_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assistente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `data_nomeacao` date DEFAULT NULL,
  `data_pericia` datetime DEFAULT NULL,
  `local_pericia` varchar(500) DEFAULT NULL,
  `data_laudo` date DEFAULT NULL,
  `data_laudo_adverso` date DEFAULT NULL,
  `status` enum('nomeado','agendado','realizado','laudo_emitido','impugnado','encerrado') DEFAULT 'nomeado',
  `ibutg_registrado` decimal(6,3) DEFAULT NULL,
  `ibutg_data` datetime DEFAULT NULL,
  `ibutg_local` varchar(255) DEFAULT NULL,
  `objeto_pericia` text DEFAULT NULL,
  `quesitos_autor` mediumtext DEFAULT NULL,
  `quesitos_reu` mediumtext DEFAULT NULL,
  `quesitos_juizo` mediumtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `local_realizacao` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pericias`
--

INSERT INTO `pericias` (`id`, `tenant_id`, `processo_id`, `tipo`, `perito_oficial_id`, `assistente_id`, `data_nomeacao`, `data_pericia`, `local_pericia`, `data_laudo`, `data_laudo_adverso`, `status`, `ibutg_registrado`, `ibutg_data`, `ibutg_local`, `objeto_pericia`, `quesitos_autor`, `quesitos_reu`, `quesitos_juizo`, `created_at`, `updated_at`, `local_realizacao`, `observacoes`) VALUES
(1, 1, 2, 'judicial_oficial', NULL, NULL, NULL, '2025-04-26 00:00:00', NULL, NULL, NULL, 'agendado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-26 23:42:24', '2026-04-26 23:42:24', '', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `portal_avaliacoes`
--

CREATE TABLE `portal_avaliacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nota` tinyint(3) UNSIGNED NOT NULL,
  `comentario` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `portal_mensagens`
--

CREATE TABLE `portal_mensagens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `remetente` enum('escritorio','cliente') NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `lida_em` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prazos`
--

CREATE TABLE `prazos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `tipo` enum('fatal','importante','interno','audiencia','pericia','laudo') NOT NULL,
  `data_prazo` datetime NOT NULL,
  `data_base` date DEFAULT NULL,
  `dias` smallint(6) DEFAULT NULL,
  `alerta_dias` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alerta_dias`)),
  `cumprido` tinyint(1) DEFAULT 0,
  `cumprido_em` datetime DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `processos`
--

CREATE TABLE `processos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `numero_cnj` varchar(30) DEFAULT NULL,
  `numero_interno` varchar(50) NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `tipo` enum('civel','trabalhista','criminal','tributario','previdenciario','ambiental','pericia_judicial','pericia_extrajudicial','consultoria','outro') NOT NULL,
  `subtipo` varchar(100) DEFAULT NULL,
  `modalidade` enum('advocacia','pericia_oficial','assistencia_tecnica','consultoria') DEFAULT 'advocacia',
  `status` enum('proposta','ativo','aguardando_decisao','recurso','execucao','arquivado','encerrado','suspenso') DEFAULT 'ativo',
  `fase_processual` varchar(100) DEFAULT NULL,
  `comarca` varchar(150) DEFAULT NULL,
  `vara` varchar(150) DEFAULT NULL,
  `tribunal` varchar(150) DEFAULT NULL,
  `juiz_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `polo` enum('ativo','passivo','neutro','interessado') DEFAULT 'ativo',
  `parte_contraria` varchar(255) DEFAULT NULL,
  `responsavel_id` bigint(20) UNSIGNED NOT NULL,
  `equipe_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`equipe_ids`)),
  `valor_causa` decimal(18,2) DEFAULT NULL,
  `valor_condenacao` decimal(18,2) DEFAULT NULL,
  `honorarios_tipo` enum('fixo','percentual','exito','misto') DEFAULT 'fixo',
  `honorarios_valor` decimal(18,2) DEFAULT NULL,
  `honorarios_percent` decimal(5,2) DEFAULT NULL,
  `honorarios_proposto` decimal(18,2) DEFAULT NULL,
  `honorarios_fixado` decimal(18,2) DEFAULT NULL,
  `honorarios_levantado` decimal(18,2) DEFAULT NULL,
  `prazo_fatal` date DEFAULT NULL,
  `data_distribuicao` date DEFAULT NULL,
  `data_encerramento` date DEFAULT NULL,
  `probabilidade_exito` tinyint(3) UNSIGNED DEFAULT NULL,
  `datajud_monitorado` tinyint(1) DEFAULT 0,
  `datajud_ultimo_sync` datetime DEFAULT NULL,
  `ultimo_andamento` date DEFAULT NULL,
  `dias_parado` smallint(5) UNSIGNED DEFAULT 0,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `descricao` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `processos`
--

INSERT INTO `processos` (`id`, `tenant_id`, `owner_id`, `numero_cnj`, `numero_interno`, `titulo`, `tipo`, `subtipo`, `modalidade`, `status`, `fase_processual`, `comarca`, `vara`, `tribunal`, `juiz_id`, `cliente_id`, `polo`, `parte_contraria`, `responsavel_id`, `equipe_ids`, `valor_causa`, `valor_condenacao`, `honorarios_tipo`, `honorarios_valor`, `honorarios_percent`, `honorarios_proposto`, `honorarios_fixado`, `honorarios_levantado`, `prazo_fatal`, `data_distribuicao`, `data_encerramento`, `probabilidade_exito`, `datajud_monitorado`, `datajud_ultimo_sync`, `ultimo_andamento`, `dias_parado`, `tags`, `descricao`, `created_at`, `updated_at`, `deleted_at`, `observacoes`) VALUES
(1, 1, NULL, '5000116-85.2014.8.24.0033', 'DJ-5000116', 'Processo 5000116-85.2014.8.24.0033', 'civel', NULL, 'advocacia', 'encerrado', NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', NULL, 1, NULL, NULL, NULL, 'fixo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-04-26 18:41:38', '2026-04-26 19:23:01', NULL, NULL),
(2, 1, 1, '', '', 'dsfssdf', 'trabalhista', NULL, 'advocacia', 'ativo', NULL, '', NULL, '', NULL, NULL, 'ativo', '', 1, NULL, NULL, NULL, 'fixo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, '2026-04-26 22:35:18', '2026-04-26 22:35:18', NULL, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `processo_andamentos`
--

CREATE TABLE `processo_andamentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` enum('andamento','despacho','sentenca','acordao','peticao','audiencia','pericia','laudo','notificacao','sistema') NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `descricao` mediumtext DEFAULT NULL,
  `data_andamento` datetime NOT NULL,
  `fonte` enum('manual','datajud','sistema') DEFAULT 'manual',
  `datajud_codigo` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `processo_andamentos`
--

INSERT INTO `processo_andamentos` (`id`, `tenant_id`, `processo_id`, `user_id`, `tipo`, `titulo`, `descricao`, `data_andamento`, `fonte`, `datajud_codigo`, `created_at`) VALUES
(1, 1, 1, 1, 'sistema', 'Status atualizado: ativo → aguardando_decisao', NULL, '2026-04-26 19:22:41', 'sistema', NULL, '2026-04-26 19:22:41'),
(2, 1, 1, 1, 'sistema', 'Status atualizado: aguardando_decisao → execucao', NULL, '2026-04-26 19:23:00', 'sistema', NULL, '2026-04-26 19:23:00'),
(3, 1, 1, 1, 'sistema', 'Status atualizado: execucao → encerrado', NULL, '2026-04-26 19:23:01', 'sistema', NULL, '2026-04-26 19:23:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `processo_tarefas`
--

CREATE TABLE `processo_tarefas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `processo_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `criado_por` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `descricao` text DEFAULT NULL,
  `prioridade` enum('baixa','media','alta','critica') DEFAULT 'media',
  `status` enum('pendente','em_andamento','concluida','cancelada') DEFAULT 'pendente',
  `data_vencimento` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `gatilho_status` varchar(50) DEFAULT NULL,
  `notificar_cliente` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `responsavel_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `processo_tarefas`
--

INSERT INTO `processo_tarefas` (`id`, `tenant_id`, `processo_id`, `user_id`, `criado_por`, `titulo`, `descricao`, `prioridade`, `status`, `data_vencimento`, `data_conclusao`, `gatilho_status`, `notificar_cliente`, `created_at`, `updated_at`, `responsavel_id`) VALUES
(1, 1, 1, 1, 1, 'Monitorar publicação da decisão no DJE', NULL, 'alta', 'pendente', '2026-04-27 19:22:41', NULL, 'aguardando_decisao', 0, '2026-04-26 19:22:41', '2026-04-26 19:22:41', NULL),
(2, 1, 1, 1, 1, 'Verificar prazo para interposição de recurso', NULL, 'critica', 'pendente', '2026-04-29 19:22:41', NULL, 'aguardando_decisao', 0, '2026-04-26 19:22:41', '2026-04-26 19:22:41', NULL),
(3, 1, 1, 1, 1, 'Iniciar liquidação de sentença', NULL, 'critica', 'pendente', '2026-05-01 19:23:00', NULL, 'execucao', 0, '2026-04-26 19:23:00', '2026-04-26 19:23:00', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `tenant_id`, `ip_address`, `user_agent`, `payload`, `expires_at`, `created_at`) VALUES
('40ffe487c0a66a9aff69c2820b7333a41c6eb4fe1fea7e90296166cb2c79efc2', 1, 1, '170.82.179.210', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsInRpZCI6MSwib3duZXIiOm51bGwsInBlcmZpbCI6ImFkbWluIiwiaWF0IjoxNzc3MjkyOTYzLCJleHAiOjE3NzczMjE3NjN9.EyWGriiEbGcX3CItW48n-yDYHugNk79ivOcPfyV2n0Q', '2026-04-27 17:29:23', '2026-04-27 09:29:23'),
('6b53abaed71540b1fedac82afd1f30d5e22cc4015e28e21edb1fa549cdc6f6c2', 1, 1, '170.82.179.210', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsInRpZCI6MSwib3duZXIiOm51bGwsInBlcmZpbCI6ImFkbWluIiwiaWF0IjoxNzc3MjkyOTg4LCJleHAiOjE3NzczMjE3ODh9.y6yfuhqXUMlTLUsLek98nkNvKWZRcVB3doV0pp84mrw', '2026-04-27 17:29:48', '2026-04-27 09:29:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `stakeholders`
--

CREATE TABLE `stakeholders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('cliente','juiz','diretor_secretaria','parceiro','contraparte','perito_oficial','assistente_tecnico','outro') NOT NULL,
  `nome` varchar(255) NOT NULL,
  `nome_social` varchar(255) DEFAULT NULL,
  `cpf_cnpj` varchar(18) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `comarca` varchar(100) DEFAULT NULL,
  `vara` varchar(100) DEFAULT NULL,
  `tribunal` varchar(100) DEFAULT NULL,
  `oab_numero` varchar(30) DEFAULT NULL,
  `oab_uf` char(2) DEFAULT NULL,
  `endereco_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`endereco_json`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `score_engajamento` tinyint(3) UNSIGNED DEFAULT 50,
  `ultimo_contato` datetime DEFAULT NULL,
  `responsavel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `stakeholders`
--

INSERT INTO `stakeholders` (`id`, `tenant_id`, `tipo`, `nome`, `nome_social`, `cpf_cnpj`, `email`, `telefone`, `whatsapp`, `data_nascimento`, `comarca`, `vara`, `tribunal`, `oab_numero`, `oab_uf`, `endereco_json`, `tags`, `score_engajamento`, `ultimo_contato`, `responsavel_id`, `notas`, `ativo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'cliente', 'Tiburcio Pancotto de Barcelos', NULL, '02034955943', 'tibabarcelos@gmail.com', '47996018551', '5547996018551', '1979-01-02', NULL, NULL, NULL, NULL, NULL, '{\"logradouro\":\"\",\"numero\":\"\",\"bairro\":\"\",\"cidade\":\"\",\"uf\":\"\",\"cep\":\"\"}', NULL, 50, NULL, NULL, '', 1, '2026-04-25 10:53:37', '2026-04-26 18:44:16', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `razao_social` varchar(255) NOT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `plano` enum('starter','professional','enterprise') DEFAULT 'professional',
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `assinafy_key` varchar(255) DEFAULT NULL,
  `whatsapp_token` varchar(255) DEFAULT NULL,
  `datajud_key` varchar(255) DEFAULT NULL,
  `valor_km` decimal(6,4) DEFAULT 0.9000,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` smallint(6) DEFAULT 587,
  `smtp_encryption` varchar(10) DEFAULT 'tls',
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_pass` varchar(255) DEFAULT NULL,
  `smtp_from_name` varchar(255) DEFAULT NULL,
  `smtp_from_addr` varchar(255) DEFAULT NULL,
  `whatsapp_provider` varchar(50) DEFAULT 'evolution',
  `whatsapp_base_url` varchar(255) DEFAULT NULL,
  `whatsapp_instance` varchar(255) DEFAULT NULL,
  `whatsapp_api_key` varchar(255) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT 'America/Sao_Paulo',
  `assinafy_account_id` varchar(100) DEFAULT NULL,
  `oab_numero` varchar(20) DEFAULT NULL,
  `oab_uf` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tenants`
--

INSERT INTO `tenants` (`id`, `slug`, `razao_social`, `cnpj`, `plano`, `owner_id`, `logo_path`, `assinafy_key`, `whatsapp_token`, `datajud_key`, `valor_km`, `created_at`, `updated_at`, `deleted_at`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_user`, `smtp_pass`, `smtp_from_name`, `smtp_from_addr`, `whatsapp_provider`, `whatsapp_base_url`, `whatsapp_instance`, `whatsapp_api_key`, `timezone`, `assinafy_account_id`, `oab_numero`, `oab_uf`) VALUES
(1, 'banca-teste', 'Banca teste', NULL, 'professional', 1, NULL, 'QukN57yTN9wCXklC1DXy4kVv0JFhLZ-O8aiA2uAtAuyQOx-5ACL0cMiN0hmWvZMq', NULL, 'APIKey cDZHYzlZa0JadVREZDJCendQbXY6SkJlTzNjLV9TRENyQk1RdnFKZGRQdw==', 0.9000, '2026-04-24 16:04:04', '2026-04-26 08:12:03', NULL, NULL, 587, 'tls', NULL, NULL, NULL, NULL, 'evolution', 'http://161.97.126.36:8080', 'whats_adv', '22F589B1FC75-4455-9ECC-DF72EEF763A2', 'America/Sao_Paulo', '1028fad53cd0a29345a44bd79af4', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Silo financeiro sócio',
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `oab_numero` varchar(30) DEFAULT NULL,
  `oab_uf` char(2) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `endereco_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`endereco_json`)),
  `assinafy_email` varchar(255) DEFAULT NULL,
  `perfil` enum('admin','socio','advogado','perito','assistente','financeiro','cliente') DEFAULT 'advogado',
  `avatar_path` varchar(500) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `dois_fatores` tinyint(1) DEFAULT 0,
  `totp_secret` varchar(64) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `owner_id`, `nome`, `email`, `password_hash`, `oab_numero`, `oab_uf`, `cpf`, `endereco_json`, `assinafy_email`, `perfil`, `avatar_path`, `telefone`, `data_nascimento`, `dois_fatores`, `totp_secret`, `ultimo_login`, `ativo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 'Anderson', 'admin@themis.com', '$2y$12$b0Cz0gZl/ntaLjddh91BR.vcjPuzTrIh4dakjP/NGbyACDpjIeGai', '19259', 'SC', '', '{\"logradouro\":\"\",\"numero\":\"\",\"bairro\":\"\",\"cidade\":\"\",\"uf\":\"\",\"cep\":\"\"}', 'tibabarcelos@gmail.com', 'admin', NULL, '', NULL, 0, NULL, '2026-04-27 09:29:48', 1, '2026-04-24 16:04:04', '2026-04-27 09:29:48', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `webhook_eventos`
--

CREATE TABLE `webhook_eventos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `fonte` enum('assinafy','whatsapp','email','datajud','oab','interno') NOT NULL,
  `evento` varchar(100) NOT NULL,
  `payload` longtext NOT NULL,
  `status` enum('recebido','processado','erro','ignorado') DEFAULT 'recebido',
  `processado_em` datetime DEFAULT NULL,
  `erro_msg` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agenda_eventos`
--
ALTER TABLE `agenda_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_inicio` (`tenant_id`,`inicio`),
  ADD KEY `idx_processo` (`processo_id`);

--
-- Índices de tabela `alvaras_monitoramento`
--
ALTER TABLE `alvaras_monitoramento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `api_retry_queue`
--
ALTER TABLE `api_retry_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_retry` (`status`,`proximo_retry`),
  ADD KEY `idx_servico` (`servico`,`status`);

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_acao` (`tenant_id`,`acao`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_modulo_entidade` (`modulo`,`entidade_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `calculos`
--
ALTER TABLE `calculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`);

--
-- Índices de tabela `crm_alertas`
--
ALTER TABLE `crm_alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alerta` (`tenant_id`,`data_alerta`,`lido`);

--
-- Índices de tabela `crm_interacoes`
--
ALTER TABLE `crm_interacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stakeholder` (`stakeholder_id`),
  ADD KEY `idx_data` (`data_interacao`),
  ADD KEY `idx_proxima_acao` (`proxima_acao`);

--
-- Índices de tabela `datajud_movimentos`
--
ALTER TABLE `datajud_movimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_numero_cnj` (`numero_cnj`),
  ADD KEY `idx_data` (`data_movimento`);

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_owner` (`tenant_id`,`owner_id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_data` (`data_despesa`);

--
-- Índices de tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_hash` (`nome_hash`),
  ADD KEY `idx_lixeira` (`deleted_at`);

--
-- Índices de tabela `doc_gerados`
--
ALTER TABLE `doc_gerados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_template` (`template_id`);

--
-- Índices de tabela `doc_templates`
--
ALTER TABLE `doc_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_tipo` (`tenant_id`,`tipo`);

--
-- Índices de tabela `financeiro_pagamentos`
--
ALTER TABLE `financeiro_pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_owner` (`tenant_id`,`owner_id`),
  ADD KEY `idx_receita` (`receita_id`);

--
-- Índices de tabela `financeiro_receitas`
--
ALTER TABLE `financeiro_receitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_owner` (`tenant_id`,`owner_id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `indices_monetarios`
--
ALTER TABLE `indices_monetarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_indice_comp` (`indice`,`competencia`),
  ADD KEY `idx_indice_data` (`indice`,`competencia`);

--
-- Índices de tabela `laudos`
--
ALTER TABLE `laudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pericia` (`pericia_id`),
  ADD KEY `idx_processo` (`processo_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_lida` (`user_id`,`lida`,`created_at`);

--
-- Índices de tabela `parecer_divergente_checklist`
--
ALTER TABLE `parecer_divergente_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_laudo` (`laudo_id`);

--
-- Índices de tabela `pericias`
--
ALTER TABLE `pericias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `portal_avaliacoes`
--
ALTER TABLE `portal_avaliacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `portal_mensagens`
--
ALTER TABLE `portal_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_processo` (`processo_id`);

--
-- Índices de tabela `prazos`
--
ALTER TABLE `prazos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_data_prazo` (`data_prazo`),
  ADD KEY `idx_tenant_data` (`tenant_id`,`data_prazo`,`cumprido`);

--
-- Índices de tabela `processos`
--
ALTER TABLE `processos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_numero_cnj` (`numero_cnj`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_responsavel` (`responsavel_id`),
  ADD KEY `idx_prazo_fatal` (`prazo_fatal`),
  ADD KEY `idx_dias_parado` (`dias_parado`);
ALTER TABLE `processos` ADD FULLTEXT KEY `idx_ft_titulo` (`titulo`);

--
-- Índices de tabela `processo_andamentos`
--
ALTER TABLE `processo_andamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_data` (`data_andamento`);

--
-- Índices de tabela `processo_tarefas`
--
ALTER TABLE `processo_tarefas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_processo` (`processo_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_data_vencimento` (`data_vencimento`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Índices de tabela `stakeholders`
--
ALTER TABLE `stakeholders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_tipo` (`tenant_id`,`tipo`),
  ADD KEY `idx_ultimo_contato` (`ultimo_contato`),
  ADD KEY `idx_responsavel` (`responsavel_id`);
ALTER TABLE `stakeholders` ADD FULLTEXT KEY `idx_ft_nome` (`nome`);

--
-- Índices de tabela `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `fk_tenant_owner` (`owner_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email_tenant` (`email`,`tenant_id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_owner` (`owner_id`);

--
-- Índices de tabela `webhook_eventos`
--
ALTER TABLE `webhook_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_fonte` (`tenant_id`,`fonte`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agenda_eventos`
--
ALTER TABLE `agenda_eventos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `alvaras_monitoramento`
--
ALTER TABLE `alvaras_monitoramento`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `api_retry_queue`
--
ALTER TABLE `api_retry_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `calculos`
--
ALTER TABLE `calculos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `crm_alertas`
--
ALTER TABLE `crm_alertas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `crm_interacoes`
--
ALTER TABLE `crm_interacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `datajud_movimentos`
--
ALTER TABLE `datajud_movimentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `despesas`
--
ALTER TABLE `despesas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de tabela `doc_gerados`
--
ALTER TABLE `doc_gerados`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de tabela `doc_templates`
--
ALTER TABLE `doc_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `financeiro_pagamentos`
--
ALTER TABLE `financeiro_pagamentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `financeiro_receitas`
--
ALTER TABLE `financeiro_receitas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `indices_monetarios`
--
ALTER TABLE `indices_monetarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=702;

--
-- AUTO_INCREMENT de tabela `laudos`
--
ALTER TABLE `laudos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `parecer_divergente_checklist`
--
ALTER TABLE `parecer_divergente_checklist`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pericias`
--
ALTER TABLE `pericias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `portal_avaliacoes`
--
ALTER TABLE `portal_avaliacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `portal_mensagens`
--
ALTER TABLE `portal_mensagens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `prazos`
--
ALTER TABLE `prazos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `processos`
--
ALTER TABLE `processos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `processo_andamentos`
--
ALTER TABLE `processo_andamentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `processo_tarefas`
--
ALTER TABLE `processo_tarefas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `stakeholders`
--
ALTER TABLE `stakeholders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `webhook_eventos`
--
ALTER TABLE `webhook_eventos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `calculos`
--
ALTER TABLE `calculos`
  ADD CONSTRAINT `fk_calc_processo` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`);

--
-- Restrições para tabelas `despesas`
--
ALTER TABLE `despesas`
  ADD CONSTRAINT `fk_desp_processo` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `fk_doc_processo` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `laudos`
--
ALTER TABLE `laudos`
  ADD CONSTRAINT `fk_laudo_pericia` FOREIGN KEY (`pericia_id`) REFERENCES `pericias` (`id`);

--
-- Restrições para tabelas `parecer_divergente_checklist`
--
ALTER TABLE `parecer_divergente_checklist`
  ADD CONSTRAINT `fk_pdc_laudo` FOREIGN KEY (`laudo_id`) REFERENCES `laudos` (`id`);

--
-- Restrições para tabelas `prazos`
--
ALTER TABLE `prazos`
  ADD CONSTRAINT `fk_prazos_processo` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `processos`
--
ALTER TABLE `processos`
  ADD CONSTRAINT `fk_proc_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `stakeholders` (`id`),
  ADD CONSTRAINT `fk_proc_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_proc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

--
-- Restrições para tabelas `stakeholders`
--
ALTER TABLE `stakeholders`
  ADD CONSTRAINT `fk_sh_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

--
-- Restrições para tabelas `tenants`
--
ALTER TABLE `tenants`
  ADD CONSTRAINT `fk_tenant_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
