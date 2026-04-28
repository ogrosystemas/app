<?php
declare(strict_types=1);
// ============================================================
// StakeholderController
// ============================================================
final class StakeholderController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $tid  = $this->db->getTenantId();
        $tipo = $req->q('tipo', '');
        $q    = $req->q('q', '');
        $sql  = "SELECT * FROM stakeholders WHERE tenant_id = {$tid} AND deleted_at IS NULL";
        $par  = [];
        if ($tipo) { $sql .= " AND tipo = ?"; $par[] = $tipo; }
        if ($q)    { $sql .= " AND (nome LIKE ? OR email LIKE ? OR cpf_cnpj LIKE ?)"; $b = "%{$q}%"; $par[] = $b; $par[] = $b; $par[] = $b; }
        $sql .= " ORDER BY nome";
        return Response::paginated($this->db->paginate($sql, $par, max(1,(int)$req->q('page',1))));
    }

    public function show(Request $req): Response
    {
        $s = $this->db->first("SELECT * FROM stakeholders WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL", [(int)$req->param('id'), $this->db->getTenantId()]);
        if (!$s) throw new \RuntimeException('Stakeholder não encontrado.', 404);
        return Response::success($s);
    }

    public function store(Request $req): Response
    {
        $req->validate(['nome' => 'required|min:2', 'tipo' => 'required']);
        $crm = new CRMService($this->db);
        $id  = $crm->upsertStakeholder(array_merge($req->body, ['tenant_id' => $this->db->getTenantId()]));
        return Response::created(['id' => $id]);
    }

    public function update(Request $req): Response
    {
        $data = $req->body; unset($data['id'], $data['tenant_id']);
        $this->db->update('stakeholders', $data, ['id' => (int)$req->param('id'), 'tenant_id' => $this->db->getTenantId()]);
        return Response::success(null, 'Atualizado.');
    }

    public function destroy(Request $req): Response
    {
        $this->db->softDelete('stakeholders', (int)$req->param('id'));
        return Response::success(null, 'Excluído.');
    }

    public function interacoes(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT i.*, u.nome AS usuario_nome FROM crm_interacoes i
             JOIN users u ON u.id = i.user_id
             WHERE i.stakeholder_id = ? AND i.tenant_id = ? ORDER BY i.data_interacao DESC",
            [(int)$req->param('id'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function addInteracao(Request $req): Response
    {
        $req->validate(['tipo' => 'required', 'titulo' => 'required', 'data_interacao' => 'required']);
        $crm = new CRMService($this->db);
        $id  = $crm->registrarInteracao(array_merge($req->body, [
            'tenant_id'      => $this->db->getTenantId(),
            'stakeholder_id' => (int) $req->param('id'),
            'user_id'        => (int) $req->user['id'],
        ]));
        return Response::created(['id' => $id]);
    }

    public function todasInteracoes(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $sql = "SELECT ci.*, s.nome AS stakeholder_nome FROM crm_interacoes ci
                JOIN stakeholders s ON s.id = ci.stakeholder_id
                WHERE ci.tenant_id = ? ORDER BY ci.data_interacao DESC";
        return Response::paginated($this->db->paginate($sql, [$tid], max(1,(int)$req->q('page',1)), 20));
    }

    public function addInteracaoGlobal(Request $req): Response
    {
        $req->validate(['stakeholder_id' => 'required', 'assunto' => 'required']);
        $id = $this->db->insert('crm_interacoes', array_merge($req->body, [
            'tenant_id'  => $this->db->getTenantId(),
            'user_id'    => $req->user['id'],
            'data_interacao' => $req->body['data_interacao'] ?? date('Y-m-d H:i:s'),
        ]));
        // Atualiza ultimo_contato do stakeholder
        $this->db->update('stakeholders', ['ultimo_contato' => date('Y-m-d H:i:s')],
            ['id' => (int)$req->body['stakeholder_id']]);
        return Response::created(['id' => $id]);
    }

    public function agendarFollowup(Request $req): Response
    {
        $req->validate(['data' => 'required']);
        $crm = new CRMService($this->db);
        $id  = $crm->agendarFollowup((int)$req->param('id'), (int)$req->user['id'], $req->str('data'), $req->str('mensagem'));
        return Response::created(['id' => $id]);
    }

    public function dashboard(Request $req): Response
    {
        $crm = new CRMService($this->db);
        return Response::success($crm->dashboard($this->db->getTenantId()));
    }

    public function aniversariantes(Request $req): Response
    {
        $crm = new CRMService($this->db);
        return Response::success($crm->getAniversariantes($this->db->getTenantId(), (int)$req->q('dias', 7)));
    }
}

// ============================================================
// DespesaController
// ============================================================
final class DespesaController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $sql = "SELECT d.*, u.nome AS usuario_nome FROM despesas d JOIN users u ON u.id = d.user_id WHERE d.tenant_id = {$tid}";
        $par = [];
        if ($s = $req->q('status')) { $sql .= " AND d.status = ?"; $par[] = $s; }
        if ($p = $req->q('processo_id')) { $sql .= " AND d.processo_id = ?"; $par[] = $p; }
        // silo financeiro
        if (($req->user['perfil'] ?? '') === 'socio') { $sql .= " AND d.owner_id = ?"; $par[] = $req->user['owner_id'] ?? $req->user['id']; }
        $sql .= " ORDER BY d.data_despesa DESC";
        return Response::paginated($this->db->paginate($sql, $par, max(1,(int)$req->q('page',1))));
    }

    public function store(Request $req): Response
    {
        $req->validate(['categoria' => 'required', 'data_despesa' => 'required']);
        $storage = new StorageManager($this->db);
        $mgr     = new ExpenseManager($this->db, $storage);
        $data    = array_merge($req->body, ['user_id' => $req->user['id'], 'owner_id' => $req->user['owner_id'] ?? $req->user['id']]);
        $id      = $mgr->registrar($data, $_FILES['recibo'] ?? null, $req->str('recibo_base64') ?: null);
        return Response::created(['id' => $id]);
    }

    public function show(Request $req): Response
    {
        $d = $this->db->first("SELECT * FROM despesas WHERE id = ? AND tenant_id = ?", [(int)$req->param('id'), $this->db->getTenantId()]);
        if (!$d) throw new \RuntimeException('Despesa não encontrada.', 404);
        return Response::success($d);
    }

    public function update(Request $req): Response
    {
        $data = $req->body; unset($data['id'], $data['tenant_id'], $data['user_id']);
        $this->db->update('despesas', $data, ['id' => (int)$req->param('id'), 'tenant_id' => $this->db->getTenantId()]);
        return Response::success(null, 'Atualizado.');
    }

    public function aprovarUm(Request $req): Response
    {
        $id  = (int) $req->param('id');
        $tid = $this->db->getTenantId();
        $this->db->update('despesas', ['status' => 'aprovado', 'aprovado_por' => $req->user['id'], 'aprovado_em' => date('Y-m-d H:i:s')],
            ['id' => $id, 'tenant_id' => $tid]);
        return Response::success(null, 'Despesa aprovada.');
    }

    public function aprovarLote(Request $req): Response
    {
        $req->validate(['ids' => 'required']);
        $storage = new StorageManager($this->db);
        $mgr     = new ExpenseManager($this->db, $storage);
        $count   = $mgr->aprovarLote((array)$req->input('ids'), (int)$req->user['id']);
        return Response::success(['aprovadas' => $count]);
    }

    public function relatorio(Request $req): Response
    {
        $storage = new StorageManager($this->db);
        $mgr     = new ExpenseManager($this->db, $storage);
        return Response::success($mgr->relatorio((int)$req->param('processoId')));
    }
}

