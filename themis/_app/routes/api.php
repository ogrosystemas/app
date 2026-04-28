<?php
declare(strict_types=1);

$router = new Router();

// ── Health Check ────────────────────────────────────────────
$router->get('/api/health', fn() => Response::json([
    'status'  => 'ok',
    'app'     => 'Themis Enterprise',
    'version' => '2.0.0',
    'php'     => PHP_VERSION,
    'time'    => date('c'),
]));

// ── Auth ────────────────────────────────────────────────────
$router->post('/api/auth/login',   [AuthController::class, 'login']);
$router->post('/api/auth/logout',  [AuthController::class, 'logout']);
$router->get('/api/auth/me',       [AuthController::class, 'me'],      [AuthMiddleware::class]);
$router->post('/api/auth/refresh', [AuthController::class, 'refresh'], [AuthMiddleware::class]);

// ── Processos ───────────────────────────────────────────────
$router->group('/api/processos', function (Router $r) {
    $r->get('',                       [ProcessoController::class, 'index']);
    $r->post('',                      [ProcessoController::class, 'store']);
    $r->get('/tarefas',               [ProcessoController::class, 'todasTarefas']); // ← alias frontend
    $r->get('/{id}',                  [ProcessoController::class, 'show']);
    $r->put('/{id}',                  [ProcessoController::class, 'update']);
    $r->delete('/{id}',               [ProcessoController::class, 'destroy']);
    $r->post('/{id}/status',          [ProcessoController::class, 'updateStatus']);
    $r->get('/{id}/andamentos',       [ProcessoController::class, 'andamentos']);
    $r->post('/{id}/andamentos',      [ProcessoController::class, 'addAndamento']);
    $r->get('/{id}/tarefas',          [ProcessoController::class, 'tarefas']);
    $r->post('/{id}/tarefas',         [ProcessoController::class, 'addTarefa']);
    $r->get('/{id}/prazos',           [ProcessoController::class, 'prazos']);
    $r->post('/{id}/prazos',          [ProcessoController::class, 'addPrazo']);
    $r->get('/{id}/documentos',       [ProcessoController::class, 'documentos']);
    $r->get('/{id}/calculos',         [ProcessoController::class, 'calculos']);
    $r->get('/{id}/pericias',         [ProcessoController::class, 'pericias']);
    $r->post('/{id}/sync-datajud',    [ProcessoController::class, 'syncDataJud']);
}, [AuthMiddleware::class]);

// ── Perícias & Laudos ───────────────────────────────────────
$router->group('/api/pericias', function (Router $r) {
    $r->get('',                         [PericiaController::class, 'index']);
    $r->post('',                        [PericiaController::class, 'store']);
    $r->get('/{id}',                    [PericiaController::class, 'show']);
    $r->put('/{id}',                    [PericiaController::class, 'update']);
    $r->get('/{id}/laudos',             [PericiaController::class, 'laudos']);
    $r->post('/{id}/laudos',            [PericiaController::class, 'addLaudo']);
    $r->get('/{id}/checklist',          [PericiaController::class, 'checklist']);
    $r->post('/{id}/parecer',           [PericiaController::class, 'gerarParecer']);
    // ← alias usado pelo frontend
    $r->post('/parecer-divergente',     [PericiaController::class, 'parecerDivergente']);
}, [AuthMiddleware::class]);

// ── Cálculos ────────────────────────────────────────────────
$router->group('/api/calculos', function (Router $r) {
    $r->post('/calcular',        [CalculoController::class, 'calcular']);
    $r->get('',                  [CalculoController::class, 'index']);
    $r->get('/{id}',             [CalculoController::class, 'show']);
    $r->delete('/{id}',          [CalculoController::class, 'destroy']);
    $r->get('/indices/{ind}',    [CalculoController::class, 'indices']);
    $r->post('/atualizar-indices', [CalculoController::class, 'atualizarIndices']);
}, [AuthMiddleware::class]);

