<?php
declare(strict_types=1);
final class WorkflowEngine
{
    private const RULES = [
        'proposta' => [
            'ativo' => [
                'tarefas' => [
                    ['titulo' => 'Cadastrar partes e documentos iniciais',         'prioridade' => 'alta',    'dias' => 2],
                    ['titulo' => 'Verificar procuração e contrato de honorários',  'prioridade' => 'alta',    'dias' => 3],
                    ['titulo' => 'Protocolar petição inicial',                     'prioridade' => 'critica', 'dias' => 5],
                ],
                'notificar_cliente' => true,
            ],
        ],
        'ativo' => [
            'aguardando_decisao' => [
                'tarefas' => [
                    ['titulo' => 'Monitorar publicação da decisão no DJE',  'prioridade' => 'alta',    'dias' => 1],
                    ['titulo' => 'Verificar prazo para interposição de recurso', 'prioridade' => 'critica', 'dias' => 3],
                ],
            ],
            'execucao' => [
                'tarefas' => [
                    ['titulo' => 'Iniciar cálculo de liquidação de sentença', 'prioridade' => 'critica', 'dias' => 5],
                    ['titulo' => 'Pesquisar bens penhoráveis do executado',   'prioridade' => 'alta',    'dias' => 7],
                    ['titulo' => 'Requerer expedição de alvará (se couber)',  'prioridade' => 'media',   'dias' => 30],
                ],
            ],
        ],
        'aguardando_decisao' => [
            'recurso' => [
                'tarefas' => [
                    ['titulo' => 'Identificar fundamentos do recurso',  'prioridade' => 'critica', 'dias' => 3],
                    ['titulo' => 'Preparar minuta do recurso',          'prioridade' => 'critica', 'dias' => 10],
                ],
            ],
            'execucao' => [
                'tarefas' => [
                    ['titulo' => 'Iniciar liquidação de sentença', 'prioridade' => 'critica', 'dias' => 5],
                ],
            ],
        ],
        'recurso' => [
            'execucao' => [
                'tarefas' => [
                    ['titulo' => 'Calcular valor atualizado para execução', 'prioridade' => 'critica', 'dias' => 5],
                ],
            ],
        ],
    ];

    public function __construct(private DB $db) {}

    public function transition(int $processoId, string $novoStatus, int $userId): array
    {
        $processo = $this->db->first("SELECT * FROM processos WHERE id = ? AND deleted_at IS NULL", [$processoId]);
        if (!$processo) throw new \RuntimeException('Processo não encontrado.', 404);

        $statusAtual = $processo['status'];
        if ($statusAtual === $novoStatus) return ['tarefas_criadas' => 0, 'notificacoes' => 0];

        return $this->db->transaction(function (DB $db) use ($processo, $processoId, $statusAtual, $novoStatus, $userId) {
            $db->update('processos', ['status' => $novoStatus, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $processoId]);

            $db->insert('processo_andamentos', [
                'tenant_id'      => $processo['tenant_id'],
                'processo_id'    => $processoId,
                'user_id'        => $userId,
                'tipo'           => 'sistema',
                'titulo'         => "Status atualizado: {$statusAtual} → {$novoStatus}",
                'data_andamento' => date('Y-m-d H:i:s'),
                'fonte'          => 'sistema',
            ]);

            $tarefas = 0;
            $notifs  = 0;
            $regras  = self::RULES[$statusAtual][$novoStatus] ?? null;

            if ($regras) {
                foreach ($regras['tarefas'] ?? [] as $t) {
                    $db->insert('processo_tarefas', [
                        'tenant_id'       => $processo['tenant_id'],
                        'processo_id'     => $processoId,
                        'user_id'         => $processo['responsavel_id'],
                        'criado_por'      => $userId,
                        'titulo'          => $t['titulo'],
                        'prioridade'      => $t['prioridade'],
                        'status'          => 'pendente',
                        'data_vencimento' => date('Y-m-d H:i:s', strtotime("+{$t['dias']} days")),
                        'gatilho_status'  => $novoStatus,
                    ]);
                    $tarefas++;
                }

                $db->insert('notificacoes', [
                    'tenant_id' => $processo['tenant_id'],
                    'user_id'   => $processo['responsavel_id'],
                    'tipo'      => 'tarefa',
                    'titulo'    => "Processo #{$processo['numero_interno']} — {$novoStatus}",
                    'mensagem'  => "{$tarefas} tarefa(s) criadas automaticamente.",
                    'icone'     => 'bolt',
                    'cor'       => 'blue',
                    'link_url'  => "/processos/{$processoId}",
                ]);
                $notifs++;

                if (!empty($regras['notificar_cliente'])) {
                    $this->notifyClient($processo, $novoStatus);
                    $notifs++;
                }
            }

            return ['tarefas_criadas' => $tarefas, 'notificacoes' => $notifs, 'status_anterior' => $statusAtual, 'status_novo' => $novoStatus];
        });
    }