// ============================================================
// GEDController
// ============================================================
final class GEDController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function upload(Request $req): Response
    {
        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Nenhum arquivo enviado.', 400);
        }
        $storage = new StorageManager($this->db);
        $cat     = $req->str('categoria') ?: 'outros';
        $procId  = (int)($req->body['processo_id'] ?? 0);
        $extra   = ['publico_cliente' => !empty($req->body['publico_cliente'])];
        $doc     = $storage->upload($_FILES['arquivo'], $procId, $cat, (int)$req->user['id'], $extra);
        return Response::created($doc);
    }

    public function download(Request $req): never
    {
        $id  = (int) $req->param('id');
        $uid = (int) $req->q('uid', $req->user['id'] ?? 0);
        $exp = (int) $req->q('exp', 0);
        $sig = $req->q('sig', '');
        $storage = new StorageManager($this->db);
        if ($exp && $sig && !$storage->validateSignedUrl($id, $uid, $exp, $sig)) {
            throw new \RuntimeException('URL inválida ou expirada.', 403);
        }
        $storage->stream($id);
    }

    public function trash(Request $req): Response
    {
        (new StorageManager($this->db))->trash((int)$req->param('id'));
        return Response::success(null, 'Movido para lixeira.');
    }

    public function restore(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $doc = $this->db->first("SELECT * FROM documentos WHERE id=? AND tenant_id=?", [(int)$req->param('id'), $tid]);
        if (!$doc) throw new \RuntimeException('Documento não encontrado.', 404);
        $this->db->update('documentos', ['deleted_at' => null], ['id' => (int)$req->param('id')]);
        return Response::success(null, 'Documento restaurado.');
    }

    public function lixeira(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $docs = $this->db->all(
            "SELECT d.*, p.numero_interno FROM documentos d
             LEFT JOIN processos p ON p.id = d.processo_id
             WHERE d.tenant_id=? AND d.deleted_at IS NOT NULL
             ORDER BY d.deleted_at DESC LIMIT 200",
            [$tid]
        );
        foreach ($docs as &$d) {
            $deletedAt = new \DateTime($d['deleted_at']);
            $purgeAt   = (clone $deletedAt)->modify('+30 days');
            $now       = new \DateTime();
            $d['dias_restantes'] = max(0, (int)$now->diff($purgeAt)->days);
            $d['purge_at']       = $purgeAt->format('Y-m-d');
        }
        return Response::success($docs);
    }

    public function purge(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $id  = (int)$req->param('id');
        $doc = $this->db->first("SELECT * FROM documentos WHERE id=? AND tenant_id=? AND deleted_at IS NOT NULL", [$id, $tid]);
        if (!$doc) throw new \RuntimeException('Documento não encontrado na lixeira.', 404);
        // Remover arquivo físico
        if (!empty($doc['path'])) {
            $fullPath = THEMIS_ROOT . '/_storage/' . $doc['path'];
            if (file_exists($fullPath)) @unlink($fullPath);
        }
        $this->db->run("DELETE FROM documentos WHERE id=? AND tenant_id=?", [$id, $tid]);
        return Response::success(null, 'Excluído permanentemente.');
    }

    public function esvaziarLixeira(Request $req): Response
    {
        $tid  = $this->db->getTenantId();
        $docs = $this->db->all("SELECT * FROM documentos WHERE tenant_id=? AND deleted_at IS NOT NULL", [$tid]);
        foreach ($docs as $doc) {
            if (!empty($doc['path'])) {
                $fullPath = THEMIS_ROOT . '/_storage/' . $doc['path'];
                if (file_exists($fullPath)) @unlink($fullPath);
            }
        }
        $count = count($docs);
        $this->db->run("DELETE FROM documentos WHERE tenant_id=? AND deleted_at IS NOT NULL", [$tid]);
        return Response::success(['removidos' => $count], "Lixeira esvaziada. {$count} documento(s) removidos.");
    }

    public function purgeLote(Request $req): Response
    {
        $tid  = $this->db->getTenantId();
        $body = $req->all();
        $ids  = array_filter(array_map('intval', $body['ids'] ?? []));
        if (empty($ids)) throw new \RuntimeException('Nenhum ID informado.', 400);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $docs = $this->db->all(
            "SELECT * FROM documentos WHERE id IN ({$placeholders}) AND tenant_id=? AND deleted_at IS NOT NULL",
            [...$ids, $tid]
        );
        foreach ($docs as $doc) {
            if (!empty($doc['path'])) {
                $fullPath = THEMIS_ROOT . '/_storage/' . $doc['path'];
                if (file_exists($fullPath)) @unlink($fullPath);
            }
        }
        $this->db->run(
            "DELETE FROM documentos WHERE id IN ({$placeholders}) AND tenant_id=? AND deleted_at IS NOT NULL",
            [...$ids, $tid]
        );
        return Response::success(null, count($docs) . ' documento(s) excluídos permanentemente.');
    }

    public function restaurarLote(Request $req): Response
    {
        $tid  = $this->db->getTenantId();
        $body = $req->all();
        $ids  = array_filter(array_map('intval', $body['ids'] ?? []));
        if (empty($ids)) throw new \RuntimeException('Nenhum ID informado.', 400);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->run(
            "UPDATE documentos SET deleted_at=NULL WHERE id IN ({$placeholders}) AND tenant_id=?",
            [...$ids, $tid]
        );
        return Response::success(null, count($ids) . ' documento(s) restaurados.');
    }

    public function restaurar(Request $req): Response
    {
        (new StorageManager($this->db))->restore((int)$req->param('id'));
        return Response::success(null, 'Restaurado.');
    }
    public function indexAlias(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $sql = "SELECT d.*, p.numero_interno FROM documentos d
                LEFT JOIN processos p ON p.id = d.processo_id
                WHERE d.tenant_id = ? AND d.deleted_at IS NULL";
        $par = [$tid];
        if ($cat = $req->q('categoria')) { $sql .= " AND d.categoria = ?"; $par[] = $cat; }
        if ($pid = $req->q('processo_id')) { $sql .= " AND d.processo_id = ?"; $par[] = $pid; }
        $sql .= " ORDER BY d.created_at DESC";
        return Response::paginated($this->db->paginate($sql, $par, max(1,(int)$req->q('page',1)), 20));
    }

    public function uploadAlias(Request $req): Response
    {
        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
            // Tenta via body JSON (base64)
            $b64    = $req->str('conteudo_base64');
            $nome   = $req->str('nome') ?: 'documento.pdf';
            $cat    = $req->str('categoria') ?: 'outros';
            $procId = (int)($req->body['processo_id'] ?? 0);
            if (!$b64) throw new \RuntimeException('Nenhum arquivo enviado.', 400);
            $storage = new StorageManager($this->db);
            $doc = $storage->uploadBase64($b64, $nome, $procId, $cat, (int)$req->user['id']);
            return Response::created($doc);
        }
        // Suporte a múltiplos arquivos
        $storage = new StorageManager($this->db);
        $cat     = $req->str('categoria') ?: 'outros';
        $procId  = (int)($req->body['processo_id'] ?? 0);
        $extra   = ['publico_cliente' => !empty($req->body['publico_cliente'])];
        // Verifica se veio array de arquivos
        $files = $_FILES['arquivo'];
        if (is_array($files['name'])) {
            $results = [];
            $count   = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $f = [
                    'name'     => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                    'type'     => $files['type'][$i],
                ];
                $results[] = $storage->upload($f, $procId, $cat, (int)$req->user['id'], $extra);
            }
            return Response::created(count($results) === 1 ? $results[0] : $results);
        }
        $doc = $storage->upload($_FILES['arquivo'], $procId, $cat, (int)$req->user['id'], $extra);
        return Response::created($doc);
    }

    public function exportarPdf(Request $req): Response
    {
        $id  = (int) $req->param('id');
        $tid = $this->db->getTenantId();
        $doc = $this->db->first("SELECT * FROM documentos WHERE id=? AND tenant_id=?", [$id, $tid]);
        if (!$doc) throw new \RuntimeException('Documento não encontrado.', 404);
        // Retorna URL assinada para download
        $storage = new StorageManager($this->db);
        $url = $storage->signedUrl($id, (int)$req->user['id']);
        return Response::success(['download_url' => $url, 'nome' => $doc['nome_original'] ?? $doc['nome']]);
    }

    public function enviarAssinatura(Request $req): Response
    {
        $id  = (int) $req->param('id');
        $tid = $this->db->getTenantId();
        $doc = $this->db->first("SELECT * FROM documentos WHERE id=? AND tenant_id=?", [$id, $tid]);
        if (!$doc) throw new \RuntimeException('Documento não encontrado.', 404);
        $signatarios = $req->input('signatarios', []);
        if (empty($signatarios)) {
            throw new \RuntimeException('Informe pelo menos um signatário (nome e e-mail).', 400);
        }
        $storage = new StorageManager($this->db);
        $engine  = new DocTemplateEngine($this->db, $storage);
        $result  = $engine->enviarAssinatura($id, $signatarios);
        return Response::success($result, 'Documento enviado para assinatura na Assinafy!');
    }


    
}

