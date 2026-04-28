-- ============================================================
-- Themis Enterprise Legal Management — Schema SQL v2.0
-- MySQL 8.0+ | utf8mb4_unicode_ci
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';

-- ------------------------------------------------------------
-- TENANTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tenants (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(80)  NOT NULL UNIQUE,
    razao_social  VARCHAR(255) NOT NULL,
    cnpj          VARCHAR(18)  UNIQUE,
    plano         ENUM('starter','professional','enterprise') DEFAULT 'professional',
    owner_id      BIGINT UNSIGNED NULL,
    logo_path     VARCHAR(500),
    assinafy_key  VARCHAR(255),
    whatsapp_token VARCHAR(255),
    datajud_key   VARCHAR(255),
    valor_km      DECIMAL(6,4) DEFAULT 0.9000,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    owner_id        BIGINT UNSIGNED NULL COMMENT 'Silo financeiro sócio',
    nome            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    oab_numero      VARCHAR(30)  NULL,
    oab_uf          CHAR(2)      NULL,
    perfil          ENUM('admin','socio','advogado','perito','assistente','financeiro','cliente') DEFAULT 'advogado',
    avatar_path     VARCHAR(500),
    telefone        VARCHAR(20),
    data_nascimento DATE         NULL,
    dois_fatores    TINYINT(1)   DEFAULT 0,
    totp_secret     VARCHAR(64)  NULL,
    ultimo_login    DATETIME     NULL,
    ativo           TINYINT(1)   DEFAULT 1,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME     NULL,
    UNIQUE KEY uq_email_tenant (email, tenant_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_owner  (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SESSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id          VARCHAR(128) PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    ip_address  VARCHAR(45),
    user_agent  TEXT,
    payload     LONGTEXT,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user    (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CRM — STAKEHOLDERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stakeholders (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    tipo              ENUM('cliente','juiz','diretor_secretaria','parceiro','contraparte','perito_oficial','assistente_tecnico','outro') NOT NULL,
    nome              VARCHAR(255) NOT NULL,
    nome_social       VARCHAR(255) NULL,
    cpf_cnpj          VARCHAR(18)  NULL,
    email             VARCHAR(255) NULL,
    telefone          VARCHAR(20)  NULL,
    whatsapp          VARCHAR(20)  NULL,
    data_nascimento   DATE         NULL,
    comarca           VARCHAR(100) NULL,
    vara              VARCHAR(100) NULL,
    tribunal          VARCHAR(100) NULL,
    oab_numero        VARCHAR(30)  NULL,
    oab_uf            CHAR(2)      NULL,
    endereco_json     JSON         NULL,
    tags              JSON         NULL,
    score_engajamento TINYINT UNSIGNED DEFAULT 50,
    ultimo_contato    DATETIME     NULL,
    responsavel_id    BIGINT UNSIGNED NULL,
    notas             TEXT         NULL,
    ativo             TINYINT(1)   DEFAULT 1,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME     NULL,
    INDEX idx_tenant_tipo    (tenant_id, tipo),
    INDEX idx_ultimo_contato (ultimo_contato),
    INDEX idx_responsavel    (responsavel_id),
    FULLTEXT idx_ft_nome     (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_interacoes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    stakeholder_id  BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    processo_id     BIGINT UNSIGNED NULL,
    tipo            ENUM('email','whatsapp','reuniao','ligacao','cafe','visita','outro') NOT NULL,
    titulo          VARCHAR(255) NOT NULL,
    descricao       TEXT         NULL,
    sentimento      ENUM('positivo','neutro','negativo') DEFAULT 'neutro',
    data_interacao  DATETIME     NOT NULL,
    proxima_acao    DATE         NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stakeholder  (stakeholder_id),
    INDEX idx_data         (data_interacao),
    INDEX idx_proxima_acao (proxima_acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_alertas (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    stakeholder_id  BIGINT UNSIGNED NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    tipo            ENUM('aniversario','followup_vencido','sem_contato','reuniao','personalizado') NOT NULL,
    mensagem        VARCHAR(500) NOT NULL,
    data_alerta     DATE         NOT NULL,
    lido            TINYINT(1)   DEFAULT 0,
    lido_em         DATETIME     NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alerta (tenant_id, data_alerta, lido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PROCESSOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS processos (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id            BIGINT UNSIGNED NOT NULL,
    owner_id             BIGINT UNSIGNED NULL,
    numero_cnj           VARCHAR(30)  NULL,
    numero_interno       VARCHAR(50)  NOT NULL,
    titulo               VARCHAR(500) NOT NULL,
    tipo                 ENUM('civel','trabalhista','criminal','tributario','previdenciario','ambiental','pericia_judicial','pericia_extrajudicial','consultoria','outro') NOT NULL,
    subtipo              VARCHAR(100) NULL,
    modalidade           ENUM('advocacia','pericia_oficial','assistencia_tecnica','consultoria') DEFAULT 'advocacia',
    status               ENUM('proposta','ativo','aguardando_decisao','recurso','execucao','arquivado','encerrado','suspenso') DEFAULT 'ativo',
    fase_processual      VARCHAR(100) NULL,
    comarca              VARCHAR(150) NULL,
    vara                 VARCHAR(150) NULL,
    tribunal             VARCHAR(150) NULL,
    juiz_id              BIGINT UNSIGNED NULL,
    cliente_id           BIGINT UNSIGNED NOT NULL,
    polo                 ENUM('ativo','passivo','neutro','interessado') DEFAULT 'ativo',
    parte_contraria      VARCHAR(255) NULL,
    responsavel_id       BIGINT UNSIGNED NOT NULL,
    equipe_ids           JSON         NULL,
    valor_causa          DECIMAL(18,2) NULL,
    valor_condenacao     DECIMAL(18,2) NULL,
    honorarios_tipo      ENUM('fixo','percentual','exito','misto') DEFAULT 'fixo',
    honorarios_valor     DECIMAL(18,2) NULL,
    honorarios_percent   DECIMAL(5,2)  NULL,
    honorarios_proposto  DECIMAL(18,2) NULL,
    honorarios_fixado    DECIMAL(18,2) NULL,
    honorarios_levantado DECIMAL(18,2) NULL,
    prazo_fatal          DATE         NULL,
    data_distribuicao    DATE         NULL,
    data_encerramento    DATE         NULL,
    probabilidade_exito  TINYINT UNSIGNED NULL,
    datajud_monitorado   TINYINT(1)   DEFAULT 0,
    datajud_ultimo_sync  DATETIME     NULL,
    ultimo_andamento     DATE         NULL,
    dias_parado          SMALLINT UNSIGNED DEFAULT 0,
    tags                 JSON         NULL,
    descricao            TEXT         NULL,
    created_at           DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at           DATETIME     NULL,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_numero_cnj    (numero_cnj),
    INDEX idx_cliente       (cliente_id),
    INDEX idx_responsavel   (responsavel_id),
    INDEX idx_prazo_fatal   (prazo_fatal),
    INDEX idx_dias_parado   (dias_parado),
    FULLTEXT idx_ft_titulo  (titulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS processo_andamentos (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id        BIGINT UNSIGNED NOT NULL,
    processo_id      BIGINT UNSIGNED NOT NULL,
    user_id          BIGINT UNSIGNED NULL,
    tipo             ENUM('andamento','despacho','sentenca','acordao','peticao','audiencia','pericia','laudo','notificacao','sistema') NOT NULL,
    titulo           VARCHAR(500) NOT NULL,
    descricao        MEDIUMTEXT   NULL,
    data_andamento   DATETIME     NOT NULL,
    fonte            ENUM('manual','datajud','sistema') DEFAULT 'manual',
    datajud_codigo   VARCHAR(50)  NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_processo (processo_id),
    INDEX idx_data     (data_andamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS processo_tarefas (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    processo_id       BIGINT UNSIGNED NOT NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    criado_por        BIGINT UNSIGNED NOT NULL,
    titulo            VARCHAR(500) NOT NULL,
    descricao         TEXT         NULL,
    prioridade        ENUM('baixa','media','alta','critica') DEFAULT 'media',
    status            ENUM('pendente','em_andamento','concluida','cancelada') DEFAULT 'pendente',
    data_vencimento   DATETIME     NULL,
    data_conclusao    DATETIME     NULL,
    gatilho_status    VARCHAR(50)  NULL,
    notificar_cliente TINYINT(1)   DEFAULT 0,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_processo         (processo_id),
    INDEX idx_user_status      (user_id, status),
    INDEX idx_data_vencimento  (data_vencimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prazos (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    BIGINT UNSIGNED NOT NULL,
    processo_id  BIGINT UNSIGNED NOT NULL,
    user_id      BIGINT UNSIGNED NOT NULL,
    titulo       VARCHAR(500) NOT NULL,
    tipo         ENUM('fatal','importante','interno','audiencia','pericia','laudo') NOT NULL,
    data_prazo   DATETIME     NOT NULL,
    data_base    DATE         NULL,
    dias         SMALLINT     NULL,
    alerta_dias  JSON         NULL,
    cumprido     TINYINT(1)   DEFAULT 0,
    cumprido_em  DATETIME     NULL,
    observacao   TEXT         NULL,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_processo  (processo_id),
    INDEX idx_data_prazo (data_prazo),
    INDEX idx_tenant_data (tenant_id, data_prazo, cumprido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PERÍCIAS & LAUDOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pericias (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    processo_id         BIGINT UNSIGNED NOT NULL,
    tipo                ENUM('judicial_oficial','judicial_assistencia','extrajudicial','arbitragem') NOT NULL,
    perito_oficial_id   BIGINT UNSIGNED NULL,
    assistente_id       BIGINT UNSIGNED NULL,
    data_nomeacao       DATE         NULL,
    data_pericia        DATETIME     NULL,
    local_pericia       VARCHAR(500) NULL,
    data_laudo          DATE         NULL,
    data_laudo_adverso  DATE         NULL,
    status              ENUM('nomeado','agendado','realizado','laudo_emitido','impugnado','encerrado') DEFAULT 'nomeado',
    ibutg_registrado    DECIMAL(6,3) NULL,
    ibutg_data          DATETIME     NULL,
    ibutg_local         VARCHAR(255) NULL,
    objeto_pericia      TEXT         NULL,
    quesitos_autor      MEDIUMTEXT   NULL,
    quesitos_reu        MEDIUMTEXT   NULL,
    quesitos_juizo      MEDIUMTEXT   NULL,
    created_at          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_processo (processo_id),
    INDEX idx_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laudos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    pericia_id        BIGINT UNSIGNED NOT NULL,
    processo_id       BIGINT UNSIGNED NOT NULL,
    tipo              ENUM('laudo_proprio','laudo_adverso','parecer_divergente','relatorio_preliminar') NOT NULL,
    titulo            VARCHAR(500) NOT NULL,
    versao            TINYINT UNSIGNED DEFAULT 1,
    status            ENUM('rascunho','revisao','aprovado','protocolado','substituido') DEFAULT 'rascunho',
    autor_id          BIGINT UNSIGNED NOT NULL,
    conteudo_json     LONGTEXT     NULL,
    conclusao         MEDIUMTEXT   NULL,
    valor_apurado     DECIMAL(18,2) NULL,
    documento_path    VARCHAR(500) NULL,
    assinatura_status ENUM('pendente','enviado','assinado','recusado') DEFAULT 'pendente',
    assinafy_doc_id   VARCHAR(255) NULL,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pericia  (pericia_id),
    INDEX idx_processo (processo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parecer_divergente_checklist (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    laudo_id    BIGINT UNSIGNED NOT NULL,
    item_ordem  TINYINT UNSIGNED NOT NULL,
    categoria   ENUM('metodologia','calculo','norma_tecnica','premissa','conclusao','ibutg','outro') NOT NULL,
    descricao   VARCHAR(500) NOT NULL,
    divergencia MEDIUMTEXT   NULL,
    severidade  ENUM('critica','alta','media','baixa') DEFAULT 'media',
    marcado     TINYINT(1)   DEFAULT 0,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_laudo (laudo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ÍNDICES MONETÁRIOS & CÁLCULOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS indices_monetarios (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    indice       ENUM('SELIC','IPCA_E','IPCA','INPC','IGP_M','CUB_SINDUSCON','TR') NOT NULL,
    competencia  DATE         NOT NULL,
    valor        DECIMAL(12,8) NOT NULL,
    acumulado    DECIMAL(14,8) NULL,
    fonte_url    VARCHAR(500) NULL,
    lei_base     VARCHAR(100) NULL,
    importado_em DATETIME     DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_indice_comp (indice, competencia),
    INDEX idx_indice_data    (indice, competencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calculos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    processo_id       BIGINT UNSIGNED NOT NULL,
    pericia_id        BIGINT UNSIGNED NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    titulo            VARCHAR(500) NOT NULL,
    tipo              ENUM('atualizacao_monetaria','juros_mora','multa','verbas_trabalhistas','inss','fgts','honorarios','personalizado') NOT NULL,
    metodo_juros      ENUM('simples','composto','pro_rata_die') DEFAULT 'simples',
    indice_correcao   ENUM('SELIC','IPCA_E','INPC','IGP_M','TR','FIXO') DEFAULT 'SELIC',
    taxa_juros        DECIMAL(8,4) NULL,
    valor_base        DECIMAL(18,2) NOT NULL,
    data_base         DATE         NOT NULL,
    data_calculo      DATE         NOT NULL,
    valor_correcao    DECIMAL(18,2) NULL,
    valor_juros       DECIMAL(18,2) NULL,
    valor_multa       DECIMAL(18,2) NULL,
    valor_total       DECIMAL(18,2) NULL,
    memoria_calculo   JSON         NULL,
    lei_aplicada      VARCHAR(200) NULL,
    observacoes       TEXT         NULL,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_processo (processo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DESPESAS DE CAMPO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS despesas (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      BIGINT UNSIGNED NOT NULL,
    owner_id       BIGINT UNSIGNED NOT NULL,
    processo_id    BIGINT UNSIGNED NULL,
    user_id        BIGINT UNSIGNED NOT NULL,
    categoria      ENUM('km','pedagio','alimentacao','hospedagem','passagem','cartorio','copia','pericia_taxa','honorario_externo','outros') NOT NULL,
    descricao      VARCHAR(500) NOT NULL,
    data_despesa   DATE         NOT NULL,
    valor          DECIMAL(10,2) NOT NULL,
    km_percorrido  DECIMAL(8,2) NULL,
    valor_km       DECIMAL(6,4) NULL,
    recibo_path    VARCHAR(500) NULL,
    recibo_hash    CHAR(40)     NULL,
    status         ENUM('pendente','aprovado','rejeitado','reembolsado') DEFAULT 'pendente',
    aprovado_por   BIGINT UNSIGNED NULL,
    aprovado_em    DATETIME     NULL,
    reembolsado_em DATETIME     NULL,
    observacao     VARCHAR(500) NULL,
    created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_owner (tenant_id, owner_id),
    INDEX idx_processo     (processo_id),
    INDEX idx_user_status  (user_id, status),
    INDEX idx_data         (data_despesa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- FINANCEIRO SILADO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS financeiro_receitas (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id        BIGINT UNSIGNED NOT NULL,
    owner_id         BIGINT UNSIGNED NOT NULL,
    processo_id      BIGINT UNSIGNED NULL,
    cliente_id       BIGINT UNSIGNED NULL,
    tipo             ENUM('honorario','exito','consultoria','pericia','reembolso','outros') NOT NULL,
    descricao        VARCHAR(500) NOT NULL,
    valor_previsto   DECIMAL(18,2) NOT NULL,
    valor_recebido   DECIMAL(18,2) DEFAULT 0,
    data_prevista    DATE         NULL,
    data_recebimento DATE         NULL,
    nf_numero        VARCHAR(50)  NULL,
    status           ENUM('previsto','parcial','recebido','cancelado','inadimplente') DEFAULT 'previsto',
    observacao       TEXT         NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner   (tenant_id, owner_id),
    INDEX idx_processo (processo_id),
    INDEX idx_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financeiro_pagamentos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    owner_id        BIGINT UNSIGNED NOT NULL,
    receita_id      BIGINT UNSIGNED NULL,
    descricao       VARCHAR(500) NOT NULL,
    valor           DECIMAL(18,2) NOT NULL,
    data_pagamento  DATE         NOT NULL,
    forma           ENUM('pix','ted','boleto','cartao','dinheiro','cheque') DEFAULT 'pix',
    comprovante_path VARCHAR(500) NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_owner   (tenant_id, owner_id),
    INDEX idx_receita (receita_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- GED — DOCUMENTOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documentos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    processo_id       BIGINT UNSIGNED NULL,
    pericia_id        BIGINT UNSIGNED NULL,
    despesa_id        BIGINT UNSIGNED NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    categoria         ENUM('peticao','laudo','parecer','contrato','procuracao','decisao','sentenca','acordao','prova','recibo','ata','notificacao','outros') NOT NULL,
    nome_original     VARCHAR(500) NOT NULL,
    nome_hash         CHAR(40)     NOT NULL,
    caminho           VARCHAR(1000) NOT NULL,
    mime_type         VARCHAR(100) NOT NULL,
    tamanho_bytes     INT UNSIGNED NOT NULL,
    metadata_json     JSON         NULL,
    assinatura_status ENUM('nao_aplicavel','pendente','enviado','assinado','recusado') DEFAULT 'nao_aplicavel',
    assinafy_doc_id   VARCHAR(255) NULL,
    versao            TINYINT UNSIGNED DEFAULT 1,
    publico_cliente   TINYINT(1)   DEFAULT 0,
    deleted_at        DATETIME     NULL,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_processo  (processo_id),
    INDEX idx_hash      (nome_hash),
    INDEX idx_lixeira   (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TEMPLATES (AUTO-DOC)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doc_templates (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      BIGINT UNSIGNED NOT NULL,
    nome           VARCHAR(255) NOT NULL,
    tipo           ENUM('peticao','laudo','parecer','contrato','notificacao','relatorio','outro') NOT NULL,
    subtipo        VARCHAR(100) NULL,
    conteudo_html  LONGTEXT     NOT NULL,
    variaveis_json JSON         NULL,
    papel_timbrado TINYINT(1)   DEFAULT 1,
    ativo          TINYINT(1)   DEFAULT 1,
    uso_count      INT UNSIGNED DEFAULT 0,
    created_by     BIGINT UNSIGNED NOT NULL,
    created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_tipo (tenant_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doc_gerados (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      BIGINT UNSIGNED NOT NULL,
    template_id    BIGINT UNSIGNED NOT NULL,
    processo_id    BIGINT UNSIGNED NULL,
    user_id        BIGINT UNSIGNED NOT NULL,
    titulo         VARCHAR(500) NOT NULL,
    variaveis_json JSON         NULL,
    documento_id   BIGINT UNSIGNED NULL,
    created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_processo (processo_id),
    INDEX idx_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- WEBHOOKS & FILA DE CONTINGÊNCIA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS webhook_eventos (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     BIGINT UNSIGNED NOT NULL,
    fonte         ENUM('assinafy','whatsapp','email','datajud','oab','interno') NOT NULL,
    evento        VARCHAR(100) NOT NULL,
    payload       LONGTEXT     NOT NULL,
    status        ENUM('recebido','processado','erro','ignorado') DEFAULT 'recebido',
    processado_em DATETIME     NULL,
    erro_msg      TEXT         NULL,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_fonte (tenant_id, fonte),
    INDEX idx_status       (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_retry_queue (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    servico         ENUM('datajud','oab','assinafy','receita_federal','bcb_selic','ibge_ipca') NOT NULL,
    endpoint        VARCHAR(500) NOT NULL,
    payload         LONGTEXT     NULL,
    tentativas      TINYINT UNSIGNED DEFAULT 0,
    max_tentativas  TINYINT UNSIGNED DEFAULT 5,
    proximo_retry   DATETIME     NOT NULL,
    status          ENUM('pendente','processando','sucesso','falhou') DEFAULT 'pendente',
    resposta_json   TEXT         NULL,
    erro_msg        TEXT         NULL,
    contexto_json   JSON         NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_retry  (status, proximo_retry),
    INDEX idx_servico (servico, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DATAJUD / ALVARÁS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS datajud_movimentos (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id        BIGINT UNSIGNED NOT NULL,
    processo_id      BIGINT UNSIGNED NOT NULL,
    numero_cnj       VARCHAR(30)  NOT NULL,
    codigo_movimento VARCHAR(20)  NULL,
    nome_movimento   VARCHAR(500) NOT NULL,
    data_movimento   DATETIME     NOT NULL,
    complemento      TEXT         NULL,
    raw_json         JSON         NULL,
    importado_em     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_processo   (processo_id),
    INDEX idx_numero_cnj (numero_cnj),
    INDEX idx_data       (data_movimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alvaras_monitoramento (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    processo_id       BIGINT UNSIGNED NOT NULL,
    valor_alvara      DECIMAL(18,2) NOT NULL,
    status            ENUM('aguardando','expedido','levantado','cancelado') DEFAULT 'aguardando',
    data_expedicao    DATE         NULL,
    data_levantamento DATE         NULL,
    banco             VARCHAR(100) NULL,
    agencia           VARCHAR(20)  NULL,
    conta             VARCHAR(30)  NULL,
    gatilho_ativo     TINYINT(1)   DEFAULT 1,
    alerta_enviado    TINYINT(1)   DEFAULT 0,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_processo (processo_id),
    INDEX idx_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- AGENDA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agenda_eventos (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    BIGINT UNSIGNED NOT NULL,
    processo_id  BIGINT UNSIGNED NULL,
    user_ids     JSON         NOT NULL,
    tipo         ENUM('audiencia','pericia','reuniao','prazo','diligencia','confraternizacao','outro') NOT NULL,
    titulo       VARCHAR(500) NOT NULL,
    descricao    TEXT         NULL,
    local        VARCHAR(500) NULL,
    link_virtual VARCHAR(500) NULL,
    inicio       DATETIME     NOT NULL,
    fim          DATETIME     NULL,
    dia_inteiro  TINYINT(1)   DEFAULT 0,
    cor          VARCHAR(20)  DEFAULT '#3B82F6',
    recorrencia  ENUM('nenhuma','diaria','semanal','mensal') DEFAULT 'nenhuma',
    alerta_minutos INT        DEFAULT 60,
    created_by   BIGINT UNSIGNED NOT NULL,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_inicio (tenant_id, inicio),
    INDEX idx_processo      (processo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NOTIFICAÇÕES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificacoes (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id  BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    tipo       ENUM('prazo','tarefa','alerta_crm','datajud','financeiro','sistema','documento','alvara') NOT NULL,
    titulo     VARCHAR(255) NOT NULL,
    mensagem   TEXT         NOT NULL,
    icone      VARCHAR(50)  NULL,
    cor        VARCHAR(20)  DEFAULT 'blue',
    link_url   VARCHAR(500) NULL,
    lida       TINYINT(1)   DEFAULT 0,
    lida_em    DATETIME     NULL,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_lida (user_id, lida, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- AUDIT LOG
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NULL,
    acao        VARCHAR(100) NOT NULL,
    modulo      VARCHAR(100) NOT NULL,
    entidade_id BIGINT UNSIGNED NULL,
    dados_antes JSON         NULL,
    dados_depois JSON        NULL,
    ip_address  VARCHAR(45)  NULL,
    user_agent  VARCHAR(500) NULL,
    url         VARCHAR(1000) NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_acao      (tenant_id, acao),
    INDEX idx_user             (user_id),
    INDEX idx_modulo_entidade  (modulo, entidade_id),
    INDEX idx_created          (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- MENSAGENS PORTAL CLIENTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS portal_mensagens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    processo_id BIGINT UNSIGNED NULL,
    cliente_id  BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    remetente   ENUM('escritorio','cliente') NOT NULL,
    mensagem    TEXT         NOT NULL,
    lida        TINYINT(1)   DEFAULT 0,
    lida_em     DATETIME     NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cliente  (cliente_id),
    INDEX idx_processo (processo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_avaliacoes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    cliente_id  BIGINT UNSIGNED NOT NULL,
    processo_id BIGINT UNSIGNED NULL,
    nota        TINYINT UNSIGNED NOT NULL,
    comentario  TEXT         NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- FOREIGN KEYS
-- ------------------------------------------------------------
ALTER TABLE tenants ADD CONSTRAINT fk_tenant_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE users ADD CONSTRAINT fk_user_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE stakeholders ADD CONSTRAINT fk_sh_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE processos ADD CONSTRAINT fk_proc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE processos ADD CONSTRAINT fk_proc_cliente FOREIGN KEY (cliente_id) REFERENCES stakeholders(id);
ALTER TABLE processos ADD CONSTRAINT fk_proc_responsavel FOREIGN KEY (responsavel_id) REFERENCES users(id);
ALTER TABLE prazos ADD CONSTRAINT fk_prazos_processo FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE CASCADE;
ALTER TABLE despesas ADD CONSTRAINT fk_desp_processo FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE SET NULL;
ALTER TABLE documentos ADD CONSTRAINT fk_doc_processo FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE SET NULL;
ALTER TABLE calculos ADD CONSTRAINT fk_calc_processo FOREIGN KEY (processo_id) REFERENCES processos(id);
ALTER TABLE laudos ADD CONSTRAINT fk_laudo_pericia FOREIGN KEY (pericia_id) REFERENCES pericias(id);
ALTER TABLE parecer_divergente_checklist ADD CONSTRAINT fk_pdc_laudo FOREIGN KEY (laudo_id) REFERENCES laudos(id);
ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- FIM — Themis Enterprise Schema v2.0
-- ============================================================

CREATE TABLE IF NOT EXISTS portal_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    stakeholder_id  BIGINT UNSIGNED NOT NULL,
    cpf             VARCHAR(20)  NOT NULL,
    token           VARCHAR(64)  NOT NULL UNIQUE,
    nome_cliente    VARCHAR(255) NOT NULL,
    expires_at      DATETIME     NOT NULL,
    used_at         DATETIME     NULL,
    criado_por      BIGINT UNSIGNED NOT NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token     (token),
    INDEX idx_cpf       (tenant_id, cpf),
    INDEX idx_expires   (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Settings extras no tenant
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_host VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_port SMALLINT NULL DEFAULT 587;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_encryption VARCHAR(10) NULL DEFAULT 'tls';
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_user VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_pass VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_from_name VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS smtp_from_addr VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS whatsapp_provider VARCHAR(50) NULL DEFAULT 'evolution';
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS whatsapp_base_url VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS whatsapp_instance VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS whatsapp_api_key VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS timezone VARCHAR(100) NULL DEFAULT 'America/Sao_Paulo';
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS assinafy_account_id VARCHAR(100) NULL;

-- Campos adicionais para perfil do advogado
ALTER TABLE users ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) NULL AFTER oab_uf;
ALTER TABLE users ADD COLUMN IF NOT EXISTS endereco_json JSON NULL AFTER cpf;
ALTER TABLE users ADD COLUMN IF NOT EXISTS assinafy_email VARCHAR(255) NULL AFTER endereco_json;

-- Status de assinatura nos documentos GED
ALTER TABLE ged_documentos ADD COLUMN IF NOT EXISTS assinafy_status VARCHAR(20) NULL AFTER assinafy_doc_id;