// ── CRM / Stakeholders ──────────────────────────────────────
$router->group('/api/stakeholders', function (Router $r) {
    $r->get('',                             [StakeholderController::class, 'index']);
    $r->post('',                            [StakeholderController::class, 'store']);
    $r->get('/dashboard/crm',               [StakeholderController::class, 'dashboard']);
    $r->get('/aniversariantes/semana',      [StakeholderController::class, 'aniversariantes']);
    $r->get('/{id}',                        [StakeholderController::class, 'show']);
    $r->put('/{id}',                        [StakeholderController::class, 'update']);
    $r->delete('/{id}',                     [StakeholderController::class, 'destroy']);
    $r->get('/{id}/interacoes',             [StakeholderController::class, 'interacoes']);
    $r->post('/{id}/interacoes',            [StakeholderController::class, 'addInteracao']);
    $r->post('/{id}/followup',              [StakeholderController::class, 'agendarFollowup']);
}, [AuthMiddleware::class]);

// ── CRM interações — alias usado pelo frontend ───────────────
$router->group('/api/crm', function (Router $r) {
    $r->get('/interacoes',  [StakeholderController::class, 'todasInteracoes']);
    $r->post('/interacoes', [StakeholderController::class, 'addInteracaoGlobal']);
}, [AuthMiddleware::class]);

// ── Despesas de Campo ───────────────────────────────────────
$router->group('/api/despesas', function (Router $r) {
    $r->get('',                                [DespesaController::class, 'index']);
    $r->post('',                               [DespesaController::class, 'store']);
    $r->get('/{id}',                           [DespesaController::class, 'show']);
    $r->put('/{id}',                           [DespesaController::class, 'update']);
    $r->patch('/{id}/aprovar',                 [DespesaController::class, 'aprovarUm']); // ← alias frontend
    $r->post('/aprovar',                       [DespesaController::class, 'aprovarLote']);
    $r->get('/relatorio/{processoId}',         [DespesaController::class, 'relatorio']);
}, [AuthMiddleware::class]);

// ── GED / Documentos ────────────────────────────────────────
$router->group('/api/ged', function (Router $r) {
    $r->post('/upload',         [GEDController::class, 'upload']);
    $r->get('/download/{id}',   [GEDController::class, 'download']);
    $r->delete('/{id}',         [GEDController::class, 'trash']);
    $r->post('/{id}/restore',   [GEDController::class, 'restore']);
    $r->get('/lixeira',         [GEDController::class, 'lixeira']);
    $r->post('/{id}/restaurar', [GEDController::class, 'restaurar']);
    $r->post('/{id}/assinar',   [GEDController::class, 'enviarAssinatura']);
    $r->get('/pdf/{id}',        [GEDController::class, 'exportarPdf']); // ← novo
}, [AuthMiddleware::class]);

// ── Documentos — alias usado pelo frontend ───────────────────
$router->group('/api/documentos', function (Router $r) {
    $r->get('',            [GEDController::class, 'indexAlias']);
    $r->post('',           [GEDController::class, 'uploadAlias']); // multipart
    $r->get('/lixeira',    [GEDController::class, 'lixeira']);
    $r->delete('/esvaziar-lixeira', [GEDController::class, 'esvaziarLixeira']);
    $r->delete('/purge-lote',       [GEDController::class, 'purgeLote']);
    $r->post('/restaurar-lote',     [GEDController::class, 'restaurarLote']);
    $r->delete('/{id}',       [GEDController::class, 'trash']);
    $r->delete('/{id}/purge', [GEDController::class, 'purge']);
    $r->post('/{id}/restore', [GEDController::class, 'restore']);
    $r->get('/{id}/download', [GEDController::class, 'download']);
    $r->get('/{id}/pdf',      [GEDController::class, 'exportarPdf']);
}, [AuthMiddleware::class]);

// ── Templates / Auto-Doc ────────────────────────────────────
$router->group('/api/templates', function (Router $r) {
    $r->get('',             [TemplateController::class, 'index']);
    $r->post('',            [TemplateController::class, 'store']);
    $r->get('/{id}',        [TemplateController::class, 'show']);
    $r->put('/{id}',        [TemplateController::class, 'update']);
    $r->post('/{id}/gerar', [TemplateController::class, 'gerar']);
    $r->get('/{id}/pdf',    [TemplateController::class, 'exportarPdf']); // ← novo
}, [AuthMiddleware::class]);