// ============================================================
// FinanceiroController (silo por owner_id)
// ============================================================
final class FinanceiroController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    private function ownerId(Request $req): int
    {
        if ($req->user['perfil'] === 'admin') return (int)$req->q('owner_id', 0);
        return (int)($req->user['owner_id'] ?? $req->user['id']);
    }

    public function receitas(Request $req): Response
    {
        $oid = $this->ownerId($req);
        $sql = "SELECT r.*, s.nome AS cliente_nome FROM financeiro_receitas r
                LEFT JOIN stakeholders s ON s.id = r.cliente_id
                WHERE r.tenant_id = {$this->db->getTenantId()}" . ($oid ? " AND r.owner_id = {$oid}" : '');
        $sql .= " ORDER BY r.data_prevista DESC";
        return Response::paginated($this->db->paginate($sql, [], max(1,(int)$req->q('page',1))));
    }

    public function addReceita(Request $req): Response
    {
        $req->validate(['descricao' => 'required', 'valor_previsto' => 'required|numeric', 'tipo' => 'required']);
        $id = (int) $this->db->insert('financeiro_receitas', array_merge($req->body, [
            'tenant_id' => $this->db->getTenantId(),
            'owner_id'  => $this->ownerId($req),
        ]));
        return Response::created(['id' => $id]);
    }

    public function addPagamento(Request $req): Response
    {
        $req->validate(['descricao' => 'required', 'valor' => 'required|numeric', 'data_pagamento' => 'required']);
        $id = (int) $this->db->insert('financeiro_pagamentos', array_merge($req->body, [
            'tenant_id' => $this->db->getTenantId(),
            'owner_id'  => $this->ownerId($req),
        ]));
        // Atualiza valor recebido na receita
        if ($recId = $req->int('receita_id')) {
            $rec = $this->db->first("SELECT valor_previsto, valor_recebido FROM financeiro_receitas WHERE id = ?", [$recId]);
            if ($rec) {
                $recebido = (float)$rec['valor_recebido'] + (float)$req->input('valor');
                $status   = $recebido >= (float)$rec['valor_previsto'] ? 'recebido' : 'parcial';
                $this->db->update('financeiro_receitas', ['valor_recebido' => $recebido, 'status' => $status, 'data_recebimento' => $req->str('data_pagamento')], ['id' => $recId]);
            }
        }
        return Response::created(['id' => $id]);
    }

    public function dashboard(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $oid = $this->ownerId($req);
        $cond = $oid ? "AND r.owner_id = {$oid}" : '';
        return Response::success([
            'previsto_mes'    => (float) $this->db->scalar("SELECT COALESCE(SUM(valor_previsto),0) FROM financeiro_receitas WHERE tenant_id=? AND MONTH(data_prevista)=MONTH(NOW()) AND YEAR(data_prevista)=YEAR(NOW()) {$cond}", [$tid]),
            'recebido_mes'    => (float) $this->db->scalar("SELECT COALESCE(SUM(valor_recebido),0) FROM financeiro_receitas WHERE tenant_id=? AND MONTH(data_recebimento)=MONTH(NOW()) AND YEAR(data_recebimento)=YEAR(NOW()) {$cond}", [$tid]),
            'inadimplente'    => (float) $this->db->scalar("SELECT COALESCE(SUM(valor_previsto-valor_recebido),0) FROM financeiro_receitas WHERE tenant_id=? AND status='inadimplente' {$cond}", [$tid]),
            'alvaras_pendentes'=> (int) $this->db->scalar("SELECT COUNT(*) FROM alvaras_monitoramento WHERE tenant_id=? AND status='aguardando'", [$tid]),
        ]);
    }

    public function kpis(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        return Response::success([
            'processos_ativos'   => (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id=? AND status='ativo' AND deleted_at IS NULL", [$tid]),
            'taxa_exito'         => $this->calcTaxaExito($tid),
            'ciclo_medio_dias'   => $this->calcCicloMedio($tid),
            'honorarios_ano'       => (float) $this->db->scalar("SELECT COALESCE(SUM(valor_recebido),0) FROM financeiro_receitas WHERE tenant_id=? AND YEAR(data_recebimento)=YEAR(NOW())", [$tid]),
            'honorarios_mes'       => (float) $this->db->scalar("SELECT COALESCE(SUM(valor_recebido),0) FROM financeiro_receitas WHERE tenant_id=? AND MONTH(data_recebimento)=MONTH(NOW()) AND YEAR(data_recebimento)=YEAR(NOW())", [$tid]),
            'honorarios_recebido'  => (float) $this->db->scalar("SELECT COALESCE(SUM(valor_recebido),0) FROM financeiro_receitas WHERE tenant_id=? AND MONTH(data_recebimento)=MONTH(NOW()) AND YEAR(data_recebimento)=YEAR(NOW())", [$tid]),
            'despesas_pendentes'   => (float) $this->db->scalar("SELECT COALESCE(SUM(valor),0) FROM despesas WHERE tenant_id=? AND status='pendente'", [$tid]),
            'prazos_criticos'      => (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id=? AND prazo_fatal BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) AND deleted_at IS NULL", [$tid]),
            'alvaras_expedidos'    => (int) $this->db->scalar("SELECT COUNT(*) FROM alvaras_monitoramento WHERE tenant_id=? AND status='expedido'", [$tid]),
            'processos_recurso'    => (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id=? AND status='aguardando_decisao' AND deleted_at IS NULL", [$tid]),
            'processos_execucao'   => (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id=? AND status='execucao' AND deleted_at IS NULL", [$tid]),
            'meses_labels'         => array_map(fn($i)=>(new \DateTime())->modify("-{$i} months")->format('M/y'), range(5,0,-1)),
            'honorarios_summary'   => ['previsto'=>0.0,'recebido'=>0.0,'pendente'=>0.0],
        ]);
    }

    public function alvaras(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT a.*, p.numero_interno, p.titulo AS processo_titulo FROM alvaras_monitoramento a
             JOIN processos p ON p.id = a.processo_id
             WHERE a.tenant_id = ? ORDER BY a.created_at DESC",
            [$this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function updateAlvara(Request $req): Response
    {
        $data = $req->body; unset($data['id'], $data['tenant_id']);
        $this->db->update('alvaras_monitoramento', $data, ['id' => (int)$req->param('id'), 'tenant_id' => $this->db->getTenantId()]);
        return Response::success(null, 'Alvará atualizado.');
    }

    private function calcTaxaExito(int $tid): float
    {
        $enc   = (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id=? AND status='encerrado' AND deleted_at IS NULL", [$tid]);
        $favor = (int) $this->db->scalar("SELECT COUNT(*) FROM processos WHERE tenant_id=? AND status='encerrado' AND probabilidade_exito >= 50 AND deleted_at IS NULL", [$tid]);
        return $enc > 0 ? round($favor / $enc * 100, 1) : 0.0;
    }

    private function calcCicloMedio(int $tid): float
    {
        return (float) $this->db->scalar(
            "SELECT COALESCE(AVG(DATEDIFF(data_encerramento, data_distribuicao)),0) FROM processos WHERE tenant_id=? AND status='encerrado' AND data_encerramento IS NOT NULL AND data_distribuicao IS NOT NULL",
            [$tid]
        );
    }
}

// ============================================================
// WebhookController
// ============================================================
final class WebhookController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function assinafy(Request $req): Response
    {
        $sig = $_SERVER['HTTP_X_ASSINAFY_SIGNATURE'] ?? null;
        $wh  = new WebhookHandler($this->db);
        $id  = $wh->receive('assinafy', $req->body, $sig);
        return Response::success(['event_id' => $id]);
    }

    public function whatsapp(Request $req): Response
    {
        // Verificação do webhook Meta
        if ($req->method === 'GET') {
            $challenge = $req->q('hub_challenge', '');
            $verify    = $req->q('hub_verify_token', '');
            if ($verify !== ($_ENV['WHATSAPP_VERIFY_TOKEN'] ?? '')) throw new \RuntimeException('Verify token inválido.', 403);
            header('Content-Type: text/plain'); echo $challenge; exit;
        }
        $wh = new WebhookHandler($this->db);
        $id = $wh->receive('whatsapp', $req->body);
        return Response::success(['event_id' => $id]);
    }

    public function datajud(Request $req): Response
    {
        $wh = new WebhookHandler($this->db);
        $id = $wh->receive('datajud', $req->body);
        return Response::success(['event_id' => $id]);
    }
}

// ============================================================
// NotificacaoController
// ============================================================
final class NotificacaoController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $uid  = (int) $req->user['id'];
        $lida = $req->q('lida');
        $sql  = "SELECT * FROM notificacoes WHERE user_id = ?";
        $par  = [$uid];
        if ($lida !== null) { $sql .= " AND lida = ?"; $par[] = (int)$lida; }
        $sql .= " ORDER BY created_at DESC";
        return Response::paginated($this->db->paginate($sql, $par, max(1,(int)$req->q('page',1)), 30));
    }

    public function marcarLida(Request $req): Response
    {
        $this->db->update('notificacoes', ['lida' => 1, 'lida_em' => date('Y-m-d H:i:s')], ['id' => (int)$req->param('id'), 'user_id' => (int)$req->user['id']]);
        return Response::success(null, 'Marcada como lida.');
    }

    public function marcarTodas(Request $req): Response
    {
        $this->db->run("UPDATE notificacoes SET lida=1, lida_em=NOW() WHERE user_id=? AND lida=0", [(int)$req->user['id']]);
        return Response::success(null, 'Todas marcadas como lidas.');
    }
}

// ============================================================
// AgendaController
// ============================================================
final class AgendaController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $uid = (int) $req->user['id'];
        $ini = $req->q('inicio', date('Y-m-01'));
        $fim = $req->q('fim',    date('Y-m-t'));
        $data = $this->db->all(
            "SELECT * FROM agenda_eventos WHERE tenant_id = ?
             AND inicio >= ? AND inicio <= ? AND JSON_CONTAINS(user_ids, ?) ORDER BY inicio",
            [$this->db->getTenantId(), $ini, $fim, "\"{$uid}\""]
        );
        return Response::success($data);
    }

    public function hoje(Request $req): Response
    {
        $uid = (int) $req->user['id'];
        $tid = $this->db->getTenantId();
        $ini = date('Y-m-d 00:00:00');
        $fim = date('Y-m-d 23:59:59');
        $data = $this->db->all(
            "SELECT ae.*, p.numero_interno AS processo_numero
             FROM agenda_eventos ae
             LEFT JOIN processos p ON p.id = ae.processo_id
             WHERE ae.tenant_id = ? AND ae.inicio >= ? AND ae.inicio <= ?
             ORDER BY ae.inicio",
            [$tid, $ini, $fim]
        );
        return Response::success($data);
    }

    public function porMes(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $mes = $req->q('mes', date('Y-m'));
        $ini = $mes . '-01 00:00:00';
        $fim = date('Y-m-t 23:59:59', strtotime($ini));
        $data = $this->db->all(
            "SELECT ae.id, ae.titulo, ae.tipo, ae.inicio, ae.fim, ae.processo_id,
                    p.numero_interno AS processo_numero
             FROM agenda_eventos ae
             LEFT JOIN processos p ON p.id = ae.processo_id
             WHERE ae.tenant_id = ? AND ae.inicio >= ? AND ae.inicio <= ?
             ORDER BY ae.inicio",
            [$tid, $ini, $fim]
        );
        return Response::success($data);
    }

    public function store(Request $req): Response
    {
        $req->validate(['titulo' => 'required', 'tipo' => 'required', 'inicio' => 'required']);
        $d = array_merge($req->body, [
            'tenant_id'  => $this->db->getTenantId(),
            'created_by' => (int) $req->user['id'],
            'user_ids'   => json_encode($req->input('user_ids', [(int)$req->user['id']])),
        ]);
        $id = (int) $this->db->insert('agenda_eventos', $d);
        return Response::created(['id' => $id]);
    }

    public function update(Request $req): Response
    {
        $data = $req->body; unset($data['id'], $data['tenant_id']);
        $this->db->update('agenda_eventos', $data, ['id' => (int)$req->param('id'), 'tenant_id' => $this->db->getTenantId()]);
        return Response::success(null, 'Atualizado.');
    }

    public function destroy(Request $req): Response
    {
        $this->db->run("DELETE FROM agenda_eventos WHERE id=? AND tenant_id=?", [(int)$req->param('id'), $this->db->getTenantId()]);
        return Response::success(null, 'Excluído.');
    }
}