    /** Verificações diárias — executar via cron */
    public function runDailyChecks(int $tenantId): array
    {
        $report = ['parados' => 0, 'prazos_urgentes' => 0, 'followup_crm' => 0, 'aniversarios' => 0];

        // Processos parados > 60 dias
        $parados = $this->db->all(
            "SELECT p.id, p.numero_interno, p.responsavel_id,
             DATEDIFF(NOW(), COALESCE(p.ultimo_andamento, p.created_at)) AS dias
             FROM processos p
             WHERE p.tenant_id = ? AND p.status NOT IN ('arquivado','encerrado','suspenso')
               AND DATEDIFF(NOW(), COALESCE(p.ultimo_andamento, p.created_at)) >= 60
               AND p.deleted_at IS NULL",
            [$tenantId]
        );
        foreach ($parados as $p) {
            $this->db->update('processos', ['dias_parado' => $p['dias']], ['id' => $p['id']]);
            $this->db->insert('notificacoes', [
                'tenant_id' => $tenantId,
                'user_id'   => $p['responsavel_id'],
                'tipo'      => 'datajud',
                'titulo'    => "⚠️ Processo parado há {$p['dias']} dias",
                'mensagem'  => "Processo #{$p['numero_interno']} sem movimentação.",
                'cor'       => 'orange',
                'link_url'  => "/processos/{$p['id']}",
            ]);
            $report['parados']++;
        }

        // Prazos nos próximos 7 dias
        $prazos = $this->db->all(
            "SELECT pr.titulo, pr.data_prazo, p.numero_interno, p.responsavel_id, p.id AS pid
             FROM prazos pr JOIN processos p ON p.id = pr.processo_id
             WHERE pr.tenant_id = ? AND pr.cumprido = 0
               AND pr.data_prazo BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)",
            [$tenantId]
        );
        foreach ($prazos as $pr) {
            $this->db->insert('notificacoes', [
                'tenant_id' => $tenantId,
                'user_id'   => $pr['responsavel_id'],
                'tipo'      => 'prazo',
                'titulo'    => "🔴 Prazo: {$pr['titulo']}",
                'mensagem'  => "Vence em " . date('d/m/Y H:i', strtotime($pr['data_prazo'])) . " — #{$pr['numero_interno']}",
                'cor'       => 'red',
                'link_url'  => "/processos/{$pr['pid']}",
            ]);
            $report['prazos_urgentes']++;
        }

        // CRM: sem contato há 30 dias
        $semContato = $this->db->all(
            "SELECT id, nome, responsavel_id FROM stakeholders
             WHERE tenant_id = ? AND tipo = 'cliente' AND ativo = 1
               AND (ultimo_contato IS NULL OR ultimo_contato < DATE_SUB(NOW(), INTERVAL 30 DAY))",
            [$tenantId]
        );
        foreach ($semContato as $sh) {
            if (!$sh['responsavel_id']) continue;
            $this->db->insert('crm_alertas', [
                'tenant_id'      => $tenantId,
                'stakeholder_id' => $sh['id'],
                'user_id'        => $sh['responsavel_id'],
                'tipo'           => 'sem_contato',
                'mensagem'       => "Sem contato com {$sh['nome']} há mais de 30 dias.",
                'data_alerta'    => date('Y-m-d'),
            ]);
            $report['followup_crm']++;
        }

        // Aniversariantes semana
        $aniv = $this->db->all(
            "SELECT id, nome, responsavel_id FROM stakeholders
             WHERE tenant_id = ? AND data_nascimento IS NOT NULL AND ativo = 1
               AND DATE_FORMAT(data_nascimento, '%m-%d') BETWEEN DATE_FORMAT(NOW(),'%m-%d')
               AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY),'%m-%d')",
            [$tenantId]
        );
        foreach ($aniv as $a) {
            if (!$a['responsavel_id']) continue;
            $this->db->insert('crm_alertas', [
                'tenant_id'      => $tenantId,
                'stakeholder_id' => $a['id'],
                'user_id'        => $a['responsavel_id'],
                'tipo'           => 'aniversario',
                'mensagem'       => "🎂 Aniversário de {$a['nome']} nos próximos 7 dias.",
                'data_alerta'    => date('Y-m-d'),
            ]);
            $report['aniversarios']++;
        }

        return $report;
    }

    private function notifyClient(array $processo, string $novoStatus): void
    {
        $this->db->insert('webhook_eventos', [
            'tenant_id' => $processo['tenant_id'],
            'fonte'     => 'interno',
            'evento'    => 'notificar_cliente',
            'payload'   => json_encode([
                'processo_id'    => $processo['id'],
                'cliente_id'     => $processo['cliente_id'],
                'status'         => $novoStatus,
                'numero_interno' => $processo['numero_interno'],
            ]),
            'status' => 'recebido',
        ]);
    }
}
