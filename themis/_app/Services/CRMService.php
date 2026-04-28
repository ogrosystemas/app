<?php
declare(strict_types=1);
// ============================================================
// CRMService
// ============================================================
final class CRMService
{
    public function __construct(private DB $db) {}

    public function upsertStakeholder(array $data): int
    {
        if (!empty($data['cpf_cnpj'])) {
            $ex = $this->db->first(
                "SELECT id FROM stakeholders WHERE tenant_id = ? AND cpf_cnpj = ? AND deleted_at IS NULL",
                [$this->db->getTenantId(), $data['cpf_cnpj']]
            );
            if ($ex) { $this->db->update('stakeholders', $data, ['id' => $ex['id']]); return $ex['id']; }
        }
        return (int) $this->db->insert('stakeholders', $data);
    }

    public function registrarInteracao(array $data): int
    {
        $id = (int) $this->db->insert('crm_interacoes', $data);
        $delta = match($data['sentimento'] ?? 'neutro') {
            'positivo' => 5, 'negativo' => -3, default => 1,
        };
        $this->db->run(
            "UPDATE stakeholders SET score_engajamento = GREATEST(0, LEAST(100, score_engajamento + ?)), ultimo_contato = ? WHERE id = ?",
            [$delta, $data['data_interacao'], $data['stakeholder_id']]
        );
        return $id;
    }

    public function getAniversariantes(int $tenantId, int $dias = 7): array
    {
        return $this->db->all(
            "SELECT *, TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) AS idade,
             DATE_FORMAT(data_nascimento, '%d/%m') AS data_fmt,
             DATEDIFF(DATE_FORMAT(CONCAT(YEAR(NOW()), DATE_FORMAT(data_nascimento, '-%m-%d')), '%Y-%m-%d'), CURDATE()) AS dias_para_aniversario
             FROM stakeholders
             WHERE tenant_id = ? AND data_nascimento IS NOT NULL AND ativo = 1
               AND DAYOFYEAR(STR_TO_DATE(CONCAT(YEAR(NOW()),'-',DATE_FORMAT(data_nascimento,'%m-%d')),'%Y-%m-%d'))
                   BETWEEN DAYOFYEAR(NOW()) AND DAYOFYEAR(DATE_ADD(NOW(), INTERVAL ? DAY))
             ORDER BY dias_para_aniversario",
            [$tenantId, $dias]
        );
    }

    public function dashboard(int $tenantId): array
    {
        return [
            'total_clientes'     => (int) $this->db->scalar("SELECT COUNT(*) FROM stakeholders WHERE tenant_id = ? AND tipo='cliente' AND ativo=1 AND deleted_at IS NULL", [$tenantId]),
            'sem_contato_30dias' => (int) $this->db->scalar("SELECT COUNT(*) FROM stakeholders WHERE tenant_id = ? AND tipo='cliente' AND ativo=1 AND (ultimo_contato IS NULL OR ultimo_contato < DATE_SUB(NOW(), INTERVAL 30 DAY))", [$tenantId]),
            'sem_contato_30dias_lista' => $this->db->all(
                "SELECT id, nome, score_engajamento, ultimo_contato,
                  DATEDIFF(NOW(), COALESCE(ultimo_contato, created_at)) AS dias_sem_contato
                 FROM stakeholders WHERE tenant_id = ? AND tipo='cliente' AND ativo=1
                 AND (ultimo_contato IS NULL OR ultimo_contato < DATE_SUB(NOW(), INTERVAL 30 DAY))
                 ORDER BY dias_sem_contato DESC LIMIT 10", [$tenantId]
            ),
            'score_medio'        => round((float) $this->db->scalar("SELECT AVG(score_engajamento) FROM stakeholders WHERE tenant_id = ? AND tipo='cliente' AND ativo=1", [$tenantId]), 1),
            'interacoes_mes'     => (int) $this->db->scalar("SELECT COUNT(*) FROM crm_interacoes WHERE tenant_id = ? AND data_interacao >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tenantId]),
            'aniversariantes_7d' => $this->getAniversariantes($tenantId, 7),
            'top_clientes'       => $this->db->all(
                "SELECT s.id, s.nome, s.score_engajamento, s.ultimo_contato, COUNT(p.id) AS processos
                 FROM stakeholders s LEFT JOIN processos p ON p.cliente_id = s.id AND p.deleted_at IS NULL
                 WHERE s.tenant_id = ? AND s.tipo='cliente' AND s.ativo=1
                 GROUP BY s.id ORDER BY s.score_engajamento DESC LIMIT 10", [$tenantId]
            ),
            'alertas_pendentes'  => (int) $this->db->scalar("SELECT COUNT(*) FROM crm_alertas WHERE tenant_id = ? AND lido = 0 AND data_alerta <= CURDATE()", [$tenantId]),
        ];
    }

    public function agendarFollowup(int $stakeholderId, int $userId, string $data, string $mensagem = ''): int
    {
        $sh = $this->db->first("SELECT nome FROM stakeholders WHERE id = ?", [$stakeholderId]);
        return (int) $this->db->insert('crm_alertas', [
            'tenant_id'      => $this->db->getTenantId(),
            'stakeholder_id' => $stakeholderId,
            'user_id'        => $userId,
            'tipo'           => 'followup_vencido',
            'mensagem'       => $mensagem ?: "Follow-up com {$sh['nome']}",
            'data_alerta'    => $data,
        ]);
    }
}

// ============================================================
// ExpenseManager
// ============================================================
final class ExpenseManager
{
    public function __construct(private DB $db, private StorageManager $storage) {}