// ============================================================
// RadarController (DataJud / OAB)
// ============================================================
final class RadarController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function sync(Request $req): Response
    {
        $pid  = (int) $req->param('processoId');
        $proc = $this->db->first("SELECT numero_cnj FROM processos WHERE id = ? AND tenant_id = ?", [$pid, $this->db->getTenantId()]);
        if (!$proc || !$proc['numero_cnj']) throw new \RuntimeException('Processo sem número CNJ.', 400);
        $svc  = new DataJudService($this->db);
        $data = $svc->consultarProcesso($proc['numero_cnj'], $pid);
        return Response::success(['movimentos' => count($data['movimentos'] ?? [])]);
    }

    public function movimentosRecentes(Request $req): Response
    {
        $tid   = $this->db->getTenantId();
        $limit = min((int)$req->q('limit', 10), 50);
        $movs  = $this->db->all(
            "SELECT m.*, p.numero_interno AS numero_processo
             FROM processo_andamentos m
             JOIN processos p ON p.id = m.processo_id
             WHERE m.tenant_id = ?
             ORDER BY m.data_andamento DESC LIMIT ?",
            [$tid, $limit]
        );
        return Response::success($movs);
    }

    public function movimentos(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT * FROM datajud_movimentos WHERE processo_id=? AND tenant_id=? ORDER BY data_movimento DESC",
            [(int)$req->param('processoId'), $this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function alvaras(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT a.*, p.numero_interno FROM alvaras_monitoramento a JOIN processos p ON p.id=a.processo_id WHERE a.tenant_id=? ORDER BY a.created_at DESC",
            [$this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function processosParados(Request $req): Response
    {
        $data = $this->db->all(
            "SELECT id, numero_interno, titulo, dias_parado, responsavel_id FROM processos WHERE tenant_id=? AND dias_parado >= 60 AND status NOT IN ('arquivado','encerrado') AND deleted_at IS NULL ORDER BY dias_parado DESC",
            [$this->db->getTenantId()]
        );
        return Response::success($data);
    }

    public function monitorarTodos(Request $req): Response
    {
        $svc    = new DataJudService($this->db);
        $result = $svc->monitorarTodos($this->db->getTenantId());
        return Response::success($result);
    }

    public function importarLote(Request $req): Response
    {
        $body = $req->all();
        $cnjs = $body['cnjs'] ?? [];
        if (empty($cnjs)) throw new \RuntimeException('Nenhum número CNJ informado.', 400);

        $svc        = new DataJudService($this->db);
        $tid        = $this->db->getTenantId();
        $importados = 0;
        $duplicados = 0;
        $nao_encontrados = 0;

        foreach ($cnjs as $cnj) {
            $cnj = trim($cnj);
            if (!preg_match('/\d{7}-\d{2}\.\d{4}\.\d\.\d{2}\.\d{4}/', $cnj)) continue;

            // Verificar se já existe
            $existe = $this->db->first(
                "SELECT id FROM processos WHERE numero_cnj = ? AND tenant_id = ?",
                [$cnj, $tid]
            );
            if ($existe) { $duplicados++; continue; }

            // Buscar no DataJud
            $proc_id = $this->db->insert('processos', [
                'tenant_id'          => $tid,
                'responsavel_id'     => $req->user['id'],
                'cliente_id'         => null,
                'numero_cnj'         => $cnj,
                'numero_interno'     => 'DJ-' . substr(preg_replace('/\D/', '', $cnj), 0, 7),
                'titulo'             => 'Processo ' . $cnj,
                'tipo'               => 'civel',
                'polo'               => 'ativo',
                'status'             => 'ativo',
                'datajud_monitorado' => 1,
            ]);

            try {
                $data = $svc->consultarProcesso($cnj, $proc_id);
                if (!empty($data)) {
                    // Atualizar título com dados reais
                    $titulo = $data['classeProcessual']['nome'] ?? $data['classe']['nome'] ?? null;
                    $vara   = $data['orgaoJulgador']['nome'] ?? null;
                    if ($titulo) $this->db->run(
                        "UPDATE processos SET titulo=?, vara=? WHERE id=?",
                        [$titulo, $vara, $proc_id]
                    );
                    $importados++;
                } else {
                    // Processo não encontrado no DataJud — manter cadastrado mesmo assim
                    $importados++;
                    $nao_encontrados++;
                }
            } catch (\Throwable $e) {
                $importados++;
                error_log("[DataJud][Lote] {$cnj}: " . $e->getMessage());
            }
            usleep(200_000); // 200ms entre requests
        }

        return Response::success(
            ['importados' => $importados, 'duplicados' => $duplicados, 'nao_encontrados' => $nao_encontrados],
            "{$importados} processo(s) importados."
        );
    }

    public function buscarOAB(Request $req): Response
    {
        $body   = $req->all();
        $oab    = preg_replace('/\D/', '', $body['oab_numero'] ?? '');
        $uf     = strtoupper(trim($body['oab_uf'] ?? ''));
        if (!$oab) throw new \RuntimeException('Número OAB obrigatório.', 400);
        $svc    = new DataJudService($this->db);
        $result = $svc->buscarPorOAB($oab, $uf, $this->db->getTenantId(), $req->user);
        return Response::success($result, "Busca concluída: {$result['encontrados']} encontrados, {$result['importados']} importados.");
    }
}

// ============================================================
// PortalController (perfil=cliente)
// ============================================================
final class PortalController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    // ── Login via CPF + Token ─────────────────────────────────────
    public function login(Request $req): Response
    {
        $cpf   = preg_replace('/\D/', '', $req->str('cpf'));
        $token = trim($req->str('token'));

        if (!$cpf || !$token) throw new \InvalidArgumentException('CPF e token são obrigatórios.', 422);

        // Localizar o tenant pelo token (token é único globalmente)
        $row = $this->db->first(
            "SELECT pt.*, s.nome, s.id AS stakeholder_id, s.email,
                    p.tenant_id AS pid_tenant
             FROM portal_tokens pt
             JOIN stakeholders s ON s.id = pt.stakeholder_id
             JOIN tenants p ON p.id = pt.tenant_id
             WHERE pt.token = ? AND pt.expires_at > NOW()
             LIMIT 1",
            [$token]
        );

        if (!$row) throw new \RuntimeException('Token inválido ou expirado.', 401);

        // Validar CPF
        $cpfCadastrado = preg_replace('/\D/', '', $row['cpf']);
        if ($cpf !== $cpfCadastrado) throw new \RuntimeException('CPF não confere.', 401);

        // Registrar uso
        $this->db->update('portal_tokens', ['used_at' => date('Y-m-d H:i:s')], ['id' => $row['id']]);

        // Gerar JWT para o portal (perfil=cliente, sem acesso ao sistema interno)
        $auth   = new Auth();
        $jwtPayload = [
            'sub'            => 'portal_' . $row['stakeholder_id'],
            'stakeholder_id' => $row['stakeholder_id'],
            'tenant_id'      => $row['tenant_id'],
            'nome'           => $row['nome'],
            'cpf'            => $cpf,
            'perfil'         => 'cliente_portal',
            'token_id'       => $row['id'],
        ];
        $jwt = $auth->gerarPortalJwt($jwtPayload);

        return Response::success([
            'token'  => $jwt,
            'nome'   => $row['nome'],
            'perfil' => 'cliente_portal',
        ], 'Login realizado com sucesso!');
    }

    // ── Resolve stakeholder_id do cliente autenticado ─────────────
    private function clienteId(Request $req): int
    {
        // Suporte ao novo JWT de portal
        if (!empty($req->user['stakeholder_id'])) {
            return (int) $req->user['stakeholder_id'];
        }
        // Fallback legado por email
        return (int) $this->db->scalar(
            "SELECT id FROM stakeholders WHERE tenant_id=? AND email=? LIMIT 1",
            [$this->db->getTenantId(), $req->user['email'] ?? '']
        );
    }

    public function processos(Request $req): Response
    {
        $cid  = $this->clienteId($req);
        $data = $this->db->all(
            "SELECT p.id, p.numero_cnj, p.numero_interno, p.titulo, p.status, p.tipo, p.vara, p.tribunal,
             p.probabilidade_exito, p.prazo_fatal, p.updated_at, u.nome AS responsavel_nome
             FROM processos p JOIN users u ON u.id = p.responsavel_id
             WHERE p.tenant_id=? AND p.cliente_id=? AND p.deleted_at IS NULL ORDER BY p.prazo_fatal",
            [$this->db->getTenantId(), $cid]
        );
        return Response::success($data);
    }

    public function documentos(Request $req): Response
    {
        $cid = $this->clienteId($req);
        $data = $this->db->all(
            "SELECT d.id, d.nome_original, d.categoria, d.mime_type, d.tamanho_bytes, d.assinatura_status, d.created_at, p.numero_interno
             FROM documentos d JOIN processos p ON p.id = d.processo_id
             WHERE d.tenant_id=? AND p.cliente_id=? AND d.publico_cliente=1 AND d.deleted_at IS NULL ORDER BY d.created_at DESC",
            [$this->db->getTenantId(), $cid]
        );
        return Response::success($data);
    }

    public function prazos(Request $req): Response
    {
        $cid = $this->clienteId($req);
        $data = $this->db->all(
            "SELECT pr.titulo, pr.tipo, pr.data_prazo, pr.cumprido, p.numero_interno, p.id AS processo_id,
             DATEDIFF(pr.data_prazo, NOW()) AS dias_restantes
             FROM prazos pr JOIN processos p ON p.id = pr.processo_id
             WHERE pr.tenant_id=? AND p.cliente_id=? AND pr.cumprido=0 AND pr.data_prazo >= NOW() ORDER BY pr.data_prazo",
            [$this->db->getTenantId(), $cid]
        );
        return Response::success($data);
    }

    public function financeiro(Request $req): Response
    {
        $cid = $this->clienteId($req);
        $data = $this->db->all(
            "SELECT r.tipo, r.descricao, r.valor_previsto, r.valor_recebido, r.status, r.data_prevista, r.data_recebimento, p.numero_interno
             FROM financeiro_receitas r LEFT JOIN processos p ON p.id = r.processo_id
             WHERE r.tenant_id=? AND r.cliente_id=? ORDER BY r.data_prevista DESC",
            [$this->db->getTenantId(), $cid]
        );
        return Response::success($data);
    }

    public function mensagens(Request $req): Response
    {
        $cid = $this->clienteId($req);
        $data = $this->db->all(
            "SELECT m.*, u.nome AS usuario_nome FROM portal_mensagens m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.tenant_id=? AND m.cliente_id=? ORDER BY m.created_at",
            [$this->db->getTenantId(), $cid]
        );
        // Marca como lidas
        $this->db->run("UPDATE portal_mensagens SET lida=1, lida_em=NOW() WHERE tenant_id=? AND cliente_id=? AND remetente='escritorio' AND lida=0", [$this->db->getTenantId(), $cid]);
        return Response::success($data);
    }

    public function enviarMensagem(Request $req): Response
    {
        $req->validate(['mensagem' => 'required|min:1']);
        $cid = $this->clienteId($req);
        $id  = (int) $this->db->insert('portal_mensagens', [
            'tenant_id'   => $this->db->getTenantId(),
            'processo_id' => $req->int('processo_id') ?: null,
            'cliente_id'  => $cid,
            'user_id'     => (int) $req->user['id'],
            'remetente'   => 'cliente',
            'mensagem'    => $req->str('mensagem'),
        ]);
        return Response::created(['id' => $id]);
    }

    public function avaliar(Request $req): Response
    {
        $req->validate(['nota' => 'required|numeric']);
        $cid = $this->clienteId($req);
        $this->db->insert('portal_avaliacoes', [
            'tenant_id'   => $this->db->getTenantId(),
            'cliente_id'  => $cid,
            'processo_id' => $req->int('processo_id') ?: null,
            'nota'        => min(5, max(1, $req->int('nota'))),
            'comentario'  => $req->str('comentario') ?: null,
        ]);
        return Response::success(null, 'Avaliação registrada.');
    }
}

// ============================================================
// AuditController
// ============================================================
final class AuditController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $sql = "SELECT a.*, u.nome AS usuario_nome FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE a.tenant_id={$tid}";
        $par = [];
        if ($m = $req->q('modulo')) { $sql .= " AND a.modulo=?"; $par[] = $m; }
        if ($u = $req->q('user_id')) { $sql .= " AND a.user_id=?"; $par[] = $u; }
        $sql .= " ORDER BY a.created_at DESC";
        return Response::paginated($this->db->paginate($sql, $par, max(1,(int)$req->q('page',1))));
    }

    public function verify(Request $req): Response
    {
        $tid  = $this->db->getTenantId();
        $date = $req->q('date', date('Y-m-d'));
        $base = Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage') . '/logs';
        $file = $base . '/' . $tid . '/audit_' . $date . '.log';
        if (!file_exists($file)) {
            return Response::success(['file_exists' => false, 'invalid_lines' => []]);
        }
        $invalid = AuditLogger::verify($file);
        return Response::success([
            'file_exists'   => true,
            'date'          => $date,
            'invalid_lines' => $invalid,
            'integrity'     => empty($invalid) ? 'ok' : 'COMPROMETIDA',
        ]);
    }
}