// ── Financeiro ──────────────────────────────────────────────
$router->group('/api/financeiro', function (Router $r) {
    $r->get('/receitas',       [FinanceiroController::class, 'receitas']);
    $r->post('/receitas',      [FinanceiroController::class, 'addReceita']);
    $r->post('/pagamentos',    [FinanceiroController::class, 'addPagamento']);
    $r->get('/dashboard',      [FinanceiroController::class, 'dashboard']);
    $r->get('/kpis',           [FinanceiroController::class, 'kpis']);
    $r->get('/alvaras',        [FinanceiroController::class, 'alvaras']);
    $r->put('/alvaras/{id}',   [FinanceiroController::class, 'updateAlvara']);
}, [AuthMiddleware::class, FinanceiroSiloMiddleware::class]);

// ── Agenda ──────────────────────────────────────────────────
$router->group('/api/agenda', function (Router $r) {
    $r->get('',         [AgendaController::class, 'index']);
    $r->get('/hoje',    [AgendaController::class, 'hoje']);   // ← alias frontend
    $r->get('/mes',     [AgendaController::class, 'porMes']); // ← calendário
    $r->post('',        [AgendaController::class, 'store']);
    $r->put('/{id}',    [AgendaController::class, 'update']);
    $r->delete('/{id}', [AgendaController::class, 'destroy']);
}, [AuthMiddleware::class]);

// ── DataJud / Radar ─────────────────────────────────────────
$router->group('/api/radar', function (Router $r) {
    $r->post('/sync/{processoId}',       [RadarController::class, 'sync']);
    $r->get('/movimentos/{processoId}',  [RadarController::class, 'movimentos']);
    $r->get('/alvaras',                  [RadarController::class, 'alvaras']);
    $r->get('/parados',                  [RadarController::class, 'processosParados']);
    $r->post('/monitorar-todos',         [RadarController::class, 'monitorarTodos']);
    $r->post('/buscar-oab',             [RadarController::class, 'buscarOAB']);
    $r->post('/importar-lote',          [RadarController::class, 'importarLote']);
}, [AuthMiddleware::class]);

// ── DataJud — alias frontend ─────────────────────────────────
$router->group('/api/datajud', function (Router $r) {
    $r->get('/movimentos', [RadarController::class, 'movimentosRecentes']); // ← alias
}, [AuthMiddleware::class]);

// ── Notificações ────────────────────────────────────────────
$router->group('/api/notificacoes', function (Router $r) {
    $r->get('',                         [NotificacaoController::class, 'index']);
    $r->put('/{id}/lida',               [NotificacaoController::class, 'marcarLida']);
    $r->post('/marcar-todas',           [NotificacaoController::class, 'marcarTodas']);
    $r->post('/marcar-todas-lidas',     [NotificacaoController::class, 'marcarTodas']); // ← alias
}, [AuthMiddleware::class]);

// ── Webhooks ────────────────────────────────────────────────
$router->post('/api/webhooks/assinafy', [WebhookController::class, 'assinafy']);
$router->get('/api/webhooks/whatsapp',  [WebhookController::class, 'whatsapp']);
$router->post('/api/webhooks/whatsapp', [WebhookController::class, 'whatsapp']);
$router->post('/api/webhooks/datajud',  [WebhookController::class, 'datajud']);

// ── Portal do Cliente ────────────────────────────────────────
$router->group('/api/portal', function (Router $r) {
    $r->get('/processos',      [PortalController::class, 'processos']);
    $r->get('/documentos',     [PortalController::class, 'documentos']);
    $r->get('/prazos',         [PortalController::class, 'prazos']);
    $r->get('/financeiro',     [PortalController::class, 'financeiro']);
    $r->get('/mensagens',      [PortalController::class, 'mensagens']);
    $r->post('/mensagens',     [PortalController::class, 'enviarMensagem']);
    $r->post('/satisfacao',    [PortalController::class, 'avaliar']);
}, [AuthClienteMiddleware::class]);