    public function registrar(array $data, ?array $reciboFile = null, ?string $reciboBase64 = null): int
    {
        // Calcula km automaticamente
        if ($data['categoria'] === 'km' && !empty($data['km_percorrido'])) {
            $vkm = (float) ($data['valor_km'] ?? $this->db->scalar(
                "SELECT valor_km FROM tenants WHERE id = (SELECT tenant_id FROM users WHERE id = ? LIMIT 1)", [$data['user_id']]
            ) ?? 0.90);
            $data['valor']    = round((float)$data['km_percorrido'] * $vkm, 2);
            $data['valor_km'] = $vkm;
        }
        $data['tenant_id'] = $this->db->getTenantId();
        $id = (int) $this->db->insert('despesas', $data);

        // Recibo via arquivo multipart
        if ($reciboFile && ($reciboFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $doc = $this->storage->upload($reciboFile, (int)($data['processo_id'] ?? 0), 'recibos', (int)$data['user_id']);
            $this->db->update('despesas', ['recibo_path' => $doc['path'], 'recibo_hash' => $doc['hash']], ['id' => $id]);
        } elseif ($reciboBase64) {
            $doc = $this->storage->uploadBase64($reciboBase64, "recibo_{$id}.jpg", (int)($data['processo_id'] ?? 0), 'recibos', (int)$data['user_id']);
            $this->db->update('despesas', ['recibo_path' => $doc['path'], 'recibo_hash' => $doc['hash']], ['id' => $id]);
        }
        return $id;
    }

    public function aprovarLote(array $ids, int $aprovadorId): int
    {
        $count = 0;
        foreach ($ids as $id) {
            $count += $this->db->update('despesas', [
                'status'       => 'aprovado',
                'aprovado_por' => $aprovadorId,
                'aprovado_em'  => date('Y-m-d H:i:s'),
            ], ['id' => (int)$id, 'status' => 'pendente', 'tenant_id' => $this->db->getTenantId()]);
        }
        return $count;
    }

    public function relatorio(int $processoId): array
    {
        $itens = $this->db->all(
            "SELECT d.*, u.nome AS usuario_nome FROM despesas d
             JOIN users u ON u.id = d.user_id
             WHERE d.processo_id = ? AND d.tenant_id = ? ORDER BY d.data_despesa",
            [$processoId, $this->db->getTenantId()]
        );
        $totais = array_reduce($itens, function($c, $i) {
            $c['total'] += $i['valor'];
            $c['por_categoria'][$i['categoria']] = ($c['por_categoria'][$i['categoria']] ?? 0) + $i['valor'];
            $c['por_status'][$i['status']]       = ($c['por_status'][$i['status']] ?? 0) + $i['valor'];
            return $c;
        }, ['total' => 0.0, 'por_categoria' => [], 'por_status' => []]);
        return ['itens' => $itens, 'totais' => $totais];
    }

    public function dashboardSocio(int $ownerId, int $mes, int $ano): array
    {
        return $this->db->all(
            "SELECT categoria, status, SUM(valor) AS total, COUNT(*) AS qtd
             FROM despesas WHERE tenant_id = ? AND owner_id = ?
               AND MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?
             GROUP BY categoria, status ORDER BY total DESC",
            [$this->db->getTenantId(), $ownerId, $mes, $ano]
        );
    }
}