// ============================================================
// TemplateController
// ============================================================
final class TemplateController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $data = $this->db->all("SELECT id,nome,tipo,subtipo,papel_timbrado,uso_count,ativo,created_at FROM doc_templates WHERE tenant_id=? ORDER BY tipo,nome", [$this->db->getTenantId()]);
        return Response::success($data);
    }

    public function show(Request $req): Response
    {
        $t = $this->db->first("SELECT * FROM doc_templates WHERE id=? AND tenant_id=?", [(int)$req->param('id'), $this->db->getTenantId()]);
        if (!$t) throw new \RuntimeException('Template não encontrado.', 404);
        return Response::success($t);
    }

    public function store(Request $req): Response
    {
        $req->validate(['nome' => 'required']);
        $allowed = ['nome','tipo','subtipo','conteudo_html','papel_timbrado','ativo','uso_count'];
        $data = array_intersect_key($req->body, array_flip($allowed));
        $data['tipo']          = $data['tipo'] ?? 'outro';
        $data['conteudo_html'] = $data['conteudo_html'] ?? '<p>Conteúdo do template.</p>';
        $data['tenant_id']     = $this->db->getTenantId();
        $data['created_by']    = (int)$req->user['id'];
        $id = (int) $this->db->insert('doc_templates', $data);
        return Response::created(['id' => $id]);
    }

    public function update(Request $req): Response
    {
        $allowed = ['nome','tipo','subtipo','conteudo_html','papel_timbrado','ativo'];
        $data = array_intersect_key($req->body, array_flip($allowed));
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('doc_templates', $data, ['id' => (int)$req->param('id'), 'tenant_id' => $this->db->getTenantId()]);
        return Response::success(null, 'Atualizado.');
    }

    public function gerar(Request $req): Response
    {
        $templateId = (int) $req->param('id');
        $body       = $req->body;

        // Extrair campos de controle (não são variáveis do template)
        $destino   = $body['destino']    ?? 'ged';
        $processoId = (int)($body['processo_id'] ?? 0) ?: null;
        $variaveis = is_array($body['variaveis'] ?? null) ? $body['variaveis'] : [];

        // Mesclar variáveis manuais com o body (excluindo campos de controle)
        $skip = ['destino', 'processo_id', 'variaveis'];
        foreach ($body as $k => $v) {
            if (!in_array($k, $skip) && is_scalar($v)) {
                $variaveis[$k] = $v;
            }
        }

        $storage = new StorageManager($this->db);
        $engine  = new DocTemplateEngine($this->db, $storage);
        $result  = $engine->render($templateId, $variaveis, $processoId, (int)$req->user['id']);

        // Destino: assinatura digital
        if ($destino === 'assinatura' && !empty($result['documento_id'])) {
            $usarProprioUsuario = (bool)($body['usar_proprio_usuario'] ?? false);
            $signatarios = $body['signatarios'] ?? [];
            if ($usarProprioUsuario || empty($signatarios)) {
                $userAtual = $this->db->first(
                    "SELECT nome, email, assinafy_email FROM users WHERE id = ? LIMIT 1",
                    [$req->user['id'] ?? 0]
                );
                $nomeUser  = $userAtual['nome']  ?? $req->user['nome']  ?? 'Responsável';
                $emailUser = $userAtual['assinafy_email'] ?? $userAtual['email'] ?? $req->user['email'] ?? '';
                error_log("[Themis] Auto-assinatura: nome={$nomeUser} email={$emailUser}");
                $signatarios = [['nome' => $nomeUser, 'email' => $emailUser]];
            }
            if (!empty($signatarios[0]['email'])) {
                error_log("[Themis] Enviando para Assinafy: " . json_encode($signatarios));
                try {
                    $assResp = $engine->enviarAssinatura((int)$result['documento_id'], $signatarios);
                    $result['assinatura_enviada'] = true;
                    $result['assinafy_doc_id']    = $assResp['id'] ?? null;
                    $result['assinafy_link']      = $assResp['url'] ?? $assResp['signing_url'] ?? null;
                    $result['signing_urls']        = $assResp['signing_urls'] ?? [];
                } catch (\Throwable $e) {
                    $result['assinatura_enviada'] = false;
                    $result['assinatura_erro']    = $e->getMessage();
                }
            } else {
                $result['assinatura_enviada'] = false;
                $result['assinatura_erro']    = 'Usuário sem e-mail cadastrado. Configure em Configurações → Perfil.';
            }
        }

        return Response::created($result, 'Documento gerado com sucesso!');
    }
}

