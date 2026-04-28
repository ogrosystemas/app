<?php
/**
 * pages/anuncios_plus.php
 * Sprint 2: Módulo de Férias · Saúde dos Anúncios · Visitas por Anúncio
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

$tab = in_array($_GET['tab']??'', ['ferias','saude','visitas']) ? $_GET['tab'] : 'ferias';

// ── Dados para cada aba ───────────────────────────────────
$totalAtivos  = (int)(db_one("SELECT COUNT(*) as cnt FROM products WHERE tenant_id=?{$acctSql} AND ml_status='ACTIVE'",  array_merge([$tenantId],$acctP))['cnt']??0);
$totalPausados= (int)(db_one("SELECT COUNT(*) as cnt FROM products WHERE tenant_id=?{$acctSql} AND ml_status='PAUSED'",  array_merge([$tenantId],$acctP))['cnt']??0);
$totalProducts= $totalAtivos + $totalPausados;

// Status de férias
$feriasKey   = $acctId ? "ferias_ativa_{$acctId}" : '';
$feriasAtiva = false;
$feriasPausadoEm = null;
if ($feriasKey) {
    $row = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?", [$tenantId, $feriasKey]);
    $feriasAtiva = ($row['value'] ?? '0') === '1';
    $rowPem = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?", [$tenantId, "ferias_pausado_em_{$acctId}"]);
    $feriasPausadoEm = $rowPem['value'] ?? null;
}

// Saúde — distribuição
$saudeData = [];
if ($tab === 'saude' || $tab === 'ferias') {
    $rows = db_all(
        "SELECT ml_health, COUNT(*) as cnt FROM products WHERE tenant_id=?{$acctSql} AND ml_status='ACTIVE' AND ml_health IS NOT NULL GROUP BY ml_health",
        array_merge([$tenantId], $acctP)
    );
    foreach ($rows as $r) {
        $bucket = (int)floor($r['ml_health'] / 10) * 10;
        $saudeData[$bucket] = ($saudeData[$bucket] ?? 0) + (int)$r['cnt'];
    }
}

// Anúncios para saúde
$prodsSaude = [];
if ($tab === 'saude') {
    $sortBy = in_array($_GET['sort']??'', ['health','visits','sales']) ? $_GET['sort'] : 'health';

    $orderSql = match($sortBy) {
        'visits' => 'p.ml_visits DESC',
        'sales'  => 'total_sales DESC',
        default  => 'p.ml_health ASC',
    };

    $acctSqlP = $acctId ? " AND p.meli_account_id=?" : "";

    $prodsSaude = db_all(
        "SELECT p.id, p.meli_item_id, p.title, p.price, p.ml_health, p.ml_status, p.ml_visits,
                COALESCE(SUM(oi.quantity),0) as total_sales
         FROM products p
         LEFT JOIN order_items oi ON oi.meli_item_id = p.meli_item_id
         LEFT JOIN orders o ON o.id = oi.order_id AND o.tenant_id = p.tenant_id
         WHERE p.tenant_id=?{$acctSqlP} AND p.ml_status='ACTIVE'
         AND p.ml_health IS NOT NULL
         GROUP BY p.id
         ORDER BY {$orderSql} LIMIT 50",
        array_merge([$tenantId], $acctP)
    );
}

// Visitas
$prodsVisitas = [];
if ($tab === 'visitas') {
    $prodsVisitas = db_all(
        "SELECT id, meli_item_id, title, price, ml_visits, ml_health, ml_status
         FROM products WHERE tenant_id=?{$acctSql} AND ml_status IN ('ACTIVE','PAUSED')
         ORDER BY ml_visits DESC LIMIT 50",
        array_merge([$tenantId], $acctP)
    );
}

$title = 'Anúncios+';
include __DIR__ . '/layout.php';
?>


<?php if ($tab === 'ferias'): ?>
<!-- ═══ ABA: FÉRIAS ═══ -->
<div style="padding:20px;max-width:700px">
  <div style="margin-bottom:24px">
    <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Módulo de Férias</h1>
    <p style="font-size:11px;color:#5E5E5A">Pause todos os anúncios ativos com um clique e reative ao voltar</p>
  </div>

  <!-- Status card -->
  <div id="ferias-card" style="background:#1A1A1C;border:0.5px solid <?= $feriasAtiva?'rgba(245,158,11,.4)':'#2E2E33' ?>;border-radius:14px;padding:24px;margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
      <div style="width:52px;height:52px;border-radius:50%;background:<?= $feriasAtiva?'rgba(245,158,11,.15)':'rgba(34,197,94,.15)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i data-lucide="<?= $feriasAtiva?'pause-circle':'play-circle' ?>" style="width:28px;height:28px;color:<?= $feriasAtiva?'#f59e0b':'#22c55e' ?>"></i>
      </div>
      <div>
        <div id="ferias-status-title" style="font-size:16px;font-weight:600;color:#E8E8E6;margin-bottom:4px">
          <?= $feriasAtiva ? '🏖️ Férias ativas' : '✅ Loja funcionando normalmente' ?>
        </div>
        <div id="ferias-status-sub" style="font-size:12px;color:#5E5E5A">
          <?php if ($feriasAtiva && $feriasPausadoEm): ?>
            Pausado em <?= date('d/m/Y H:i', strtotime($feriasPausadoEm)) ?>
          <?php elseif ($feriasAtiva): ?>
            Anúncios pausados
          <?php else: ?>
            <?= $totalAtivos ?> anúncio<?= $totalAtivos!==1?'s':'' ?> ativo<?= $totalAtivos!==1?'s':'' ?> · <?= $totalPausados ?> pausado<?= $totalPausados!==1?'s':'' ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
      <div style="background:#252528;border-radius:8px;padding:12px 14px;text-align:center">
        <div id="ferias-ativos" style="font-size:24px;font-weight:700;color:#22c55e"><?= $totalAtivos ?></div>
        <div style="font-size:10px;color:#5E5E5A;margin-top:2px">Ativos</div>
      </div>
      <div style="background:#252528;border-radius:8px;padding:12px 14px;text-align:center">
        <div id="ferias-pausados" style="font-size:24px;font-weight:700;color:#f59e0b"><?= $totalPausados ?></div>
        <div style="font-size:10px;color:#5E5E5A;margin-top:2px">Pausados</div>
      </div>
    </div>

    <?php if (!$feriasAtiva): ?>
    <!-- Botão ativar férias -->
    <div style="background:rgba(245,158,11,.06);border:0.5px solid rgba(245,158,11,.3);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:11px;color:#f59e0b;line-height:1.6">
      ⚠ Ao ativar, todos os <strong><?= $totalAtivos ?></strong> anúncios ativos serão <strong>pausados</strong> no Mercado Livre. Compradores não verão seus anúncios até você desativar as férias.
    </div>
    <button onclick="ativarFerias()" id="btn-ferias"
      style="width:100%;padding:14px;background:rgba(245,158,11,.1);border:0.5px solid #f59e0b;color:#f59e0b;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
      <i data-lucide="umbrella" style="width:18px;height:18px"></i>
      Ativar Férias — Pausar <?= $totalAtivos ?> anúncio<?= $totalAtivos!==1?'s':'' ?>
    </button>
    <?php else: ?>
    <!-- Botão desativar férias -->
    <div style="background:rgba(34,197,94,.06);border:0.5px solid rgba(34,197,94,.3);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:11px;color:#22c55e;line-height:1.6">
      ✓ Ao desativar, todos os <strong><?= $totalPausados ?></strong> anúncios pausados serão <strong>reativados</strong> no Mercado Livre.
    </div>
    <button onclick="desativarFerias()" id="btn-ferias"
      style="width:100%;padding:14px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
      <i data-lucide="play-circle" style="width:18px;height:18px"></i>
      Voltar das Férias — Reativar <?= $totalPausados ?> anúncio<?= $totalPausados!==1?'s':'' ?>
    </button>
    <?php endif; ?>
  </div>

  <!-- Aviso -->
  <div style="padding:12px 14px;background:#252528;border-radius:10px;font-size:11px;color:#5E5E5A;line-height:1.6">
    <strong style="color:#9A9A96">💡 Dica:</strong> O módulo de férias pausa/reativa apenas os anúncios cadastrados no SAM.
    Anúncios criados diretamente no ML após a sincronização podem não ser afetados.
  </div>
</div>

<?php elseif ($tab === 'saude'): ?>
<!-- ═══ ABA: SAÚDE ═══ -->
<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Saúde dos Anúncios</h1>
      <p style="font-size:11px;color:#5E5E5A">Score de qualidade baseado nos critérios do ML</p>
    </div>
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
      <!-- Filtros de ordenação -->
      <div style="display:flex;gap:4px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;padding:3px">
        <?php foreach ([
          'health' => ['Saúde',   'heart-pulse', '#ef4444'],
          'visits' => ['Visitas', 'eye',         '#3483FA'],
          'sales'  => ['Vendas',  'shopping-bag','#22c55e'],
        ] as $s => [$slabel, $sicon, $scolor]): ?>
        <a href="?tab=saude&sort=<?= $s ?>" style="display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:500;text-decoration:none;transition:all .15s;
          background:<?= ($sortBy??'health')===$s?$scolor.'20':'transparent' ?>;
          color:<?= ($sortBy??'health')===$s?$scolor:'#5E5E5A' ?>;
          border:0.5px solid <?= ($sortBy??'health')===$s?$scolor:'transparent' ?>">
          <i data-lucide="<?= $sicon ?>" style="width:11px;height:11px"></i>
          <?= $slabel ?>
        </a>
        <?php endforeach; ?>
      </div>
      <button onclick="sincronizarSaude()" id="btn-sync-saude" class="btn-secondary" style="font-size:12px;gap:6px">
        <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Sincronizar
      </button>
    </div>
  </div>

  <?php if (empty($prodsSaude)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="heart-pulse" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhum dado de saúde ainda</div>
    <div style="font-size:11px;color:#5E5E5A;margin-bottom:16px">Clique em Sincronizar para buscar os dados do ML</div>
    <button onclick="sincronizarSaude()" class="btn-primary" style="font-size:12px">
      <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Sincronizar agora
    </button>
  </div>
  <?php else: ?>

  <!-- Distribuição de saúde -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:20px">
    <?php
    $buckets = [
      [90,100,'#22c55e','Excelente'],
      [70,89, '#3483FA','Bom'],
      [50,69, '#f59e0b','Regular'],
      [0, 49, '#ef4444','Crítico'],
    ];
    foreach ($buckets as [$min,$max,$color,$label]):
      $cnt = 0;
      foreach ($prodsSaude as $p) {
        if ($p['ml_health'] >= $min && $p['ml_health'] <= $max) $cnt++;
      }
    ?>
    <div style="background:#1A1A1C;border:0.5px solid <?= $color ?>40;border-radius:10px;padding:12px 14px;text-align:center">
      <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= $cnt ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px"><?= $label ?> (<?= $min ?>-<?= $max ?>%)</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Lista de anúncios com saúde baixa primeiro -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:12px;font-weight:500;color:#E8E8E6"><?= count($prodsSaude) ?> anúncios — ordenados por saúde (menor primeiro)</span>
    </div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Anúncio</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500;white-space:nowrap">Preço</th>
            <th style="padding:10px 14px;text-align:center;color:<?= ($sortBy??'health')==='health'?'#ef4444':'#5E5E5A' ?>;font-weight:<?= ($sortBy??'health')==='health'?'600':'500' ?>">
              <span style="display:flex;align-items:center;justify-content:center;gap:4px">
                <i data-lucide="heart-pulse" style="width:11px;height:11px"></i> Saúde
              </span>
            </th>
            <th style="padding:10px 14px;text-align:center;color:<?= ($sortBy??'health')==='visits'?'#3483FA':'#5E5E5A' ?>;font-weight:<?= ($sortBy??'health')==='visits'?'600':'500' ?>">
              <span style="display:flex;align-items:center;justify-content:center;gap:4px">
                <i data-lucide="eye" style="width:11px;height:11px"></i> Visitas 30d
              </span>
            </th>
            <th style="padding:10px 14px;text-align:center;color:<?= ($sortBy??'health')==='sales'?'#22c55e':'#5E5E5A' ?>;font-weight:<?= ($sortBy??'health')==='sales'?'600':'500' ?>">
              <span style="display:flex;align-items:center;justify-content:center;gap:4px">
                <i data-lucide="shopping-bag" style="width:11px;height:11px"></i> Vendas
              </span>
            </th>
            <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">IA</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($prodsSaude as $p):
            $h = (int)$p['ml_health'];
            $hColor = $h >= 90 ? '#22c55e' : ($h >= 70 ? '#3483FA' : ($h >= 50 ? '#f59e0b' : '#ef4444'));
            $hLabel = $h >= 90 ? 'Excelente' : ($h >= 70 ? 'Bom' : ($h >= 50 ? 'Regular' : 'Crítico'));
          ?>
          <tr style="border-bottom:0.5px solid #2E2E33">
            <td style="padding:10px 14px;max-width:280px">
              <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#E8E8E6"><?= htmlspecialchars($p['title']) ?></div>
              <?php if ($p['meli_item_id']): ?>
              <a href="https://produto.mercadolivre.com.br/<?= $p['meli_item_id'] ?>" target="_blank"
                style="font-size:10px;color:#3483FA;text-decoration:none"><?= $p['meli_item_id'] ?> ↗</a>
              <?php endif; ?>
            </td>
            <td style="padding:10px 14px;text-align:right;color:#E8E8E6;white-space:nowrap">
              R$ <?= number_format($p['price'],2,',','.') ?>
            </td>
            <td style="padding:10px 14px;text-align:center">
              <div style="display:flex;align-items:center;justify-content:center;gap:6px">
                <div style="width:48px;height:6px;background:#2E2E33;border-radius:3px;overflow:hidden">
                  <div style="width:<?= $h ?>%;height:100%;background:<?= $hColor ?>;border-radius:3px"></div>
                </div>
                <span style="font-size:11px;font-weight:600;color:<?= $hColor ?>"><?= $h ?>%</span>
              </div>
              <div style="font-size:9px;color:<?= $hColor ?>;margin-top:2px"><?= $hLabel ?></div>
            </td>
            <td style="padding:10px 14px;text-align:center;color:#9A9A96">
              <?= number_format((int)($p['ml_visits']??0),0,',','.') ?>
            </td>
            <td style="padding:10px 14px;text-align:center">
              <?php $sales = (int)($p['total_sales']??0); ?>
              <span style="font-size:12px;font-weight:<?= $sales>0?'600':'400' ?>;color:<?= $sales>0?'#22c55e':'#5E5E5A' ?>">
                <?= $sales > 0 ? number_format($sales,0,',','.') : '—' ?>
              </span>
            </td>
            <td style="padding:10px 14px;text-align:center">
              <?php if ($h < 80): ?>
              <button onclick="analisarSaude('<?= $p['id'] ?>','<?= htmlspecialchars(addslashes($p['title'])) ?>',<?= $h ?>)"
                style="padding:4px 10px;background:rgba(255,230,0,.1);border:0.5px solid rgba(255,230,0,.3);color:#FFE600;border-radius:6px;font-size:10px;cursor:pointer;display:inline-flex;align-items:center;gap:4px">
                <i data-lucide="sparkles" style="width:10px;height:10px"></i> Analisar
              </button>
              <?php else: ?>
              <span style="font-size:10px;color:#22c55e">✓ OK</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal de análise IA -->
<div id="saude-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:520px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
      <i data-lucide="sparkles" style="width:16px;height:16px;color:#FFE600"></i>
      <span style="font-size:14px;font-weight:600;color:#E8E8E6">Análise de Saúde — IA</span>
      <button onclick="document.getElementById('saude-modal').style.display='none'" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer">✕</button>
    </div>
    <div id="saude-modal-content" style="font-size:12px;color:#9A9A96;line-height:1.7">
      <div style="text-align:center;padding:24px;color:#5E5E5A">
        <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
        Analisando com IA...
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'visitas'): ?>
<!-- ═══ ABA: VISITAS ═══ -->
<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Visitas por Anúncio</h1>
      <p style="font-size:11px;color:#5E5E5A">Ranking de visitas dos últimos 30 dias</p>
    </div>
    <button onclick="sincronizarVisitas()" id="btn-sync-vis" class="btn-secondary" style="font-size:12px;gap:6px">
      <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Sincronizar
    </button>
  </div>

  <?php if (empty($prodsVisitas)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="eye" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhum dado de visitas ainda</div>
    <div style="font-size:11px;color:#5E5E5A;margin-bottom:16px">Clique em Sincronizar para buscar as visitas do ML</div>
    <button onclick="sincronizarVisitas()" class="btn-primary" style="font-size:12px">
      <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Sincronizar agora
    </button>
  </div>
  <?php else:
    $maxVisits = max(array_column($prodsVisitas, 'ml_visits') ?: [1]);
  ?>
  <!-- KPI total -->
  <?php
    $totalVisits30 = array_sum(array_column($prodsVisitas, 'ml_visits'));
    $mediaVisits   = count($prodsVisitas) > 0 ? round($totalVisits30 / count($prodsVisitas)) : 0;
    $topAnuncio    = $prodsVisitas[0] ?? null;
  ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Total de visitas (30d)</div>
      <div style="font-size:22px;font-weight:700;color:#E8E8E6"><?= number_format($totalVisits30,0,',','.') ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Média por anúncio</div>
      <div style="font-size:22px;font-weight:700;color:#3483FA"><?= number_format($mediaVisits,0,',','.') ?></div>
    </div>
    <?php if ($topAnuncio): ?>
    <div style="background:#1A1A1C;border:0.5px solid rgba(255,230,0,.3);border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">🏆 Mais visitado</div>
      <div style="font-size:13px;font-weight:600;color:#FFE600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_substr($topAnuncio['title'],0,30)) ?></div>
      <div style="font-size:10px;color:#5E5E5A"><?= number_format((int)$topAnuncio['ml_visits'],0,',','.') ?> visitas</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Lista ranking -->
  <div style="display:flex;flex-direction:column;gap:6px">
    <?php foreach ($prodsVisitas as $i => $p):
      $vis    = (int)($p['ml_visits'] ?? 0);
      $pct    = $maxVisits > 0 ? round($vis / $maxVisits * 100) : 0;
      $health = (int)($p['ml_health'] ?? 0);
      $hColor = $health >= 90 ? '#22c55e' : ($health >= 70 ? '#3483FA' : ($health >= 50 ? '#f59e0b' : '#ef4444'));
      $medal  = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ''));
    ?>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:12px">
      <div style="font-size:11px;font-weight:700;color:#5E5E5A;width:24px;text-align:center;flex-shrink:0">
        <?= $medal ?: ($i+1) ?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px">
          <?= htmlspecialchars($p['title']) ?>
        </div>
        <div style="height:6px;background:#252528;border-radius:3px;overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,#3483FA,#22c55e);border-radius:3px;transition:width .3s"></div>
        </div>
      </div>
      <div style="text-align:right;flex-shrink:0;min-width:70px">
        <div style="font-size:13px;font-weight:700;color:#E8E8E6"><?= number_format($vis,0,',','.') ?></div>
        <div style="font-size:9px;color:#5E5E5A">visitas</div>
      </div>
      <?php if ($health > 0): ?>
      <div style="font-size:10px;font-weight:600;color:<?= $hColor ?>;flex-shrink:0;min-width:36px;text-align:center"><?= $health ?>%</div>
      <?php endif; ?>
      <?php if ($p['meli_item_id']): ?>
      <a href="https://produto.mercadolivre.com.br/<?= $p['meli_item_id'] ?>" target="_blank"
        style="color:#5E5E5A;text-decoration:none;flex-shrink:0">
        <i data-lucide="external-link" style="width:12px;height:12px"></i>
      </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
lucide.createIcons();

// ── FÉRIAS ────────────────────────────────────────────────
async function ativarFerias() {
  if (!await dialog({title:'Ativar Férias',message:'Pausar todos os anúncios ativos?<br>Eles ficarão <strong>invisíveis</strong> para compradores.',confirmText:'Pausar Tudo',danger:true})) return;
  const btn = document.getElementById('btn-ferias');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:16px;height:16px;animation:spin 1s linear infinite"></i> Pausando anúncios...';
  lucide.createIcons();
  const fd = new FormData(); fd.append('action','ativar');
  const r = await fetch('/api/ferias.php', {method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) {
    toast(`🏖️ ${d.pausados} anúncio(s) pausado(s)!${d.erros?' · '+d.erros+' erro(s)':''}`, 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    toast(d.error || 'Erro ao pausar', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="umbrella" style="width:18px;height:18px"></i> Ativar Férias';
    lucide.createIcons();
  }
}

async function desativarFerias() {
  if (!await dialog({title:'Voltar das Férias',message:'Reativar todos os anúncios pausados?',confirmText:'Reativar Tudo',confirmColor:'#22c55e'})) return;
  const btn = document.getElementById('btn-ferias');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:16px;height:16px;animation:spin 1s linear infinite"></i> Reativando anúncios...';
  lucide.createIcons();
  const fd = new FormData(); fd.append('action','desativar');
  const r = await fetch('/api/ferias.php', {method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) {
    toast(`✅ ${d.reativados} anúncio(s) reativado(s)!${d.erros?' · '+d.erros+' erro(s)':''}`, 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    toast(d.error || 'Erro ao reativar', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="play-circle" style="width:18px;height:18px"></i> Voltar das Férias';
    lucide.createIcons();
  }
}

// ── SAÚDE ─────────────────────────────────────────────────
async function sincronizarSaude() {
  const btn = document.getElementById('btn-sync-saude');
  if (!btn) return;
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Sincronizando...';
  lucide.createIcons();
  const r = await fetch('/api/sync_health_visits.php?limit=50');
  const d = await r.json();
  if (d.ok) {
    toast(`${d.updated} anúncio(s) sincronizado(s)!`, 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    toast('Erro ao sincronizar', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Sincronizar dados';
    lucide.createIcons();
  }
}

async function analisarSaude(productId, title, health) {
  const modal = document.getElementById('saude-modal');
  modal.style.display = 'flex';
  document.getElementById('saude-modal-content').innerHTML = `
    <div style="text-align:center;padding:24px;color:#5E5E5A">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
      Analisando com IA...
    </div>`;
  lucide.createIcons();

  const fd = new FormData();
  fd.append('action',     'analisar_saude');
  fd.append('product_id', productId);
  fd.append('title',      title);
  fd.append('health',     health);
  const r = await fetch('/api/ai_config.php', {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok && d.analysis) {
    document.getElementById('saude-modal-content').innerHTML = `
      <div style="background:#252528;border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:11px;color:#5E5E5A;margin-bottom:6px">Anúncio: <strong style="color:#E8E8E6">${title}</strong></div>
        <div style="font-size:11px;color:#5E5E5A">Saúde atual: <strong style="color:${health>=70?'#22c55e':health>=50?'#f59e0b':'#ef4444'}">${health}%</strong></div>
      </div>
      <div style="font-size:12px;color:#E8E8E6;line-height:1.7;white-space:pre-wrap">${d.analysis}</div>`;
  } else {
    document.getElementById('saude-modal-content').innerHTML = `
      <div style="color:#ef4444;font-size:12px">${d.error || 'Configure um provedor de IA em Integração ML.'}</div>`;
  }
}

// ── VISITAS ───────────────────────────────────────────────
async function sincronizarVisitas() {
  const btn = document.getElementById('btn-sync-vis');
  if (!btn) return;
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Sincronizando...';
  lucide.createIcons();
  const r = await fetch('/api/sync_health_visits.php?limit=50');
  const d = await r.json();
  if (d.ok) {
    toast(`${d.updated} anúncio(s) sincronizado(s)!`, 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    toast('Erro ao sincronizar', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Sincronizar';
    lucide.createIcons();
  }
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
