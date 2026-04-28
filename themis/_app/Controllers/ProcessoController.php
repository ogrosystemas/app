<?php
declare(strict_types=1);
// ============================================================
// AuthController
// ============================================================
final class AuthController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function login(Request $req): Response
    {
        $req->validate(['email' => 'required|email', 'password' => 'required|min:6']);
        $auth = new Auth($this->db);
        $result = $auth->login($req->str('email'), $req->str('password'), $req->str('totp') ?: null);
        return Response::success($result, 'Login realizado.');
    }

    public function logout(Request $req): Response
    {
        $auth  = new Auth($this->db);
        $token = ltrim($_SERVER['HTTP_AUTHORIZATION'] ?? '', 'Bearer ');
        if ($token) $auth->logout($token);
        return Response::success(null, 'Logout realizado.');
    }

    public function me(Request $req): Response
    {
        $user = $req->user;
        unset($user['password_hash'], $user['totp_secret']);
        return Response::success($user);
    }

    public function refresh(Request $req): Response
    {
        $auth = new Auth($this->db);
        $user = $auth->guard();
        return Response::success(['message' => 'Token válido.', 'user' => $user]);
    }
}

// ============================================================
// ProcessoController
// ============================================================
final class ProcessoController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $page    = max(1, (int)$req->q('page', 1));
        $perPage = min(100, max(1, (int)$req->q('per_page', 25)));
        $status  = $req->q('status', '');
        $tipo    = $req->q('tipo', '');
        $busca   = $req->q('q', '');
        $tid     = $this->db->getTenantId();

        $where = ["p.tenant_id = {$tid}", "p.deleted_at IS NULL"];
        $params = [];
        if ($status) { $where[] = "p.status = ?"; $params[] = $status; }
        if ($tipo)   { $where[] = "p.tipo = ?";   $params[] = $tipo; }
        if ($busca)  { $where[] = "(p.titulo LIKE ? OR p.numero_cnj LIKE ? OR p.numero_interno LIKE ?)"; $b = "%{$busca}%"; $params[] = $b; $params[] = $b; $params[] = $b; }

        $sql = "SELECT p.*, s.nome AS cliente_nome, u.nome AS responsavel_nome
                FROM processos p
                LEFT JOIN stakeholders s ON s.id = p.cliente_id
                JOIN users u ON u.id = p.responsavel_id
                WHERE " . implode(' AND ', $where) . " ORDER BY p.prazo_fatal ASC, p.updated_at DESC";

        return Response::paginated($this->db->paginate($sql, $params, $page, $perPage));
    }

    public function show(Request $req): Response
    {
        $id = (int) $req->param('id');
        $p  = $this->db->first(
            "SELECT p.*, s.nome AS cliente_nome, s.cpf_cnpj AS cliente_doc,
             u.nome AS responsavel_nome, u.oab_numero, u.oab_uf
             FROM processos p
             LEFT JOIN stakeholders s ON s.id = p.cliente_id
             JOIN users u ON u.id = p.responsavel_id
             WHERE p.id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL",
            [$id, $this->db->getTenantId()]
        );
        if (!$p) throw new \RuntimeException('Processo não encontrado.', 404);
        return Response::success($p);
    }

    public function store(Request $req): Response
    {
        $req->validate(['titulo' => 'required|min:2']);
        // Campos permitidos na tabela processos
        $allowed = ['numero_cnj','numero_interno','titulo','tipo','subtipo','modalidade','polo',
                    'tribunal','comarca','vara','juiz_id','cliente_id','responsavel_id',
                    'valor_causa','data_distribuicao','prazo_fatal','parte_contraria',
                    'observacoes','status','owner_id'];
        $data = array_intersect_key($req->body, array_flip($allowed));
        // Mapear tipos do frontend para ENUM
        $tipoMap = ['trabalhista'=>'trabalhista','civel'=>'civel','criminal'=>'criminal',
                    'tributario'=>'tributario','previdenciario'=>'previdenciario',
                    'pericia'=>'pericia_judicial','outro'=>'outro','ambiental'=>'ambiental'];
        $data['tipo']           = $tipoMap[$data['tipo'] ?? ''] ?? 'outro';
        $data['status']         = $data['status']         ?? 'ativo';
        $data['tenant_id']      = $this->db->getTenantId();
        $data['owner_id']       = $data['owner_id']       ?? $req->user['id'];
        $data['responsavel_id'] = $data['responsavel_id'] ?? $req->user['id'];
        $data['numero_interno'] = $data['numero_interno'] ?? $this->gerarNumeroInterno();
        if (empty($data['cliente_id'])) unset($data['cliente_id']);
        // Salvar parte contrária como nota se não houver campo dedicado
        if (!empty($data['parte_contraria'])) {
            $data['observacoes'] = trim(($data['observacoes'] ?? '') . "
Parte contrária: " . $data['parte_contraria']);
            unset($data['parte_contraria']);
        }
        $id = (int) $this->db->insert('processos', $data);
        AuditLogger::log('create', 'processos', $id, 'Processo criado: ' . $data['titulo']);
        return Response::created(['id' => $id, 'numero_interno' => $data['numero_interno']]);
    }

    public function update(Request $req): Response
    {
        $id   = (int) $req->param('id');
        $data = $req->body;
        unset($data['id'], $data['tenant_id'], $data['created_at']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        $rows = $this->db->update('processos', $data, ['id' => $id, 'tenant_id' => $this->db->getTenantId()]);
        if (!$rows) throw new \RuntimeException('Processo não encontrado.', 404);
        return Response::success(['updated' => $rows]);
    }

    public function destroy(Request $req): Response
    {
        $this->db->softDelete('processos', (int)$req->param('id'));
        return Response::success(null, 'Processo excluído.');
    }

    public function updateStatus(Request $req): Response
    {
        $req->validate(['status' => 'required']);
        $workflow = new WorkflowEngine($this->db);
        $result   = $workflow->transition((int)$req->param('id'), $req->str('status'), (int)$req->user['id']);
        return Response::success($result, 'Status atualizado.');
    }

    public function andamentos(Request $req): Response
    {
        $id   = (int) $req->param('id');
        $data = $this->db->all(
            "SELECT a.*, u.nome AS usuario_nome FROM processo_andamentos a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.processo_id = ? AND a.tenant_id = ? ORDER BY a.data_andamento DESC",
            [$id, $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function addAndamento(Request $req): Response
    {
        $req->validate(['titulo' => 'required', 'tipo' => 'required', 'data_andamento' => 'required']);
        $d = array_merge($req->body, [
            'tenant_id'   => $this->db->getTenantId(),
            'processo_id' => (int) $req->param('id'),
            'user_id'     => (int) $req->user['id'],
            'fonte'       => 'manual',
        ]);
        $id = (int) $this->db->insert('processo_andamentos', $d);
        $this->db->run("UPDATE processos SET ultimo_andamento = ?, dias_parado = 0 WHERE id = ?", [date('Y-m-d'), $req->param('id')]);
        return Response::created(['id' => $id]);
    }

    public function todasTarefas(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $sql = "SELECT t.*, p.numero_interno, p.titulo AS processo_titulo, u.nome AS responsavel_nome
                FROM processo_tarefas t
                JOIN processos p ON p.id = t.processo_id
                LEFT JOIN users u ON u.id = t.responsavel_id
                WHERE t.tenant_id = ?";
        $par = [$tid];
        if ($st = $req->q('status')) { $sql .= " AND t.status = ?"; $par[] = $st; }
        $sql .= " ORDER BY t.data_vencimento ASC , t.created_at DESC";
        return Response::paginated($this->db->paginate($sql, $par, max(1,(int)$req->q('page',1)), 30));
    }

    public function tarefas(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT t.*, u.nome AS responsavel_nome FROM processo_tarefas t
             JOIN users u ON u.id = t.user_id
             WHERE t.processo_id = ? AND t.tenant_id = ? ORDER BY t.data_vencimento",
            [(int)$req->param('id'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function addTarefa(Request $req): Response
    {
        $req->validate(['titulo' => 'required', 'user_id' => 'required|numeric']);
        $d = array_merge($req->body, [
            'tenant_id'   => $this->db->getTenantId(),
            'processo_id' => (int) $req->param('id'),
            'criado_por'  => (int) $req->user['id'],
        ]);
        $id = (int) $this->db->insert('processo_tarefas', $d);
        return Response::created(['id' => $id]);
    }

    public function prazos(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT * FROM prazos WHERE processo_id = ? AND tenant_id = ? ORDER BY data_prazo",
            [(int)$req->param('id'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function addPrazo(Request $req): Response
    {
        $req->validate(['titulo' => 'required', 'tipo' => 'required', 'data_prazo' => 'required']);
        $d = array_merge($req->body, [
            'tenant_id'   => $this->db->getTenantId(),
            'processo_id' => (int) $req->param('id'),
            'user_id'     => (int) $req->user['id'],
        ]);
        $id = (int) $this->db->insert('prazos', $d);
        return Response::created(['id' => $id]);
    }

    public function documentos(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT d.*, u.nome AS usuario_nome FROM documentos d
             JOIN users u ON u.id = d.user_id
             WHERE d.processo_id = ? AND d.tenant_id = ? AND d.deleted_at IS NULL ORDER BY d.created_at DESC",
            [(int)$req->param('id'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function calculos(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT c.*, u.nome AS usuario_nome FROM calculos c
             JOIN users u ON u.id = c.user_id
             WHERE c.processo_id = ? AND c.tenant_id = ? ORDER BY c.created_at DESC",
            [(int)$req->param('id'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function pericias(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT * FROM pericias WHERE processo_id = ? AND tenant_id = ? ORDER BY created_at DESC",
            [(int)$req->param('id'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function syncDataJud(Request $req): Response
    {
        $proc = $this->db->first("SELECT numero_cnj FROM processos WHERE id = ? AND tenant_id = ?", [(int)$req->param('id'), $this->db->getTenantId()]);
        if (!$proc || !$proc['numero_cnj']) throw new \RuntimeException('Processo sem número CNJ.', 400);
        $svc    = new DataJudService($this->db);
        $result = $svc->consultarProcesso($proc['numero_cnj'], (int)$req->param('id'));
        return Response::success(['movimentos' => count($result['movimentos'] ?? [])]);
    }

    private function gerarNumeroInterno(): string
    {
        $ano    = date('Y');
        $ultimo = (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id = ? AND YEAR(created_at) = ?", [$this->db->getTenantId(), $ano]);
        return $ano . '/' . str_pad((string)($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }
}

// ============================================================
// CalculoController
// ============================================================
final class CalculoController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function calcular(Request $req): Response
    {
        // Normaliza campos: aceita data_inicio/data_fim (frontend) ou data_base/data_calculo (legado)
        $body = $req->body;
        if (empty($body['data_base'])    && !empty($body['data_inicio'])) $body['data_base']    = $body['data_inicio'];
        if (empty($body['data_calculo']) && !empty($body['data_fim']))    $body['data_calculo'] = $body['data_fim'];
        if (empty($body['valor_base'])   && !empty($body['valor']))       $body['valor_base']   = $body['valor'];
        if (empty($body['metodo_juros']) && !empty($body['tipo_juros']))  $body['metodo_juros'] = $body['tipo_juros'];
        if (empty($body['valor_base']) || empty($body['data_base']) || empty($body['data_calculo'])) {
            throw new \InvalidArgumentException('Informe valor, data de início e data de fim.', 422);
        }
        $engine = new CalculationEngine($this->db);
        $result = $engine->calcular($body);

        // Salvar no histórico apenas se vinculado a um processo
        $procId = $req->int('processo_id') ?: null;
        if ($procId) {
            $engine->salvar(
                $procId,
                (int) $req->user['id'],
                ($body['indice'] ?? 'SELIC') . ' — ' . ($body['data_base'] ?? '') . ' a ' . ($body['data_calculo'] ?? ''),
                $result,
                ['data_base' => $body['data_base'], 'data_calculo' => $body['data_calculo']]
            );
        }
        return Response::success($result);
    }

    public function index(Request $req): Response
    {
        $pid  = $req->q('processo_id');
        $sql  = "SELECT c.*, u.nome AS usuario_nome FROM calculos c JOIN users u ON u.id = c.user_id WHERE c.tenant_id = {$this->db->getTenantId()}";
        $par  = [];
        if ($pid) { $sql .= " AND c.processo_id = ?"; $par[] = $pid; }
        $sql .= " ORDER BY c.created_at DESC";
        return Response::paginated($this->db->paginate($sql, $par, max(1, (int)$req->q('page', 1))));
    }

    public function show(Request $req): Response
    {
        $c = $this->db->first("SELECT * FROM calculos WHERE id = ? AND tenant_id = ?", [(int)$req->param('id'), $this->db->getTenantId()]);
        if (!$c) throw new \RuntimeException('Cálculo não encontrado.', 404);
        return Response::success($c);
    }

    public function destroy(Request $req): Response
    {
        $this->db->run("DELETE FROM calculos WHERE id = ? AND tenant_id = ?", [(int)$req->param('id'), $this->db->getTenantId()]);
        return Response::success(null, 'Cálculo excluído.');
    }

    public function indices(Request $req): Response
    {
        $indice = strtoupper($req->param('ind', 'SELIC'));
        $ano    = (int) $req->q('ano', date('Y'));
        $data   = $this->db->all(
            "SELECT competencia, valor, lei_base FROM indices_monetarios WHERE indice = ? AND YEAR(competencia) = ? ORDER BY competencia",
            [$indice, $ano]
        );
        return Response::success($data);
    }

    public function atualizarIndices(Request $req): Response
    {
        $eng     = new CalculationEngine($this->db);
        $results = [];
        $meses   = 13; // último ano + mês atual

        for ($i = $meses; $i >= 0; $i--) {
            $ts  = strtotime("-{$i} months");
            $ano = (int) date('Y', $ts);
            $mes = (int) date('n', $ts);

            $results['SELIC'][]  = $eng->importarSelic($ano, $mes)  ? "{$mes}/{$ano}" : null;
            $results['IPCA_E'][] = $eng->importarIpcaE($ano, $mes)  ? "{$mes}/{$ano}" : null;
            $results['INPC'][]   = $eng->importarInpc($ano, $mes)   ? "{$mes}/{$ano}" : null;
            $results['IGP_M'][]  = $eng->importarIgpm($ano, $mes)   ? "{$mes}/{$ano}" : null;
        }

        $summary = [];
        foreach ($results as $ind => $mesesOk) {
            $summary[$ind] = count(array_filter($mesesOk)) . ' meses';
        }

        return Response::success($summary, 'Índices atualizados com sucesso.');
    }
}

// ============================================================
// PericiaController
// ============================================================
final class PericiaController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $sql = "SELECT p.*, proc.numero_interno, proc.titulo AS processo_titulo
                FROM pericias p JOIN processos proc ON proc.id = p.processo_id
                WHERE p.tenant_id = {$this->db->getTenantId()}";
        if ($s = $req->q('status')) { $sql .= " AND p.status = '{$s}'"; }
        $sql .= " ORDER BY p.data_pericia DESC";
        return Response::paginated($this->db->paginate($sql, [], max(1, (int)$req->q('page', 1))));
    }

    public function show(Request $req): Response
    {
        $p = $this->db->first("SELECT * FROM pericias WHERE id = ? AND tenant_id = ?", [(int)$req->param('id'), $this->db->getTenantId()]);
        if (!$p) throw new \RuntimeException('Perícia não encontrada.', 404);
        return Response::success($p);
    }

    public function store(Request $req): Response
    {
        $req->validate(['processo_id' => 'required|numeric']);
        $allowed = ['processo_id','tipo','status','data_pericia','local_realizacao',
                    'perito_oficial_id','assistente_id','observacoes'];
        $data = array_intersect_key($req->body, array_flip($allowed));
        $data['tenant_id'] = $this->db->getTenantId();
        // Mapear tipo do frontend para ENUM do banco
        $tipoPerMap = ['nr15_calor'=>'judicial_assistencia','nr15_ruido'=>'judicial_assistencia',
                       'nr15_quimicos'=>'judicial_assistencia','contabil'=>'judicial_oficial',
                       'engenharia'=>'judicial_oficial','medica'=>'judicial_oficial',
                       'outra'=>'extrajudicial','extrajudicial'=>'extrajudicial',
                       'arbitragem'=>'arbitragem','judicial_oficial'=>'judicial_oficial',
                       'judicial_assistencia'=>'judicial_assistencia'];
        $data['tipo'] = $tipoPerMap[$data['tipo'] ?? ''] ?? 'extrajudicial';
        $data['status']    = $data['status'] ?? 'agendado';
        if (empty($data['data_pericia'])) $data['data_pericia'] = date('Y-m-d');
        $id = (int) $this->db->insert('pericias', $data);
        return Response::created(['id' => $id]);
    }

    public function update(Request $req): Response
    {
        $data = $req->body;
        unset($data['id'], $data['tenant_id']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('pericias', $data, ['id' => (int)$req->param('id'), 'tenant_id' => $this->db->getTenantId()]);
        return Response::success(null, 'Perícia atualizada.');
    }

    public function laudos(Request $req): Response
    {
        $data = $this->db->all("SELECT * FROM laudos WHERE pericia_id = ? AND tenant_id = ? ORDER BY versao DESC", [(int)$req->param('id'), $this->db->getTenantId()]);
        return Response::success($data);
    }

    public function addLaudo(Request $req): Response
    {
        $req->validate(['tipo' => 'required', 'titulo' => 'required']);
        $id = (int) $this->db->insert('laudos', array_merge($req->body, [
            'tenant_id'  => $this->db->getTenantId(),
            'pericia_id' => (int) $req->param('id'),
            'autor_id'   => (int) $req->user['id'],
        ]));
        return Response::created(['id' => $id]);
    }

    public function checklist(Request $req): Response
    {
        $periciaId = (int) $req->param('id');
        $laudoAdverso = $this->db->first(
            "SELECT id FROM laudos WHERE pericia_id = ? AND tipo = 'laudo_adverso' ORDER BY versao DESC LIMIT 1", [$periciaId]
        );
        if (!$laudoAdverso) return Response::success([]);
        $items = $this->db->all(
            "SELECT * FROM parecer_divergente_checklist WHERE laudo_id = ? ORDER BY item_ordem", [$laudoAdverso['id']]
        );
        return Response::success($items);
    }

    public function parecerDivergente(Request $req): Response
    {
        $req->validate(['processo_id' => 'required|numeric']);
        $procId      = $req->int('processo_id');
        $perOfi      = $req->str('perito_oficial');
        $diverg      = $req->str('divergencias');
        $conclusao   = $req->str('conclusao');
        $userId      = (int) $req->user['id'];

        // Buscar pericia_id
        $periciaId = $req->int('pericia_id') ?: null;
        if (!$periciaId) {
            $per = $this->db->first("SELECT id FROM pericias WHERE processo_id=? AND tenant_id=? LIMIT 1", [$procId, $this->db->getTenantId()]);
            $periciaId = $per ? $per['id'] : null;
        }
        if (!$periciaId) throw new \RuntimeException('Nenhuma perícia encontrada para este processo.', 422);

        // Salvar laudo no banco
        $id = (int) $this->db->insert('laudos', [
            'tenant_id'     => $this->db->getTenantId(),
            'processo_id'   => $procId,
            'pericia_id'    => $periciaId,
            'tipo'          => 'parecer_divergente',
            'titulo'        => 'Parecer Divergente — ' . date('d/m/Y'),
            'conclusao'     => $conclusao,
            'autor_id'      => $userId,
            'status'        => 'rascunho',
            'conteudo_json' => json_encode(['perito_oficial' => $perOfi, 'divergencias' => $diverg, 'conclusao' => $conclusao]),
        ]);

        // Gerar PDF e salvar no GED
        $html = $this->buildParecerHtml($perOfi, $diverg, $conclusao);
        $storage = new StorageManager($this->db);
        $engine  = new DocTemplateEngine($this->db, $storage);
        $doc = $engine->gerarPdfHtml($html, 'Parecer-Divergente-' . date('Ymd') . '.pdf', $procId, $userId);

        return Response::created(['id' => $id, 'documento_id' => $doc['id']], 'Parecer gerado e salvo no GED.');
    }

    private function buildParecerHtml(string $perOfi, string $diverg, string $conclusao): string
    {
        $data = date('d/m/Y');
        return "
        <h2 style='text-align:center;color:#1e3a5f;margin-bottom:20px'>PARECER TÉCNICO DIVERGENTE</h2>
        <p style='text-align:center;color:#666;margin-bottom:30px'>Gerado em {$data}</p>
        <hr>
        <h3 style='color:#1e3a5f'>I — PERITO OFICIAL</h3>
        <p>" . nl2br(htmlspecialchars($perOfi)) . "</p>
        <h3 style='color:#1e3a5f'>II — PRINCIPAIS DIVERGÊNCIAS</h3>
        <p>" . nl2br(htmlspecialchars($diverg)) . "</p>
        <h3 style='color:#1e3a5f'>III — CONCLUSÃO</h3>
        <p>" . nl2br(htmlspecialchars($conclusao)) . "</p>
        <br><br>
        <p style='text-align:center'>________________________________</p>
        <p style='text-align:center'>Assistente Técnico</p>
        ";
    }

    public function gerarParecer(Request $req): Response
    {
        $req->validate(['template_id' => 'required|numeric']);
        $periciaId = (int) $req->param('id');
        $pericia   = $this->db->first("SELECT * FROM pericias WHERE id = ? AND tenant_id = ?", [$periciaId, $this->db->getTenantId()]);
        if (!$pericia) throw new \RuntimeException('Perícia não encontrada.', 404);

        $checklist = $this->db->all(
            "SELECT c.* FROM parecer_divergente_checklist c
             JOIN laudos l ON l.id = c.laudo_id
             WHERE l.pericia_id = ? AND c.marcado = 1 ORDER BY c.item_ordem", [$periciaId]
        );

        $storage = new StorageManager($this->db);
        $engine  = new DocTemplateEngine($this->db, $storage);
        $result  = $engine->render(
            $req->int('template_id'),
            array_merge($req->body, ['divergencias' => $checklist]),
            $pericia['processo_id'],
            (int) $req->user['id']
        );

        return Response::created($result, 'Parecer gerado com sucesso.');
    }
}