// ── Workflow Kanban ─────────────────────────────────────────
$router->get('/api/workflow/kanban', function(Request $req) {
    $db  = DB::getInstance();
    $tid = $db->getTenantId();
    $statuses = ['proposta','ativo','aguardando_decisao','recurso','execucao','encerrado','arquivado'];
    $data = [];
    foreach ($statuses as $st) {
        $data[$st] = $db->all(
            "SELECT p.id, p.numero_interno, p.titulo, p.prazo_fatal, p.valor_causa, p.polo,
                    p.updated_at, s.nome AS cliente_nome, u.nome AS responsavel_nome
             FROM processos p
             LEFT JOIN stakeholders s ON s.id = p.cliente_id
             LEFT JOIN users u ON u.id = p.responsavel_id
             WHERE p.tenant_id=? AND p.status=? AND p.deleted_at IS NULL
             ORDER BY p.prazo_fatal ASC LIMIT 20",
            [$tid, $st]
        );
    }
    return Response::success($data);
}, [AuthMiddleware::class]);

// ── Configurações ───────────────────────────────────────────
$router->get('/api/settings',                [SettingsController::class, 'index'],        [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/settings',               [SettingsController::class, 'save'],         [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/settings/test-mail',     [SettingsController::class, 'testMail'],     [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/settings/test-whatsapp', [SettingsController::class, 'testWhatsapp'], [AuthMiddleware::class, AdminOnlyMiddleware::class]);

// ── Licença ─────────────────────────────────────────────────
$router->get('/api/license',          [LicenseController::class, 'status'],  [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/license/install', [LicenseController::class, 'install'], [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/license/revoke',  [LicenseController::class, 'revoke'],  [AuthMiddleware::class, AdminOnlyMiddleware::class]);

// ── Circuit Breaker ─────────────────────────────────────────
$router->get('/api/circuit/status',          [CircuitController::class, 'status'],      [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/circuit/{api}/reset',    [CircuitController::class, 'reset'],       [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/circuit/{api}/flush',    [CircuitController::class, 'flushQueue'],  [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->post('/api/circuit/process-queue', [CircuitController::class, 'processQueue'], [AuthMiddleware::class]);

// ── Audit Log ───────────────────────────────────────────────
$router->get('/api/audit',        [AuditController::class, 'index'],  [AuthMiddleware::class, AdminOnlyMiddleware::class]);
$router->get('/api/audit/verify', [AuditController::class, 'verify'], [AuthMiddleware::class, AdminOnlyMiddleware::class]);


// ── Portal — Gestão de Tokens (advogado) ─────────────────────────
$router->group('/api/portal-tokens', function (Router $r) {
    $r->get('',                  [PortalTokenController::class, 'index']);
    $r->post('',                 [PortalTokenController::class, 'gerar']);
    $r->delete('/{id}',          [PortalTokenController::class, 'revogar']);
}, [AuthMiddleware::class]);

// ── Portal — Auth do cliente (sem autenticação JWT) ───────────────
$router->post('/api/portal/auth/login', [PortalController::class, 'login']);

// ── Perfil do Usuário (Advogado) ────────────────────────────
$router->get('/api/perfil',    [PerfilController::class, 'show'],   [AuthMiddleware::class]);
$router->put('/api/perfil',    [PerfilController::class, 'update'], [AuthMiddleware::class]);

// ── Clientes (alias dedicado) ────────────────────────────────
$router->get('/api/clientes',         [ClienteController::class, 'index'],   [AuthMiddleware::class]);
$router->post('/api/clientes',        [ClienteController::class, 'store'],   [AuthMiddleware::class]);
$router->get('/api/clientes/{id}',    [ClienteController::class, 'show'],    [AuthMiddleware::class]);
$router->put('/api/clientes/{id}',    [ClienteController::class, 'update'],  [AuthMiddleware::class]);
$router->delete('/api/clientes/{id}', [ClienteController::class, 'destroy'], [AuthMiddleware::class]);


// ── Webhook Assinafy (sem autenticação JWT) ──────────────────────
$router->post('/api/webhook/assinafy', [WebhookAssinafyController::class, 'handle']);

return $router;