// ═══════════════════════════════════════════════════════════════════
// PortalTokenController
// ═══════════════════════════════════════════════════════════════════
final class PortalTokenController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $tid  = $this->db->getTenantId();
        $data = $this->db->all(
            "SELECT pt.*, s.nome AS cliente_nome, s.cpf_cnpj, u.nome AS criado_por_nome
             FROM portal_tokens pt
             JOIN stakeholders s ON s.id = pt.stakeholder_id
             JOIN users u ON u.id = pt.criado_por
             WHERE pt.tenant_id = ?
             ORDER BY pt.created_at DESC",
            [$tid]
        );
        foreach ($data as &$row) {
            $row['expirado'] = $row['expires_at'] < date('Y-m-d H:i:s');
            $row['ativo']    = !$row['expirado'] && empty($row['used_at']);
        }
        return Response::success($data);
    }

    public function gerar(Request $req): Response
    {
        $req->validate(['stakeholder_id' => 'required|numeric']);
        $tid  = $this->db->getTenantId();
        $sid  = (int) $req->int('stakeholder_id');
        $dias = max(1, min(365, (int)($req->body['validade_dias'] ?? 30)));

        $stk = $this->db->first(
            "SELECT id, nome, cpf_cnpj FROM stakeholders WHERE id=? AND tenant_id=?",
            [$sid, $tid]
        );
        if (!$stk) throw new \RuntimeException('Cliente não encontrado.', 404);
        if (empty($stk['cpf_cnpj'])) throw new \RuntimeException('O cliente não tem CPF/CNPJ cadastrado.', 422);

        $this->db->run(
            "UPDATE portal_tokens SET expires_at = NOW() WHERE stakeholder_id = ? AND tenant_id = ? AND expires_at > NOW()",
            [$sid, $tid]
        );

        $token     = bin2hex(random_bytes(24));
        $expiresAt = (new \DateTime())->modify("+{$dias} days")->format('Y-m-d H:i:s');
        $cpfNorm   = preg_replace('/\D/', '', $stk['cpf_cnpj']);

        $id = $this->db->insert('portal_tokens', [
            'tenant_id'      => $tid,
            'stakeholder_id' => $sid,
            'cpf'            => $cpfNorm,
            'token'          => $token,
            'nome_cliente'   => $stk['nome'],
            'expires_at'     => $expiresAt,
            'criado_por'     => $req->user['id'],
        ]);

        AuditLogger::log('create', 'portal_tokens', $id, "Token portal para {$stk['nome']}");

        return Response::created([
            'id'           => $id,
            'token'        => $token,
            'nome_cliente' => $stk['nome'],
            'cpf'          => $cpfNorm,
            'expires_at'   => $expiresAt,
            'validade_dias'=> $dias,
        ], 'Token gerado com sucesso!');
    }

    public function revogar(Request $req): Response
    {
        $id  = (int) $req->param('id');
        $tid = $this->db->getTenantId();
        $this->db->update('portal_tokens',
            ['expires_at' => date('Y-m-d H:i:s')],
            ['id' => $id, 'tenant_id' => $tid]
        );
        AuditLogger::log('delete', 'portal_tokens', $id, 'Token portal revogado');
        return Response::success(null, 'Token revogado.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// LicenseController
// ═══════════════════════════════════════════════════════════════════
final class LicenseController
{
    public function status(Request $req): Response
    {
        LicenseManager::check();
        return Response::success(LicenseManager::info());
    }

    public function install(Request $req): Response
    {
        $token = trim($req->str('token'));
        if (!$token) throw new \InvalidArgumentException('Token de licença obrigatório.', 422);

        $lm     = new LicenseManager();
        $result = $lm->install($token);

        if (!$result['valid']) {
            throw new \RuntimeException($result['reason'] ?? 'Licença inválida.', 400);
        }

        AuditLogger::logDB('install', 'license', null, 'Licença instalada: ' . ($result['licensee'] ?? ''));
        return Response::success($result, 'Licença ativada com sucesso!');
    }

    public function revoke(Request $req): Response
    {
        (new LicenseManager())->revoke();
        AuditLogger::logDB('revoke', 'license', null, 'Licença revogada.');
        return Response::success(null, 'Licença revogada. Sistema em modo trial.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// CircuitController
// ═══════════════════════════════════════════════════════════════════
final class CircuitController
{
    private CircuitBreaker $cb;
    public function __construct() { $this->cb = new CircuitBreaker(); }

    public function status(Request $req): Response
    {
        return Response::success($this->cb->allStatus());
    }

    public function reset(Request $req): Response
    {
        $api = $req->param('api', '');
        $this->cb->forceClose($api);
        return Response::success(null, "Circuito '{$api}' fechado manualmente.");
    }

    public function flushQueue(Request $req): Response
    {
        $api   = $req->param('api', '');
        $items = $this->cb->dequeue($api, 100);
        return Response::success(['flushed' => count($items)], 'Fila limpa.');
    }

    public function processQueue(Request $req): Response
    {
        $processed = 0;
        $errors    = [];
        foreach (['datajud', 'evolution', 'assinafy', 'smtp'] as $api) {
            if ($this->cb->isOpen($api)) continue;
            $items = $this->cb->dequeue($api, 5);
            foreach ($items as $item) {
                try {
                    // Re-enqueue para o serviço correto tratar
                    // Aqui apenas confirma o dequeue (serviços chamam diretamente)
                    $processed++;
                } catch (\Throwable $e) {
                    $errors[] = "{$api}: " . $e->getMessage();
                    $this->cb->enqueue($api, $item);
                }
            }
        }
        return Response::success(['processed' => $processed, 'errors' => $errors]);
    }
}

// ═══════════════════════════════════════════════════════════════════
// AuditController (atualizado com verify)
// ═══════════════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════════════
// PerfilController — Perfil do Advogado/Usuário logado
// ═══════════════════════════════════════════════════════════════════
final class PerfilController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function show(Request $req): Response
    {
        $user = $this->db->first(
            "SELECT id, nome, email, oab_numero, oab_uf, telefone, data_nascimento,
                    perfil, avatar_path, cpf, endereco_json, assinafy_email
             FROM users WHERE id = ? LIMIT 1",
            [$req->user['id']]
        );
        if (!$user) throw new \RuntimeException('Usuário não encontrado.', 404);
        $user['endereco'] = json_decode($user['endereco_json'] ?? '{}', true) ?: [];
        unset($user['endereco_json']);
        return Response::success($user);
    }

    public function update(Request $req): Response
    {
        $body = $req->all();
        $id   = (int) $req->user['id'];

        // Verificar/adicionar colunas novas se não existirem
        $upd = [];
        $params = [];

        if (isset($body['nome']))           { $upd[] = 'nome = ?';           $params[] = trim($body['nome']); }
        if (isset($body['telefone']))       { $upd[] = 'telefone = ?';       $params[] = trim($body['telefone']); }
        if (isset($body['oab_numero']))     { $upd[] = 'oab_numero = ?';     $params[] = trim($body['oab_numero']); }
        if (isset($body['oab_uf']))         { $upd[] = 'oab_uf = ?';         $params[] = strtoupper(trim($body['oab_uf'])); }
        if (isset($body['data_nascimento'])){ $upd[] = 'data_nascimento = ?';$params[] = $body['data_nascimento'] ?: null; }
        if (isset($body['cpf']))            { $upd[] = 'cpf = ?';            $params[] = trim($body['cpf']); }
        if (isset($body['assinafy_email'])) { $upd[] = 'assinafy_email = ?'; $params[] = trim($body['assinafy_email']); }
        if (isset($body['endereco']))       { $upd[] = 'endereco_json = ?';  $params[] = json_encode($body['endereco']); }

        if (!empty($body['senha_nova']) && !empty($body['senha_atual'])) {
            $atual = $this->db->first("SELECT password_hash FROM users WHERE id = ?", [$id]);
            if (!password_verify($body['senha_atual'], $atual['password_hash'] ?? '')) {
                throw new \RuntimeException('Senha atual incorreta.', 400);
            }
            $upd[] = 'password_hash = ?';
            $params[] = password_hash($body['senha_nova'], PASSWORD_BCRYPT);
        }

        if (empty($upd)) return Response::success(null, 'Nada a atualizar.');

        $params[] = $id;
        $this->db->run("UPDATE users SET " . implode(', ', $upd) . " WHERE id = ?", $params);

        return Response::success(null, 'Perfil atualizado com sucesso!');
    }
}

// ═══════════════════════════════════════════════════════════════════
// ClienteController — Clientes (stakeholders tipo=cliente)
// ═══════════════════════════════════════════════════════════════════
final class ClienteController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function index(Request $req): Response
    {
        $tid   = $this->db->getTenantId();
        $busca = $req->q('q', '');
        $page  = max(1, (int)$req->q('page', 1));
        $per   = min(100, (int)$req->q('per_page', 50));

        $where  = ["tenant_id = {$tid}", "tipo = 'cliente'", "deleted_at IS NULL"];
        $params = [];
        if ($busca) {
            $where[]  = "(nome LIKE ? OR cpf_cnpj LIKE ? OR email LIKE ?)";
            $b = "%{$busca}%";
            $params[] = $b; $params[] = $b; $params[] = $b;
        }

        $sql = "SELECT id, nome, cpf_cnpj, email, telefone, whatsapp, endereco_json,
                       data_nascimento, notas, ativo, created_at
                FROM stakeholders WHERE " . implode(' AND ', $where) . "
                ORDER BY nome ASC";

        $result = $this->db->paginate($sql, $params, $page, $per);

        // Decodificar endereço
        $result['data'] = array_map(function($c) {
            $c['endereco'] = json_decode($c['endereco_json'] ?? '{}', true) ?: [];
            unset($c['endereco_json']);
            return $c;
        }, $result['data']);

        return Response::paginated($result);
    }

    public function show(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $c   = $this->db->first(
            "SELECT * FROM stakeholders WHERE id = ? AND tenant_id = ? AND tipo = 'cliente' AND deleted_at IS NULL",
            [(int)$req->param('id'), $tid]
        );
        if (!$c) throw new \RuntimeException('Cliente não encontrado.', 404);
        $c['endereco'] = json_decode($c['endereco_json'] ?? '{}', true) ?: [];
        unset($c['endereco_json']);
        return Response::success($c);
    }

    public function store(Request $req): Response
    {
        $body = $req->all();
        $tid  = $this->db->getTenantId();

        if (empty($body['nome'])) throw new \RuntimeException('Nome é obrigatório.', 400);

        $id = $this->db->insert('stakeholders', [
            'tenant_id'       => $tid,
            'tipo'            => 'cliente',
            'nome'            => trim($body['nome']),
            'cpf_cnpj'        => $body['cpf_cnpj']        ?? null,
            'email'           => $body['email']            ?? null,
            'telefone'        => $body['telefone']         ?? null,
            'whatsapp'        => $body['whatsapp']         ?? null,
            'data_nascimento' => $body['data_nascimento']  ?? null,
            'endereco_json'   => isset($body['endereco'])  ? json_encode($body['endereco']) : null,
            'notas'           => $body['notas']            ?? null,
            'ativo'           => 1,
        ]);

        return Response::created(['id' => $id], 'Cliente cadastrado com sucesso!');
    }

    public function update(Request $req): Response
    {
        $body = $req->all();
        $tid  = $this->db->getTenantId();
        $id   = (int) $req->param('id');

        $c = $this->db->first("SELECT id FROM stakeholders WHERE id = ? AND tenant_id = ? AND tipo = 'cliente'", [$id, $tid]);
        if (!$c) throw new \RuntimeException('Cliente não encontrado.', 404);

        $upd = [];
        foreach (['nome','cpf_cnpj','email','telefone','whatsapp','data_nascimento','notas'] as $f) {
            if (array_key_exists($f, $body)) $upd[$f] = $body[$f];
        }
        if (isset($body['endereco'])) $upd['endereco_json'] = json_encode($body['endereco']);

        if (!empty($upd)) $this->db->update('stakeholders', $upd, ['id' => $id]);

        return Response::success(null, 'Cliente atualizado com sucesso!');
    }

    public function destroy(Request $req): Response
    {
        $tid = $this->db->getTenantId();
        $id  = (int) $req->param('id');
        $this->db->run(
            "UPDATE stakeholders SET deleted_at = NOW() WHERE id = ? AND tenant_id = ? AND tipo = 'cliente'",
            [$id, $tid]
        );
        return Response::success(null, 'Cliente removido.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// WebhookAssinafyController — Recebe notificações da Assinafy
// ═══════════════════════════════════════════════════════════════════
final class WebhookAssinafyController
{
    private DB $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function handle(Request $req): Response
    {
        $payload = $req->all();
        error_log("[Webhook][Assinafy] " . json_encode($payload));

        $event   = $payload['event']   ?? $payload['type'] ?? '';
        $docId   = $payload['document']['id'] ?? $payload['data']['document']['id'] ?? null;
        $status  = $payload['document']['status'] ?? $payload['data']['document']['status'] ?? null;

        if (!$docId) return Response::success(null, 'ok');

        // Buscar documento no banco pelo assinafy_doc_id
        $doc = $this->db->first(
            "SELECT id, processo_id, tenant_id, nome FROM documentos WHERE assinafy_doc_id = ? LIMIT 1",
            [$docId]
        );

        if (!$doc) {
            error_log("[Webhook][Assinafy] Documento não encontrado: {$docId}");
            return Response::success(null, 'ok');
        }

        // Atualizar status no banco
        $novoStatus = match($event) {
            'document.signed', 'document_signed', 'signer_signed_document' => 'assinado',
            'document.declined', 'document_declined'                        => 'recusado',
            'document.expired', 'document_expired'                          => 'expirado',
            default => null
        };

        if ($novoStatus) {
            $this->db->run(
                "UPDATE documentos SET assinafy_status = ?, updated_at = NOW() WHERE id = ?",
                [$novoStatus, $doc['id']]
            );

            // Criar notificação interna
            $msg = match($novoStatus) {
                'assinado'  => "✅ Documento \"{$doc['nome']}\" foi assinado com sucesso!",
                'recusado'  => "❌ Documento \"{$doc['nome']}\" foi recusado pelo signatário.",
                'expirado'  => "⚠️ Documento \"{$doc['nome']}\" expirou sem assinatura.",
                default     => "Documento \"{$doc['nome']}\" atualizado."
            };

            $this->db->run(
                "INSERT INTO notificacoes (tenant_id, tipo, mensagem, referencia_tipo, referencia_id, created_at)
                 VALUES (?, 'assinatura', ?, 'documento', ?, NOW())",
                [$doc['tenant_id'], $msg, $doc['id']]
            );

            error_log("[Webhook][Assinafy] Documento {$doc['id']} → status={$novoStatus}");
        }

        return Response::success(null, 'ok');
    }
}
