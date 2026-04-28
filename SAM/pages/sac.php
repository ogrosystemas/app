<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_sac');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND sm.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : []; // NUNCA null

// Aba ativa
$sacTab = in_array($_GET['tab'] ?? '', ['inbox','reclamacoes','avaliacoes'])
    ? $_GET['tab']
    : 'inbox';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'messages') {
        $orderId = $_POST['order_id'] ?? '';
        $msgs    = db_all("SELECT * FROM sac_messages WHERE order_id=? ORDER BY created_at ASC", [$orderId]);
        $order   = db_one("SELECT * FROM orders WHERE id=? AND tenant_id=?", [$orderId, $tenantId]);
        $conv    = db_one("SELECT * FROM sac_conversations WHERE order_id=?", [$orderId]);
        db_query("UPDATE sac_messages SET is_read=1 WHERE order_id=? AND from_role='BUYER'", [$orderId]);
        echo json_encode(['ok'=>true, 'messages'=>$msgs, 'order'=>$order, 'conv_status'=>$conv['status'] ?? 'OPEN']);
        exit;
    }

    if ($action === 'send') {
        audit_log('SAC_SEND_MESSAGE', 'sac_messages', null, null, ['pack_id' => $_POST['pack_id'] ?? '']);
        $orderId = $_POST['order_id'] ?? '';
        $text    = trim($_POST['text'] ?? '');
        if (!$orderId || !$text) { echo json_encode(['ok'=>false]); exit; }
        $order = db_one("SELECT * FROM orders WHERE id=? AND tenant_id=?", [$orderId, $tenantId]);
        if (!$order) { echo json_encode(['ok'=>false]); exit; }
        // Envia mensagem via API ML
        $account = db_one("SELECT * FROM meli_accounts WHERE id=? AND is_active=1", [$order['meli_account_id']]);
        $mlError = null;
        if ($account && $order['meli_order_id']) {
            $packId  = str_replace('#', '', $order['meli_order_id']);
            $sellerId = $account['meli_user_id'];
            // Descriptografa token
            $token = TOKEN_KEY ? crypto_decrypt_token($account['access_token_enc']) : $account['access_token_enc'];
            $payload = json_encode(['text' => $text, 'tags' => ['post_sale']]);
            $ch = curl_init("https://api.mercadolibre.com/messages/packs/{$packId}/sellers/{$sellerId}?tag=post_sale");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$token}",
                    "Content-Type: application/json",
                    "User-Agent: OgroERP/1.0",
                ],
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $res     = curl_exec($ch);
            $httpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $resData = json_decode($res, true);
            if ($httpCode >= 400) {
                $mlError = $resData['message'] ?? "Erro HTTP {$httpCode}";
            }
        }

        // Salva no banco independente do resultado ML
        db_insert('sac_messages', [
            'tenant_id'       => $tenantId,
            'meli_account_id' => $order['meli_account_id'],
            'order_id'        => $orderId,
            'from_role'       => 'SELLER',
            'from_nickname'   => $user['name'],
            'message_text'    => $text,
            'is_read'         => 1,
        ]);
        db_upsert('sac_conversations',
            ['tenant_id'=>$tenantId, 'order_id'=>$orderId, 'status'=>'WAITING'],
            ['status']
        );
        echo json_encode(['ok'=>true, 'ml_error'=>$mlError]);
        exit;
    }

    if ($action === 'set_status') {
        audit_log('SAC_UPDATE_STATUS', 'sac_conversations', $_POST['order_id']??'', null, ['status'=>$_POST['status']??'']);
        $orderId   = $_POST['order_id'] ?? '';
        $newStatus = $_POST['status'] ?? 'OPEN';
        db_upsert('sac_conversations',
            ['tenant_id'=>$tenantId, 'order_id'=>$orderId, 'status'=>$newStatus],
            ['status']
        );
        echo json_encode(['ok'=>true, 'status'=>$newStatus]);
        exit;
    }

    if ($action === 'ai') {
        $text = trim($_POST['text'] ?? '');
        if (!$text) { echo json_encode(['suggestion' => 'Texto vazio.']); exit; }

        require_once dirname(__DIR__) . '/ai.php';
        $prompt = "Responda apenas com a sugestão de resposta ao cliente do Mercado Livre, sem saudações, sem explicações e sem aspas extras. Seja curto, direto e empático em português brasileiro. Cliente disse: \"{$text}\"";
        $result = ai_generate_for($tenantId, 'sac', $prompt, 300);

        if ($result['text']) {
            echo json_encode(['suggestion' => $result['text']]);
        } else {
            echo json_encode(['suggestion' => 'Configure um provedor de IA em Integração ML → IA Provedor de Linguagem.']);
        }
        exit;
    }
}

// KPIs de status — filtrados pela conta ativa
$kpiAcctSql  = $acctId ? " AND sm.meli_account_id=?" : " AND 1=0";
$kpiAcctSqlC = $acctId ? " AND o.meli_account_id=?" : " AND 1=0"; // para sac_conversations via orders
$kpiAcctSqlO = $acctId ? " AND o.meli_account_id=?" : " AND 1=0";
$kpiP        = $acctId ? [$acctId] : [];

$totalConvs    = db_one("SELECT COUNT(DISTINCT sm.order_id) as cnt FROM sac_messages sm WHERE sm.tenant_id=?{$kpiAcctSql}", array_merge([$tenantId], (array)$kpiP));
$openConvs     = db_one("SELECT COUNT(*) as cnt FROM sac_conversations sc JOIN orders o ON o.id=sc.order_id WHERE sc.tenant_id=?{$kpiAcctSqlC} AND sc.status='OPEN'", array_merge([$tenantId], (array)$kpiP));
$waitConvs     = db_one("SELECT COUNT(*) as cnt FROM sac_conversations sc JOIN orders o ON o.id=sc.order_id WHERE sc.tenant_id=?{$kpiAcctSqlC} AND sc.status='WAITING'", array_merge([$tenantId], (array)$kpiP));
$resolvedConvs = db_one("SELECT COUNT(*) as cnt FROM sac_conversations sc JOIN orders o ON o.id=sc.order_id WHERE sc.tenant_id=?{$kpiAcctSqlC} AND sc.status='RESOLVED'", array_merge([$tenantId], (array)$kpiP));
$unreadTotal   = db_one("SELECT COUNT(*) as cnt FROM sac_messages sm WHERE sm.tenant_id=?{$kpiAcctSql} AND sm.is_read=0 AND sm.from_role='BUYER'", array_merge([$tenantId], (array)$kpiP));
$mediacoTotal  = db_one("SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id=?{$kpiAcctSqlO} AND o.has_mediacao=1", array_merge([$tenantId], (array)$kpiP));

// Histórico 7 dias para gráfico — filtrado pela conta ativa
$weekHistory = db_all(
    "SELECT DATE(sm.created_at) as day, COUNT(*) as cnt
     FROM sac_messages sm
     WHERE sm.tenant_id=?{$kpiAcctSql} AND sm.from_role='BUYER' AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(sm.created_at) ORDER BY day ASC",
    array_merge([$tenantId], (array)$kpiP)
);

// Conversas com status — paginadas
$sacPage    = max(1, (int)($_GET['p'] ?? 1));
$sacPerPage = 30;
$sacOffset  = ($sacPage - 1) * $sacPerPage;

$totalConvsQuery = db_one(
    "SELECT COUNT(DISTINCT sm.order_id) as cnt
     FROM sac_messages sm
     JOIN orders o ON o.id=sm.order_id
     WHERE sm.tenant_id=?{$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);
$totalConvsAll = (int)($totalConvsQuery['cnt'] ?? 0);
$sacPages      = max(1, (int)ceil($totalConvsAll / $sacPerPage));

$conversations = db_all(
    "SELECT sm.order_id, o.meli_order_id, o.buyer_nickname, o.has_mediacao,
            COALESCE(sc.status, 'OPEN') as conv_status,
            COUNT(CASE WHEN sm.is_read=0 AND sm.from_role='BUYER' THEN 1 END) as unread,
            MAX(sm.created_at) as last_at,
            (SELECT message_text FROM sac_messages WHERE order_id=sm.order_id ORDER BY created_at DESC LIMIT 1) as last_msg
     FROM sac_messages sm
     JOIN orders o ON o.id=sm.order_id
     LEFT JOIN sac_conversations sc ON sc.order_id=sm.order_id
     WHERE sm.tenant_id=?{$acctSql}
     GROUP BY sm.order_id
     ORDER BY
       (COUNT(CASE WHEN sm.is_read=0 AND sm.from_role='BUYER' THEN 1 END)) DESC,
       last_at DESC
     LIMIT {$sacPerPage} OFFSET {$sacOffset}",
    array_merge([$tenantId], (array)$acctP)
);

$title = 'SAC — Inbox';
include __DIR__ . '/layout.php';
?>

<?php if ($sacTab === 'inbox'): ?>
<div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;background:#1A1A1C">

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px">
    <!-- Abertas -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:10px;padding:12px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">🔴 Abertas</div>
      <div id="sac-kpi-open" style="font-size:22px;font-weight:600;color:#ef4444"><?= (int)($openConvs['cnt'] ?? 0) ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">aguardam resposta</div>
    </div>
    <!-- Aguardando -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-top:3px solid #f59e0b;border-radius:10px;padding:12px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">⏳ Aguardando</div>
      <div id="sac-kpi-waiting" style="font-size:22px;font-weight:600;color:#f59e0b"><?= (int)($waitConvs['cnt'] ?? 0) ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">retorno do cliente</div>
    </div>
    <!-- Resolvidas -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:10px;padding:12px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">✅ Resolvidas</div>
      <div id="sac-kpi-resolved" style="font-size:22px;font-weight:600;color:#22c55e"><?= (int)($resolvedConvs['cnt'] ?? 0) ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">neste período</div>
    </div>
    <!-- Não lidas -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:10px;padding:12px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">💬 Não lidas</div>
      <div id="sac-kpi-unread" style="font-size:22px;font-weight:600;color:#3483FA"><?= (int)($unreadTotal['cnt'] ?? 0) ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">mensagens novas</div>
    </div>
    <!-- Mediações -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-top:3px solid #a855f7;border-radius:10px;padding:12px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">⚠️ Mediações</div>
      <div id="sac-kpi-mediacao" style="font-size:22px;font-weight:600;color:#a855f7"><?= (int)($mediacoTotal['cnt'] ?? 0) ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">ativas agora</div>
    </div>
  </div>

  <!-- Gráficos -->
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px">

    <!-- Mensagens recebidas por dia -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-radius:10px;padding:14px">
      <div style="font-size:11px;font-weight:500;color:#E8E8E6;margin-bottom:10px">Mensagens recebidas — últimos 7 dias</div>
      <canvas id="sacLineChart" style="height:70px!important;max-height:70px"></canvas>
    </div>

    <!-- Status donut -->
    <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-radius:10px;padding:14px;display:flex;align-items:center;gap:16px">
      <canvas id="sacDonutChart" style="height:80px!important;max-height:80px;width:80px!important;max-width:80px;flex-shrink:0"></canvas>
      <div style="flex:1;display:flex;flex-direction:column;gap:6px">
        <?php
        $statusCounts = [
          ['🔴', 'Abertas',    (int)($openConvs['cnt']     ?? 0), '#ef4444'],
          ['⏳', 'Aguardando', (int)($waitConvs['cnt']     ?? 0), '#f59e0b'],
          ['✅', 'Resolvidas', (int)($resolvedConvs['cnt'] ?? 0), '#22c55e'],
        ];
        foreach ($statusCounts as [$icon, $label, $cnt, $color]):
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px">
          <div style="display:flex;align-items:center;gap:5px">
            <div style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>"></div>
            <span style="color:#9A9A96"><?= $label ?></span>
          </div>
          <span style="color:#E8E8E6;font-weight:600"><?= $cnt ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Inbox principal -->
<div id="sac-inbox" style="display:flex;height:calc(100dvh - 52px - 220px);min-height:300px">

  <!-- Coluna 1: Lista -->
  <div id="sac-col1" class="sac-col1" style="width:240px;border-right:0.5px solid #2E2E33;display:flex;flex-direction:column;flex-shrink:0">
    <div style="padding:8px 10px;border-bottom:0.5px solid #2E2E33;display:flex;gap:4px">
      <?php
      $filters = [
          'ALL'      => ['Todas',      '#E8E8E6', 'transparent',            '#2E2E33'],
          'OPEN'     => ['Aberto',     '#3483FA',  'rgba(52,131,250,.12)',  '#3483FA'],
          'WAITING'  => ['Aguardando', '#f59e0b',  'rgba(245,158,11,.12)', '#f59e0b'],
          'RESOLVED' => ['Resolvido',  '#22c55e',  'rgba(34,197,94,.12)',  '#22c55e'],
      ];
      foreach ($filters as $k => [$label, $color, $bg, $border]):
        $isActive = $k === 'ALL';
      ?>
      <button onclick="filterConvs('<?= $k ?>')" id="filter-<?= $k ?>"
        style="flex:1;padding:5px 4px;font-size:9px;font-weight:600;border-radius:6px;cursor:pointer;transition:all .12s;
               border:0.5px solid <?= $isActive ? '#3E3E45' : $border ?>;
               background:<?= $isActive ? '#252528' : 'transparent' ?>;
               color:<?= $isActive ? '#E8E8E6' : $color ?>">
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <div style="flex:1;overflow-y:auto" id="conv-list">
      <?php foreach ($conversations as $c):
        if ($c['has_mediacao'])                    { $urg_color = '#ef4444'; $urg_bg = 'rgba(239,68,68,.06)'; }
        elseif ($c['conv_status'] === 'RESOLVED')  { $urg_color = '#22c55e'; $urg_bg = 'transparent'; }
        elseif ($c['unread'] > 1)                  { $urg_color = '#f59e0b'; $urg_bg = 'rgba(245,158,11,.06)'; }
        elseif ($c['unread'] > 0)                  { $urg_color = '#3483FA'; $urg_bg = 'rgba(52,131,250,.06)'; }
        elseif ($c['conv_status'] === 'WAITING')   { $urg_color = '#f59e0b'; $urg_bg = 'transparent'; }
        else                                       { $urg_color = '#22c55e'; $urg_bg = 'transparent'; }

        // Badges visuais por status
        $statusBadges = [];
        if ($c['has_mediacao']) {
            $statusBadges[] = ['Mediação', '#ef4444', 'rgba(239,68,68,.15)'];
        }
        switch ($c['conv_status']) {
            case 'RESOLVED': $statusBadges[] = ['Resolvido', '#22c55e', 'rgba(34,197,94,.12)']; break;
            case 'WAITING':  $statusBadges[] = ['Aguardando', '#f59e0b', 'rgba(245,158,11,.12)']; break;
            default:
                if (!$c['has_mediacao']) $statusBadges[] = ['Aberto', '#3483FA', 'rgba(52,131,250,.12)'];
                break;
        }
      ?>
      <button onclick="loadConv('<?= $c['order_id'] ?>','<?= htmlspecialchars($c['buyer_nickname'],ENT_QUOTES) ?>','<?= htmlspecialchars($c['meli_order_id'],ENT_QUOTES) ?>')"
        id="conv-<?= $c['order_id'] ?>"
        data-status="<?= htmlspecialchars($c['conv_status']) ?>"
        data-mediacao="<?= $c['has_mediacao'] ? '1' : '0' ?>"
        style="width:100%;text-align:left;padding:10px 14px;border:none;background:<?= $urg_bg ?>;border-bottom:0.5px solid #2E2E33;cursor:pointer;transition:all .15s;border-left:3px solid <?= $urg_color ?>">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
          <div style="width:20px;height:20px;border-radius:50%;background:#252528;display:flex;align-items:center;justify-content:center;font-size:9px;color:#9A9A96;flex-shrink:0">
            <?= strtoupper(mb_substr($c['buyer_nickname'],0,1)) ?>
          </div>
          <span style="font-size:11px;font-weight:500;color:#E8E8E6;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($c['buyer_nickname']) ?></span>
          <?php if ($c['unread'] > 0): ?>
          <span style="min-width:16px;height:16px;padding:0 4px;background:#3483FA;border-radius:8px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <?= $c['unread'] > 9 ? '9+' : $c['unread'] ?>
          </span>
          <?php endif; ?>
        </div>
        <div style="font-size:10px;color:#5E5E5A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:5px">
          <?= htmlspecialchars(mb_substr($c['last_msg'] ?? '',0,42)) ?>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:9px;color:#5E5E5A"><?= date('d/m H:i',strtotime($c['last_at'])) ?></span>
          <div style="display:flex;align-items:center;gap:3px" class="conv-badges">
            <?php foreach ($statusBadges as [$label, $color, $bg]): ?>
            <span class="conv-status-icon" style="font-size:8px;font-weight:600;padding:1px 6px;border-radius:8px;background:<?= $bg ?>;color:<?= $color ?>;line-height:1.6;white-space:nowrap">
              <?= $label ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
      </button>
      <?php endforeach; ?>
      <?php if (empty($conversations)): ?>
      <div style="padding:24px;text-align:center;font-size:12px;color:#5E5E5A">Nenhuma mensagem</div>
      <?php endif; ?>

      <!-- Paginação -->
      <?php if ($sacPages > 1): ?>
      <div style="padding:8px 10px;border-top:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
        <span style="font-size:10px;color:#5E5E5A"><?= $totalConvsAll ?> conversas</span>
        <div style="display:flex;gap:3px">
          <?php if ($sacPage > 1): ?>
          <a href="?p=<?= $sacPage-1 ?>" style="padding:3px 8px;border-radius:5px;border:0.5px solid #2E2E33;background:#252528;color:#9A9A96;text-decoration:none;font-size:11px">←</a>
          <?php endif; ?>
          <span style="padding:3px 8px;border-radius:5px;border:0.5px solid #3483FA;background:rgba(52,131,250,.1);color:#3483FA;font-size:11px"><?= $sacPage ?>/<?= $sacPages ?></span>
          <?php if ($sacPage < $sacPages): ?>
          <a href="?p=<?= $sacPage+1 ?>" style="padding:3px 8px;border-radius:5px;border:0.5px solid #2E2E33;background:#252528;color:#9A9A96;text-decoration:none;font-size:11px">→</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Coluna 2: Chat -->
  <div id="sac-col2" class="sac-col2" style="flex:1;display:flex;flex-direction:column;min-width:0">
    <div id="chat-header" style="padding:10px 16px;border-bottom:0.5px solid #2E2E33;background:#1A1A1C;display:flex;align-items:center;gap:8px;flex-shrink:0">
      <button id="sac-back-btn" onclick="sacShowList()" style="display:none;padding:5px;border:none;background:none;color:#9A9A96;cursor:pointer;flex-shrink:0">
        <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
      </button>
      <i data-lucide="message-square" style="width:14px;height:14px;color:#5E5E5A"></i>
      <span id="chat-title" style="font-size:13px;color:#5E5E5A;flex:1">Selecione uma conversa</span>
      <div id="status-btns" style="display:none;align-items:center;gap:6px">
        <button onclick="setStatus('OPEN')" id="sbtn-OPEN" style="padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;border:0.5px solid #ef4444;background:rgba(239,68,68,.1);color:#ef4444">🔴 Aberto</button>
        <button onclick="setStatus('WAITING')" id="sbtn-WAITING" style="padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;border:0.5px solid #2E2E33;background:transparent;color:#5E5E5A">⏳ Aguardando</button>
        <button onclick="setStatus('RESOLVED')" id="sbtn-RESOLVED" style="padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;border:0.5px solid #2E2E33;background:transparent;color:#5E5E5A">✅ Resolvido</button>
      </div>
    </div>
    <div id="messages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;min-height:0">
      <div style="text-align:center;font-size:12px;color:#5E5E5A;margin-top:40px">Selecione uma conversa para começar</div>
    </div>
    <div id="sac-reply-bar" style="padding:12px 14px;border-top:0.5px solid #2E2E33;background:#1A1A1C;flex-shrink:0;z-index:10">
      <!-- Campo de texto -->
      <div style="display:flex;gap:8px;margin-bottom:8px">
        <input id="msg-input" type="text" placeholder="Digite sua resposta ao comprador..."
          onkeydown="if(event.key==='Enter')sendMsg()"
          style="flex:1;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none;min-height:42px">
      </div>
      <!-- Botões de ação -->
      <div style="display:flex;gap:8px">
        <button onclick="showQuickReplies()" id="qr-btn"
          style="padding:10px 12px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:5px;white-space:nowrap">
          <i data-lucide="message-square-reply" style="width:13px;height:13px"></i> Prontas
        </button>
        <button onclick="getAI()" id="ai-btn"
          style="flex:1;padding:10px;background:rgba(255,230,0,.1);border:0.5px solid #FFE600;color:#FFE600;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;min-height:42px">
          <i data-lucide="sparkles" style="width:14px;height:14px"></i> Sugerir com IA
        </button>
        <button onclick="sendMsg()" class="btn-primary"
          style="flex:2;padding:10px;font-size:13px;font-weight:600;min-height:42px;display:flex;align-items:center;justify-content:center;gap:6px">
          <i data-lucide="send" style="width:14px;height:14px"></i> Enviar resposta
        </button>
      </div>
    </div>
  </div>

  <!-- Coluna 3: Contexto -->
  <div id="order-ctx" style="width:190px;border-left:0.5px solid #2E2E33;display:none;overflow-y:auto;flex-shrink:0">
    <div style="padding:10px 14px;border-bottom:0.5px solid #2E2E33">
      <span style="font-size:10px;font-weight:500;color:#5E5E5A;text-transform:uppercase;letter-spacing:.6px">Pedido</span>
    </div>
    <div id="order-ctx-body" style="padding:12px"></div>
  </div>
</div>

<!-- Modal respostas prontas -->
<div id="qr-modal" style="display:none" class="modal-bg">
  <div class="modal-box" style="max-width:460px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:8px">
        <i data-lucide="message-square-reply" style="width:14px;height:14px;color:#3483FA"></i>
        <span style="font-size:14px;font-weight:500;color:#E8E8E6">Respostas prontas</span>
      </div>
      <a href="/pages/respostas.php" target="_blank" style="font-size:10px;color:#5E5E5A;text-decoration:none">Gerenciar →</a>
    </div>
    <input type="text" id="qr-search" placeholder="Buscar resposta..."
      oninput="filterQuickReplies(this.value)"
      style="width:100%;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box;margin-bottom:10px">
    <div id="qr-list" style="max-height:300px;overflow-y:auto;display:flex;flex-direction:column;gap:6px"></div>
    <button onclick="document.getElementById('qr-modal').style.display='none'" class="btn-secondary" style="width:100%;justify-content:center;margin-top:10px">Fechar</button>
  </div>
</div>

<!-- Modal IA -->
<div id="ai-modal" style="display:none" class="modal-bg">
  <div class="modal-box" style="max-width:400px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
      <i data-lucide="sparkles" style="width:14px;height:14px;color:#FFE600"></i>
      <span style="font-size:14px;font-weight:500;color:#E8E8E6">Sugestão de resposta — IA</span>
    </div>
    <div id="ai-text" style="font-size:13px;color:#9A9A96;line-height:1.6;padding:12px;background:#252528;border-radius:8px;margin-bottom:16px"></div>
    <div style="display:flex;gap:8px">
      <button onclick="useAI()" class="btn-primary" style="flex:1">Usar esta resposta</button>
      <button onclick="document.getElementById('ai-modal').style.display='none'" class="btn-secondary">Fechar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

Chart.defaults.color = '#5E5E5A';
Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
Chart.defaults.font.size = 10;

// Gráfico linha — mensagens por dia
let sacLineChart, sacDonutChart;

function buildSacDays(weekData) {
  const days=[], msgs=[];
  for (let i=6;i>=0;i--) {
    const d=new Date(); d.setDate(d.getDate()-i);
    const key=d.toISOString().split('T')[0];
    const found=weekData.find(r=>r.day===key);
    days.push(d.toLocaleDateString('pt-BR',{weekday:'short',day:'numeric'}));
    msgs.push(found?parseInt(found.cnt):0);
  }
  return {days,msgs};
}

const initialWeek = <?= json_encode(array_values($weekHistory)) ?>;
const {days, msgs} = buildSacDays(initialWeek);

sacLineChart = registerChart("sac-line", new Chart(document.getElementById('sacLineChart'), {
  type: 'line',
  data: {
    labels: days,
    datasets: [{
      data: msgs,
      borderColor: '#3483FA',
      backgroundColor: (ctx) => {
        const g = ctx.chart.ctx.createLinearGradient(0,0,0,80);
        g.addColorStop(0,'rgba(52,131,250,0.3)');
        g.addColorStop(1,'rgba(52,131,250,0)');
        return g;
      },
      borderWidth: 2, fill: true, tension: 0.4,
      pointBackgroundColor: '#3483FA', pointBorderColor: '#0F0F10',
      pointBorderWidth: 2, pointRadius: 3,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend:{display:false}, tooltip:{backgroundColor:'#252528',borderColor:'#2E2E33',borderWidth:1,titleColor:'#E8E8E6',bodyColor:'#9A9A96'} },
    scales: {
      x: { grid:{color:'#2E2E3322'}, ticks:{color:'#5E5E5A',font:{size:9}} },
      y: { grid:{color:'#2E2E3322'}, ticks:{color:'#5E5E5A',font:{size:9},stepSize:1} }
    }
  }
}));

// Gráfico donut — status
sacDonutChart = registerChart("sac-donut", new Chart(document.getElementById('sacDonutChart'), {
  type: 'doughnut',
  data: {
    labels: ['Abertas','Aguardando','Resolvidas'],
    datasets: [{
      data: [<?= (int)($openConvs['cnt']??0) ?>, <?= (int)($waitConvs['cnt']??0) ?>, <?= (int)($resolvedConvs['cnt']??0) ?>],
      backgroundColor: ['#ef4444','#f59e0b','#22c55e'],
      borderColor: '#0F0F10', borderWidth: 3
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false, cutout: '65%',
    plugins: {
      legend: {display:false},
      tooltip: {backgroundColor:'#252528',borderColor:'#2E2E33',borderWidth:1,titleColor:'#E8E8E6',bodyColor:'#9A9A96'}
    }
  }
}));

// Refresh SAC charts
async function refreshSacCharts() {
  try {
    const r = await fetch('/api/sac_data.php');
    const d = await r.json();
    const {days,msgs} = buildSacDays(d.week_history);
    sacLineChart.data.labels = days;
    sacLineChart.data.datasets[0].data = msgs;
    sacLineChart.update('none');
    sacDonutChart.data.datasets[0].data = [d.kpis.open, d.kpis.waiting, d.kpis.resolved];
    sacDonutChart.update('none');
    // Update KPIs
    const kpiMap = {open:'#ef4444',waiting:'#f59e0b',resolved:'#22c55e',unread:'#3483FA',mediacao:'#a855f7'};
    document.querySelectorAll('[id^="sac-kpi-"]').forEach(el => {
      const key = el.id.replace('sac-kpi-','');
      if (d.kpis[key] !== undefined) el.textContent = d.kpis[key];
    });
  } catch(e) {}
}
setInterval(refreshSacCharts, 30000);

// ── Lógica do chat ───────────────────────────────────────
let currentOrderId = null, currentConvStatus = 'OPEN';

// ── SAC Mobile Navigation ────────────────────────────────
function sacShowChat() {
  if (window.innerWidth > 768) return;
  const col1 = document.querySelector('.sac-col1');
  const col2 = document.querySelector('.sac-col2');
  if (col1) col1.classList.add('slide-out');
  if (col2) col2.classList.add('slide-in');
  const backBtn = document.getElementById('sac-back-btn');
  if (backBtn) backBtn.style.display = 'flex';
}

function sacShowList() {
  if (window.innerWidth > 768) return;
  const col1 = document.querySelector('.sac-col1');
  const col2 = document.querySelector('.sac-col2');
  if (col1) col1.classList.remove('slide-out');
  if (col2) col2.classList.remove('slide-in');
  const backBtn = document.getElementById('sac-back-btn');
  if (backBtn) backBtn.style.display = 'none';
}

function filterConvs(status) {
  const colorMap = {
    ALL:      { bg:'#252528', color:'#E8E8E6',  border:'#3E3E45' },
    OPEN:     { bg:'rgba(52,131,250,.12)',  color:'#3483FA', border:'#3483FA' },
    WAITING:  { bg:'rgba(245,158,11,.12)', color:'#f59e0b', border:'#f59e0b' },
    RESOLVED: { bg:'rgba(34,197,94,.12)',  color:'#22c55e', border:'#22c55e' },
  };
  document.querySelectorAll('[id^="filter-"]').forEach(btn => {
    const k   = btn.id.replace('filter-','');
    const map = colorMap[k] || colorMap.ALL;
    const isActive = k === status;
    btn.style.background = isActive ? map.bg    : 'transparent';
    btn.style.color      = isActive ? map.color : '#5E5E5A';
    btn.style.border     = isActive ? `0.5px solid ${map.border}` : '0.5px solid #2E2E33';
  });
  document.querySelectorAll('#conv-list button[data-status]').forEach(btn => {
    btn.style.display = (status==='ALL' || btn.dataset.status===status) ? 'block' : 'none';
  });
}

async function loadConv(orderId, buyer, meliId) {
  sacShowChat(); // Mobile: desliza para o chat
  currentOrderId = orderId;
  document.querySelectorAll('[id^="conv-"]').forEach(el => el.style.fontWeight = 'normal');
  const btn = document.getElementById('conv-'+orderId);
  if (btn) btn.style.fontWeight = '600';
  document.getElementById('chat-title').innerHTML = `<strong style="color:#E8E8E6">${buyer}</strong> <span style="color:#5E5E5A;font-size:11px">· ${meliId}</span>`;
  document.getElementById('status-btns').style.display = 'flex';

  const fd = new FormData();
  fd.append('action','messages'); fd.append('order_id',orderId);
  const r = await fetch('/pages/sac.php',{method:'POST',body:fd});
  const d = await r.json();

  currentConvStatus = d.conv_status || 'OPEN';
  updateStatusBtns(currentConvStatus);

  const container = document.getElementById('messages');
  container.innerHTML = '';
  (d.messages||[]).forEach(m => {
    const isSeller = m.from_role === 'SELLER';
    const div = document.createElement('div');
    div.style.cssText = `display:flex;justify-content:${isSeller?'flex-end':'flex-start'}`;
    div.innerHTML = `<div style="max-width:72%;display:flex;flex-direction:column;align-items:${isSeller?'flex-end':'flex-start'};gap:3px">
      <span style="font-size:10px;color:#5E5E5A">${isSeller?'Você':m.from_nickname} · ${new Date(m.created_at).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</span>
      <div style="padding:9px 13px;border-radius:12px;${isSeller?'border-bottom-right-radius:3px;background:#3483FA;color:#fff':'border-bottom-left-radius:3px;background:#252528;color:#E8E8E6'};font-size:13px;line-height:1.5">${m.message_text}</div>
    </div>`;
    container.appendChild(div);
  });
  container.scrollTop = container.scrollHeight;

  if (d.order) {
    const o = d.order;
    document.getElementById('order-ctx').style.display = 'block';
    document.getElementById('order-ctx-body').innerHTML = `
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:4px">Pedido</div>
      <div style="font-family:monospace;font-size:11px;color:#9A9A96;margin-bottom:10px">${o.meli_order_id}</div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:4px">Total</div>
      <div style="font-size:15px;font-weight:500;color:#E8E8E6;margin-bottom:10px">R$ ${parseFloat(o.total_amount).toFixed(2).replace('.',',')}</div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:6px">Status</div>
      <div id="ctx-status" style="font-size:12px;font-weight:500"></div>
      ${o.has_mediacao?'<div style="margin-top:10px"><span class="badge badge-red">⚠ Mediação</span></div>':''}
      <div style="margin-top:14px;padding-top:14px;border-top:0.5px solid #2E2E33">
        <a href="/pages/crm.php" onclick="sessionStorage.setItem('crm_open','${buyer}')"
          style="display:flex;align-items:center;gap:6px;padding:7px 10px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:8px;font-size:11px;font-weight:600;text-decoration:none;transition:all .15s"
          onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
          <i data-lucide="user-circle" style="width:12px;height:12px"></i> Ver perfil CRM
        </a>
      </div>`;
    updateCtxStatus(currentConvStatus);
    lucide.createIcons();
  }
}

function updateStatusBtns(status) {
  const cfg = { 'OPEN':{border:'#ef4444',bg:'rgba(239,68,68,.1)',color:'#ef4444'}, 'WAITING':{border:'#f59e0b',bg:'rgba(245,158,11,.1)',color:'#f59e0b'}, 'RESOLVED':{border:'#22c55e',bg:'rgba(34,197,94,.1)',color:'#22c55e'} };
  ['OPEN','WAITING','RESOLVED'].forEach(s => {
    const btn = document.getElementById('sbtn-'+s);
    if (!btn) return;
    if (s===status) { btn.style.background=cfg[s].bg; btn.style.borderColor=cfg[s].border; btn.style.color=cfg[s].color; btn.style.fontWeight='600'; }
    else { btn.style.background='transparent'; btn.style.borderColor='#2E2E33'; btn.style.color='#5E5E5A'; btn.style.fontWeight='400'; }
  });
}

function updateCtxStatus(status) {
  const el = document.getElementById('ctx-status');
  if (!el) return;
  const labels = {'OPEN':'🔴 Aberto','WAITING':'⏳ Aguardando','RESOLVED':'✅ Resolvido'};
  const colors = {'OPEN':'#ef4444','WAITING':'#f59e0b','RESOLVED':'#22c55e'};
  el.textContent = labels[status]||status;
  el.style.color = colors[status]||'#9A9A96';
}

async function setStatus(newStatus) {
  if (!currentOrderId) return;
  const fd = new FormData();
  fd.append('action','set_status'); fd.append('order_id',currentOrderId); fd.append('status',newStatus);
  const r = await fetch('/pages/sac.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) {
    currentConvStatus = newStatus;
    updateStatusBtns(newStatus);
    updateCtxStatus(newStatus);

    // Atualiza card na lista sem reload
    const card = document.getElementById('conv-'+currentOrderId);
    if (card) {
      card.dataset.status = newStatus;

      // Cores da borda e fundo
      const borderColors = { OPEN:'#3483FA', WAITING:'#f59e0b', RESOLVED:'#22c55e' };
      const bgColors     = { OPEN:'transparent', WAITING:'transparent', RESOLVED:'transparent' };
      card.style.borderLeftColor = borderColors[newStatus] || '#22c55e';
      card.style.background      = bgColors[newStatus] || 'transparent';

      // Troca os badges
      const badgesEl = card.querySelector('.conv-badges');
      if (badgesEl) {
        const hasMediacao = card.dataset.mediacao === '1';
        const badgeMap = {
          OPEN:     { label:'Aberto',     color:'#3483FA', bg:'rgba(52,131,250,.12)' },
          WAITING:  { label:'Aguardando', color:'#f59e0b', bg:'rgba(245,158,11,.12)' },
          RESOLVED: { label:'Resolvido',  color:'#22c55e', bg:'rgba(34,197,94,.12)' },
        };
        const b = badgeMap[newStatus] || badgeMap.OPEN;
        let html = '';
        if (hasMediacao) {
          html += `<span class="conv-status-icon" style="font-size:8px;font-weight:600;padding:1px 6px;border-radius:8px;background:rgba(239,68,68,.15);color:#ef4444;line-height:1.6">Mediação</span>`;
        }
        html += `<span class="conv-status-icon" style="font-size:8px;font-weight:600;padding:1px 6px;border-radius:8px;background:${b.bg};color:${b.color};line-height:1.6">${b.label}</span>`;
        badgesEl.innerHTML = html;
      }

      // Remove contador de não lidos quando resolve
      if (newStatus === 'RESOLVED') {
        const dot = card.querySelector('.unread-dot, span[style*="background:#3483FA"]');
        if (dot) dot.remove();
      }
    }
    toast(newStatus==='RESOLVED'?'Conversa resolvida!':'Status atualizado','success');
    refreshCharts();
  }
}

async function sendMsg() {
  const input = document.getElementById('msg-input');
  const text = input.value.trim();
  if (!text || !currentOrderId) return;
  input.value = '';
  const fd = new FormData();
  fd.append('action','send'); fd.append('order_id',currentOrderId); fd.append('text',text);
  await fetch('/pages/sac.php',{method:'POST',body:fd});
  const container = document.getElementById('messages');
  const div = document.createElement('div');
  div.style.cssText = 'display:flex;justify-content:flex-end';
  div.innerHTML = `<div style="max-width:72%;display:flex;flex-direction:column;align-items:flex-end;gap:3px"><span style="font-size:10px;color:#5E5E5A">Você · agora</span><div style="padding:9px 13px;border-radius:12px;border-bottom-right-radius:3px;background:#3483FA;color:#fff;font-size:13px;line-height:1.5">${text}</div></div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
  if (currentConvStatus==='OPEN') { currentConvStatus='WAITING'; updateStatusBtns('WAITING'); }
}

async function getAI() {
  if (!currentOrderId) return;
  const msgs = document.getElementById('messages').querySelectorAll('div[style*="flex-start"]');
  const lastMsg = msgs[msgs.length-1];
  const text = lastMsg?.querySelector('div[style*="background:#252528"]')?.textContent||'';
  document.getElementById('ai-btn').innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i>';
  lucide.createIcons();
  const fd = new FormData();
  fd.append('action','ai'); fd.append('text',text||'Mensagem do comprador');
  const r = await fetch('/pages/sac.php',{method:'POST',body:fd});
  const d = await r.json();
  document.getElementById('ai-btn').innerHTML = '<i data-lucide="sparkles" style="width:12px;height:12px"></i> IA';
  lucide.createIcons();
  document.getElementById('ai-text').textContent = d.suggestion;
  document.getElementById('ai-modal').style.display = 'flex';
}

function useAI() {
  document.getElementById('msg-input').value = document.getElementById('ai-text').textContent;
  document.getElementById('ai-modal').style.display = 'none';
}

// ── Respostas prontas ─────────────────────────────────────
let quickRepliesCache = [];

async function showQuickReplies() {
  document.getElementById('qr-modal').style.display = 'flex';
  document.getElementById('qr-search').value = '';
  if (!quickRepliesCache.length) await loadQuickReplies();
  renderQuickReplies(quickRepliesCache);
}

async function loadQuickReplies() {
  const r = await fetch('/api/quick_replies.php?context=sac');
  const d = await r.json();
  quickRepliesCache = d.replies || [];
}

function filterQuickReplies(q) {
  const filtered = quickRepliesCache.filter(r =>
    r.title.toLowerCase().includes(q.toLowerCase()) ||
    r.body.toLowerCase().includes(q.toLowerCase()) ||
    (r.tags||'').toLowerCase().includes(q.toLowerCase())
  );
  renderQuickReplies(filtered);
}

function renderQuickReplies(replies) {
  const el = document.getElementById('qr-list');
  if (!replies.length) {
    el.innerHTML = '<div style="text-align:center;padding:20px;color:#5E5E5A;font-size:12px">Nenhuma resposta encontrada</div>';
    return;
  }
  el.innerHTML = replies.map(r => `
    <div onclick="useQuickReply('${r.id}',\`${r.body.replace(/`/g,"'")}\`)"
      style="padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;cursor:pointer;transition:border-color .15s"
      onmouseover="this.style.borderColor='#3483FA'" onmouseout="this.style.borderColor='#2E2E33'">
      <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:3px">${r.title}</div>
      <div style="font-size:11px;color:#5E5E5A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.body}</div>
    </div>
  `).join('');
}

async function useQuickReply(id, body) {
  document.getElementById('msg-input').value = body;
  document.getElementById('qr-modal').style.display = 'none';
  // Incrementa contador de uso
  const fd = new FormData(); fd.append('action','use'); fd.append('id',id);
  fetch('/pages/respostas.php', {method:'POST', body:fd});
}
</script>
<script>
window.PAGE_DATA_API = '/api/sac_data.php';

window.onChartsData = function(d) {
  if (!d.kpis) return;
  const ids = {open:'sac-kpi-open',waiting:'sac-kpi-waiting',resolved:'sac-kpi-resolved',unread:'sac-kpi-unread',mediacao:'sac-kpi-mediacao'};
  Object.entries(ids).forEach(([k,id]) => {
    const el = document.getElementById(id);
    if (el && d.kpis[k] !== undefined) el.textContent = d.kpis[k];
  });
  // Atualiza gráfico linha
  if (d.week_history && window.Charts['sac-line']) {
    const days=[],msgs=[];
    for (let i=6;i>=0;i--) {
      const dt=new Date(); dt.setDate(dt.getDate()-i);
      const key=dt.toISOString().split('T')[0];
      const found=d.week_history.find(r=>r.day===key);
      days.push(dt.toLocaleDateString('pt-BR',{weekday:'short',day:'numeric'}));
      msgs.push(found?parseInt(found.cnt):0);
    }
    updateChartData('sac-line', days, [msgs]);
  }
  // Atualiza donut
  if (window.Charts['sac-donut']) {
    updateChartData('sac-donut', null, [[d.kpis.open, d.kpis.waiting, d.kpis.resolved]]);
  }
};
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php endif; // fim inbox ?>

<!-- ═══════════════════════════════════════════════════════
     ABA: RECLAMAÇÕES
═══════════════════════════════════════════════════════ -->
<?php if ($sacTab === 'reclamacoes'): ?>
<div style="padding:20px">

  <!-- Header + filtros de status -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div>
      <h2 style="font-size:15px;font-weight:500;color:#E8E8E6">Gestão de Reclamações</h2>
      <p style="font-size:11px;color:#5E5E5A;margin-top:2px">Acompanhe prazos, responda e resolva casos de mediação</p>
    </div>
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
      <div style="display:flex;gap:4px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;padding:3px">
        <?php foreach (['opened'=>['Abertas','#ef4444'],'in_process'=>['Em andamento','#f59e0b'],'closed'=>['Encerradas','#22c55e']] as $s=>[$l,$c]): ?>
        <button onclick="setClaimsFilter('<?= $s ?>')" id="claims-filter-<?= $s ?>"
          style="padding:5px 12px;border:none;border-radius:6px;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;
            background:<?= $s==='opened'?'#ef4444':'transparent' ?>;color:<?= $s==='opened'?'#fff':'#5E5E5A' ?>">
          <?= $l ?>
        </button>
        <?php endforeach; ?>
      </div>
      <button onclick="loadClaims()" class="btn-secondary" style="font-size:12px;padding:7px 12px">
        <i data-lucide="refresh-cw" style="width:12px;height:12px"></i>
      </button>
    </div>
  </div>

  <!-- KPIs rápidos -->
  <div id="claims-kpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A">Abertas</div>
      <div id="ck-abertas" style="font-size:22px;font-weight:700;color:#ef4444">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #f97316;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A">Prazo ≤ 3 dias</div>
      <div id="ck-urgentes" style="font-size:22px;font-weight:700;color:#f97316">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #f59e0b;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A">Em andamento</div>
      <div id="ck-andamento" style="font-size:22px;font-weight:700;color:#f59e0b">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A">Resolvidas</div>
      <div id="ck-resolvidas" style="font-size:22px;font-weight:700;color:#22c55e">—</div>
    </div>
  </div>

  <div id="claims-loading" style="text-align:center;padding:48px;color:#5E5E5A;font-size:13px">
    <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    Buscando reclamações no ML...
  </div>
  <div id="claims-content" style="display:none"></div>
</div>

<!-- Modal de detalhes da reclamação -->
<div id="claim-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:center;justify-content:center;z-index:500;padding:16px;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:680px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6)">
    <!-- Header -->
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px;flex-shrink:0">
      <i data-lucide="alert-triangle" style="width:16px;height:16px;color:#ef4444"></i>
      <span id="claim-modal-title" style="font-size:14px;font-weight:600;color:#E8E8E6">Reclamação</span>
      <button onclick="closeClaimModal()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px">✕</button>
    </div>

    <div style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:16px">

      <!-- Info da reclamação -->
      <div id="claim-detail-info"></div>

      <!-- Histórico de mensagens -->
      <div>
        <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Histórico</div>
        <div id="claim-messages" style="display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;padding-right:4px"></div>
      </div>

      <!-- Nota interna -->
      <div style="background:#252528;border-radius:10px;padding:14px">
        <div style="font-size:11px;font-weight:600;color:#f59e0b;margin-bottom:8px;display:flex;align-items:center;gap:5px">
          <i data-lucide="sticky-note" style="width:12px;height:12px"></i> Nota interna (visível só para operadores)
        </div>
        <div style="display:flex;gap:8px;margin-bottom:8px">
          <select id="claim-status-select" style="padding:7px 10px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:11px;outline:none">
            <option value="aberta">Aberta</option>
            <option value="em_andamento">Em andamento</option>
            <option value="aguardando_comprador">Aguardando comprador</option>
            <option value="aguardando_ml">Aguardando ML</option>
            <option value="resolvida">Resolvida</option>
          </select>
        </div>
        <textarea id="claim-note-text" placeholder="Registre aqui o que foi feito, o combinado com o comprador, próximos passos..."
          style="width:100%;height:80px;padding:9px 12px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;resize:none;box-sizing:border-box;line-height:1.5"></textarea>
        <button onclick="saveClaimNote()" style="margin-top:8px;padding:6px 14px;background:rgba(245,158,11,.15);border:0.5px solid #f59e0b;color:#f59e0b;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">
          <i data-lucide="save" style="width:11px;height:11px"></i> Salvar nota
        </button>
      </div>

      <!-- Responder -->
      <div>
        <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Responder ao comprador</div>
        <div style="display:flex;gap:8px;margin-bottom:8px">
          <button onclick="aiSuggestClaim()" style="padding:6px 12px;background:rgba(255,230,0,.1);border:0.5px solid rgba(255,230,0,.3);color:#FFE600;border-radius:7px;font-size:11px;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i data-lucide="sparkles" style="width:11px;height:11px"></i> Sugerir com IA
          </button>
        </div>
        <textarea id="claim-reply-text" placeholder="Digite sua resposta ao comprador..."
          style="width:100%;height:100px;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;resize:none;box-sizing:border-box;line-height:1.5"></textarea>
        <button onclick="sendClaimReply()" id="btn-send-claim" style="margin-top:8px;padding:8px 18px;background:#3483FA;border:none;color:#fff;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px">
          <i data-lucide="send" style="width:12px;height:12px"></i> Enviar resposta no ML
        </button>
      </div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

let currentClaimId   = null;
let currentClaimData = null;
let currentFilter    = 'opened';

const statusMap = {
  opened:           ['Aberta',             '#ef4444', 'rgba(239,68,68,.12)'],
  closed:           ['Encerrada',          '#22c55e', 'rgba(34,197,94,.12)'],
  expired:          ['Expirada',           '#5E5E5A', 'rgba(94,94,90,.12)'],
  in_process:       ['Em andamento',       '#f59e0b', 'rgba(245,158,11,.12)'],
  resolved:         ['Resolvida',          '#22c55e', 'rgba(34,197,94,.12)'],
  under_review:     ['Em revisão',         '#3483FA', 'rgba(52,131,250,.12)'],
  waiting_for_info: ['Aguard. informação', '#f59e0b', 'rgba(245,158,11,.12)'],
};

const reasonMap = {
  'item_not_received':     'Item não recebido',
  'item_not_as_described': 'Item diferente do anunciado',
  'unauthorized_payment':  'Pagamento não autorizado',
  'damaged_item':          'Item avariado',
  'returns_and_refunds':   'Devolução / Reembolso',
  'quality_issues':        'Problema de qualidade',
};

const internalStatusColors = {
  aberta:                ['Aberta',                '#ef4444'],
  em_andamento:          ['Em andamento',          '#f59e0b'],
  aguardando_comprador:  ['Aguard. comprador',     '#3483FA'],
  aguardando_ml:         ['Aguard. ML',            '#a855f7'],
  resolvida:             ['Resolvida',             '#22c55e'],
};

function setClaimsFilter(status) {
  currentFilter = status;
  const colors = {opened:'#ef4444', in_process:'#f59e0b', closed:'#22c55e'};
  Object.keys(colors).forEach(s => {
    const btn = document.getElementById('claims-filter-' + s);
    if (!btn) return;
    btn.style.background = s === status ? colors[s] : 'transparent';
    btn.style.color      = s === status ? '#fff'     : '#5E5E5A';
  });
  loadClaims();
}

async function loadClaims() {
  document.getElementById('claims-loading').style.display = 'block';
  document.getElementById('claims-content').style.display = 'none';

  try {
    const r = await fetch(`/api/sac_claims.php?action=list&status=${currentFilter}`);
    const d = await r.json();

    document.getElementById('claims-loading').style.display = 'none';
    const el = document.getElementById('claims-content');
    el.style.display = 'block';

    if (!d.ok || !d.claims?.length) {
      el.innerHTML = `
        <div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
          <i data-lucide="check-circle" style="width:32px;height:32px;color:#22c55e;margin:0 auto 12px;display:block"></i>
          <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma reclamação encontrada</div>
          <div style="font-size:11px;color:#5E5E5A">${d.error || 'Ótimo! Nenhum caso aberto nesta categoria.'}</div>
        </div>`;
      lucide.createIcons();
      return;
    }

    // Atualizar KPIs
    let abertas=0, urgentes=0, andamento=0, resolvidas=0;
    d.claims.forEach(c => {
      if (c.status==='opened') abertas++;
      if (c.status==='in_process' || c.status==='under_review') andamento++;
      if (c.status==='resolved' || c.status==='closed') resolvidas++;
      if (c.resolution_due_date) {
        const dias = Math.ceil((new Date(c.resolution_due_date) - new Date()) / 86400000);
        if (dias >= 0 && dias <= 3) urgentes++;
      }
    });
    document.getElementById('ck-abertas').textContent    = abertas;
    document.getElementById('ck-urgentes').textContent   = urgentes;
    document.getElementById('ck-andamento').textContent  = andamento;
    document.getElementById('ck-resolvidas').textContent = resolvidas;

    let html = `<div style="display:flex;flex-direction:column;gap:10px">`;

    for (const c of d.claims) {
      const [stLabel, stColor, stBg] = statusMap[c.status] || ['Desconhecido', '#5E5E5A', 'transparent'];
      const reason  = reasonMap[c.reason] || c.reason || 'Não informado';
      const valor   = c.transaction?.amount ? 'R$ ' + parseFloat(c.transaction.amount).toFixed(2).replace('.',',') : '—';
      const prazo   = c.resolution_due_date ? new Date(c.resolution_due_date).toLocaleDateString('pt-BR') : '—';
      const diasR   = c.resolution_due_date ? Math.ceil((new Date(c.resolution_due_date)-new Date())/86400000) : null;
      const urgente = diasR !== null && diasR <= 3;
      const note    = c._note;
      const [iLabel, iColor] = note ? (internalStatusColors[note.status] || ['—','#5E5E5A']) : ['Sem status','#5E5E5A'];

      html += `
        <div style="background:#1A1A1C;border:0.5px solid ${urgente?'#ef4444':'#2E2E33'};border-left:4px solid ${stColor};border-radius:12px;padding:16px;transition:box-shadow .15s"
          onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,.3)'" onmouseout="this.style.boxShadow=''">

          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px">
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                <span style="font-size:11px;font-family:monospace;color:#3483FA">#${c.id}</span>
                <span style="font-size:9px;font-weight:600;padding:2px 8px;border-radius:8px;background:${stBg};color:${stColor}">${stLabel}</span>
                <span style="font-size:9px;font-weight:600;padding:2px 8px;border-radius:8px;background:${iColor}18;color:${iColor}">● ${iLabel}</span>
                ${urgente ? `<span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:8px;background:rgba(239,68,68,.15);color:#ef4444;animation:pulse 2s infinite">⚠ URGENTE — ${diasR}d</span>` : ''}
              </div>
              <div style="font-size:13px;font-weight:600;color:#E8E8E6">${reason}</div>
              <div style="font-size:11px;color:#5E5E5A;margin-top:2px">Comprador: ${c.complainant?.user_id || c.players?.find(p=>p.role==='complainant')?.user_id || '—'}</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-size:18px;font-weight:700;color:${c.status==='resolved'?'#22c55e':'#ef4444'}">${valor}</div>
              <div style="font-size:10px;color:#5E5E5A">em disputa</div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">
            <div style="background:#252528;border-radius:8px;padding:8px 10px">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Pedido ML</div>
              <div style="font-size:11px;color:#E8E8E6;font-family:monospace">${c.resource_id || '—'}</div>
            </div>
            <div style="background:#252528;border-radius:8px;padding:8px 10px">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Aberta em</div>
              <div style="font-size:11px;color:#E8E8E6">${c.date_created ? new Date(c.date_created).toLocaleDateString('pt-BR') : '—'}</div>
            </div>
            <div style="background:${urgente?'rgba(239,68,68,.1)':'#252528'};border:${urgente?'0.5px solid rgba(239,68,68,.3)':'none'};border-radius:8px;padding:8px 10px">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Prazo resposta</div>
              <div style="font-size:11px;color:${urgente?'#ef4444':'#E8E8E6'};font-weight:${urgente?'700':'400'}">${prazo}</div>
            </div>
          </div>

          ${note?.note ? `<div style="background:#0F0F10;border:0.5px solid rgba(245,158,11,.2);border-radius:8px;padding:9px 12px;margin-bottom:12px;font-size:11px;color:#f59e0b;line-height:1.5">
            <i data-lucide="sticky-note" style="width:10px;height:10px;margin-right:4px"></i> ${note.note}
          </div>` : ''}

          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button onclick="openClaimModal('${c.id}', '${reason.replace(/'/g,"\\'")}')"
              style="padding:7px 14px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;color:#3483FA;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s"
              onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
              <i data-lucide="message-square" style="width:11px;height:11px"></i> Responder / Detalhes
            </button>
            <a href="https://www.mercadolivre.com.br/vendas/reclamos/${c.id}" target="_blank"
              style="padding:7px 14px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:8px;font-size:11px;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all .15s"
              onmouseover="this.style.borderColor='#9A9A96';this.style.color='#E8E8E6'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
              <i data-lucide="external-link" style="width:11px;height:11px"></i> Ver no ML
            </a>
          </div>
        </div>`;
    }

    html += `</div>`;
    el.innerHTML = html;
    lucide.createIcons();

  } catch(e) {
    document.getElementById('claims-loading').style.display = 'none';
    document.getElementById('claims-content').innerHTML =
      `<div style="text-align:center;padding:32px;color:#ef4444;font-size:12px">Erro ao buscar reclamações.</div>`;
    document.getElementById('claims-content').style.display = 'block';
  }
}

async function openClaimModal(claimId, reason) {
  currentClaimId = claimId;
  document.getElementById('claim-modal-title').textContent = reason;
  document.getElementById('claim-modal').style.display = 'flex';
  document.getElementById('claim-detail-info').innerHTML =
    '<div style="text-align:center;padding:24px;color:#5E5E5A;font-size:12px"><i data-lucide="loader-2" style="width:16px;height:16px;animation:spin 1s linear infinite;display:block;margin:0 auto 8px"></i>Carregando...</div>';
  document.getElementById('claim-messages').innerHTML = '';
  document.getElementById('claim-note-text').value = '';
  document.getElementById('claim-reply-text').value = '';
  lucide.createIcons();

  const r = await fetch(`/api/sac_claims.php?action=detail&id=${claimId}`);
  const d = await r.json();
  currentClaimData = d;

  // Info da reclamação
  const c = d.claim || {};
  const [stLabel, stColor] = statusMap[c.status] || ['—','#5E5E5A'];
  const valor = c.transaction?.amount ? 'R$ ' + parseFloat(c.transaction.amount).toFixed(2).replace('.',',') : '—';
  document.getElementById('claim-detail-info').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
      <div style="background:#252528;border-radius:8px;padding:10px 12px">
        <div style="font-size:9px;color:#5E5E5A;margin-bottom:3px">Status ML</div>
        <span style="font-size:11px;font-weight:600;color:${stColor}">${stLabel}</span>
      </div>
      <div style="background:#252528;border-radius:8px;padding:10px 12px">
        <div style="font-size:9px;color:#5E5E5A;margin-bottom:3px">Valor em disputa</div>
        <span style="font-size:11px;font-weight:700;color:#ef4444">${valor}</span>
      </div>
      <div style="background:#252528;border-radius:8px;padding:10px 12px">
        <div style="font-size:9px;color:#5E5E5A;margin-bottom:3px">Prazo</div>
        <span style="font-size:11px;color:#E8E8E6">${c.resolution_due_date ? new Date(c.resolution_due_date).toLocaleDateString('pt-BR') : '—'}</span>
      </div>
    </div>`;

  // Mensagens
  const messages = d.messages || [];
  if (messages.length) {
    const msgsHtml = messages.map(m => {
      const isSeller = m.from?.role === 'respondent';
      return `<div style="display:flex;${isSeller?'justify-content:flex-end':''}">
        <div style="max-width:80%;background:${isSeller?'rgba(52,131,250,.15)':'#252528'};border:0.5px solid ${isSeller?'rgba(52,131,250,.3)':'#2E2E33'};border-radius:10px;padding:10px 12px">
          <div style="font-size:9px;color:#5E5E5A;margin-bottom:4px">${isSeller?'Você':'Comprador'} · ${m.date ? new Date(m.date).toLocaleString('pt-BR') : ''}</div>
          <div style="font-size:12px;color:#E8E8E6;line-height:1.5">${m.message || ''}</div>
        </div>
      </div>`;
    }).join('');
    document.getElementById('claim-messages').innerHTML = msgsHtml;
  } else {
    document.getElementById('claim-messages').innerHTML =
      '<div style="text-align:center;color:#5E5E5A;font-size:11px;padding:12px">Sem mensagens ainda</div>';
  }

  // Nota interna
  if (d.note) {
    document.getElementById('claim-note-text').value   = d.note.note || '';
    document.getElementById('claim-status-select').value = d.note.status || 'aberta';
  }

  lucide.createIcons();
}

function closeClaimModal() {
  document.getElementById('claim-modal').style.display = 'none';
  currentClaimId = null;
}

async function saveClaimNote() {
  if (!currentClaimId) return;
  const fd = new FormData();
  fd.append('action',   'save_note');
  fd.append('claim_id', currentClaimId);
  fd.append('note',     document.getElementById('claim-note-text').value);
  fd.append('status',   document.getElementById('claim-status-select').value);
  const r = await fetch('/api/sac_claims.php', {method:'POST', body:fd});
  const d = await r.json();
  d.ok ? toast('Nota salva!', 'success') : toast(d.error||'Erro', 'error');
}

async function aiSuggestClaim() {
  const reason  = document.getElementById('claim-modal-title').textContent;
  const msgs    = currentClaimData?.messages || [];
  const context = msgs.length ? 'Última mensagem: ' + (msgs[msgs.length-1]?.message || '') : '';

  const fd = new FormData();
  fd.append('action',  'ai_suggest');
  fd.append('reason',  reason);
  fd.append('context', context);
  const r = await fetch('/api/sac_claims.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    document.getElementById('claim-reply-text').value = d.suggestion;
    toast('Sugestão gerada!', 'success');
  } else {
    toast(d.error || 'Erro ao gerar sugestão', 'error');
  }
}

async function sendClaimReply() {
  if (!currentClaimId) return;
  const msg = document.getElementById('claim-reply-text').value.trim();
  if (!msg) { toast('Digite uma mensagem', 'error'); return; }

  const btn = document.getElementById('btn-send-claim');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Enviando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('action',   'reply');
  fd.append('claim_id', currentClaimId);
  fd.append('message',  msg);
  const r = await fetch('/api/sac_claims.php', {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok) {
    toast('Resposta enviada ao comprador!', 'success');
    document.getElementById('claim-reply-text').value = '';
    closeClaimModal();
    loadClaims();
  } else {
    toast(d.error || 'Erro ao enviar', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="send" style="width:12px;height:12px"></i> Enviar resposta no ML';
  lucide.createIcons();
}

document.getElementById('claim-modal').addEventListener('click', function(e) {
  if (e.target === this) closeClaimModal();
});

// Carrega ao abrir a aba
loadClaims();
</script>

<?php endif; // fim reclamacoes ?>

<!-- ═══════════════════════════════════════════════════════
     ABA: AVALIAÇÕES
═══════════════════════════════════════════════════════ -->
<?php if ($sacTab === 'avaliacoes'): ?>
<div style="padding:20px" id="avaliacoes-panel">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
      <h2 style="font-size:15px;font-weight:500;color:#E8E8E6">Avaliações</h2>
      <p style="font-size:11px;color:#5E5E5A;margin-top:2px">Reputação e feedbacks dos compradores</p>
    </div>
    <button onclick="loadReviews()" class="btn-secondary" style="font-size:12px;padding:7px 14px;display:flex;align-items:center;gap:6px">
      <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Atualizar
    </button>
  </div>

  <!-- KPI de reputação -->
  <div id="reputation-bar" style="display:none;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px;margin-bottom:16px"></div>

  <!-- Loading -->
  <div id="reviews-loading" style="text-align:center;padding:48px;color:#5E5E5A;font-size:13px">
    <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin-bottom:8px;display:block;margin:0 auto 8px"></i>
    Buscando avaliações no ML...
  </div>

  <!-- Conteúdo -->
  <div id="reviews-content" style="display:none"></div>

</div>

<script>
lucide.createIcons();

async function loadReviews() {
  document.getElementById('reviews-loading').style.display = 'block';
  document.getElementById('reviews-content').style.display = 'none';
  document.getElementById('reputation-bar').style.display = 'none';

  try {
    const r = await fetch('/api/sac_reviews.php');
    const d = await r.json();

    document.getElementById('reviews-loading').style.display = 'none';

    // Barra de reputação
    if (d.reputation) {
      const rep = d.reputation;
      const stars = '★'.repeat(Math.round(rep.rating || 0)) + '☆'.repeat(5 - Math.round(rep.rating || 0));
      const repBar = document.getElementById('reputation-bar');
      repBar.style.display = 'block';
      repBar.innerHTML = `
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
          <div style="text-align:center">
            <div style="font-size:36px;font-weight:700;color:#FFE600">${(rep.rating||0).toFixed(1)}</div>
            <div style="font-size:14px;color:#FFE600;letter-spacing:2px">${stars}</div>
            <div style="font-size:10px;color:#5E5E5A;margin-top:2px">${rep.total || 0} avaliações</div>
          </div>
          <div style="flex:1;min-width:200px">
            ${[5,4,3,2,1].map(n => {
              const cnt = rep.ratings?.[n] || 0;
              const pct = rep.total ? Math.round(cnt/rep.total*100) : 0;
              return `<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <span style="font-size:10px;color:#5E5E5A;width:8px">${n}</span>
                <span style="font-size:10px;color:#FFE600">★</span>
                <div style="flex:1;height:6px;background:#252528;border-radius:3px;overflow:hidden">
                  <div style="height:100%;width:${pct}%;background:#FFE600;border-radius:3px"></div>
                </div>
                <span style="font-size:10px;color:#5E5E5A;width:24px">${cnt}</span>
              </div>`;
            }).join('')}
          </div>
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <div style="text-align:center">
              <div style="font-size:18px;font-weight:700;color:#22c55e">${rep.positive_pct || 0}%</div>
              <div style="font-size:10px;color:#5E5E5A">Positivas</div>
            </div>
            <div style="text-align:center">
              <div style="font-size:18px;font-weight:700;color:#ef4444">${rep.negative_pct || 0}%</div>
              <div style="font-size:10px;color:#5E5E5A">Negativas</div>
            </div>
          </div>
        </div>`;
      lucide.createIcons();
    }

    const el = document.getElementById('reviews-content');
    el.style.display = 'block';

    if (!d.ok || !d.reviews?.length) {
      el.innerHTML = `
        <div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
          <i data-lucide="star" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
          <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma avaliação encontrada</div>
          <div style="font-size:11px;color:#5E5E5A">${d.error || 'As avaliações aparecerão aqui após as primeiras vendas'}</div>
        </div>`;
      lucide.createIcons();
      return;
    }

    const ratingColor = r => r >= 4 ? '#22c55e' : r >= 3 ? '#f59e0b' : '#ef4444';

    let html = `<div style="display:flex;flex-direction:column;gap:10px">`;
    for (const rv of d.reviews) {
      const stars = '★'.repeat(rv.rating||0) + '☆'.repeat(5-(rv.rating||0));
      const col   = ratingColor(rv.rating||0);
      const date  = rv.date_created ? new Date(rv.date_created).toLocaleDateString('pt-BR') : '—';

      html += `
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px" data-review-id="${rv.id}">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px">
            <div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <span style="font-size:16px;color:${col};letter-spacing:1px">${stars}</span>
                <span style="font-size:11px;color:#5E5E5A">${date}</span>
              </div>
              <div style="font-size:12px;color:#9A9A96">
                ${rv.reviewer?.nickname || 'Comprador anônimo'}
                ${rv.order_id ? `<span style="color:#3483FA;font-family:monospace;margin-left:6px">#${rv.order_id}</span>` : ''}
              </div>
            </div>
            ${!rv.reply ? `
            <button onclick="openReplyModal('${rv.id}')"
              style="padding:6px 12px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;color:#3483FA;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;flex-shrink:0;display:flex;align-items:center;gap:4px">
              <i data-lucide="reply" style="width:11px;height:11px"></i> Responder
            </button>` : `
            <span style="font-size:9px;padding:2px 8px;border-radius:8px;background:rgba(34,197,94,.1);color:#22c55e;flex-shrink:0">✓ Respondida</span>`}
          </div>

          ${rv.comment ? `
          <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-left:3px solid ${col};border-radius:8px;padding:10px 12px;margin-bottom:${rv.reply?'10px':'0'}">
            <div style="font-size:11px;color:#E8E8E6;line-height:1.5">${rv.comment}</div>
          </div>` : ''}

          ${rv.reply ? `
          <div style="background:#252528;border:0.5px solid #2E2E33;border-left:3px solid #3483FA;border-radius:8px;padding:10px 12px">
            <div style="font-size:9px;color:#3483FA;margin-bottom:4px;font-weight:600">SUA RESPOSTA</div>
            <div style="font-size:11px;color:#9A9A96;line-height:1.5">${rv.reply.comment}</div>
          </div>` : ''}
        </div>`;
    }
    html += `</div>`;
    el.innerHTML = html;
    lucide.createIcons();

  } catch(e) {
    document.getElementById('reviews-loading').style.display = 'none';
    document.getElementById('reviews-content').style.display = 'block';
    document.getElementById('reviews-content').innerHTML = `
      <div style="text-align:center;padding:32px;color:#ef4444;font-size:12px">
        Erro ao buscar avaliações. Verifique a conexão com o ML.
      </div>`;
  }
}

// Modal de resposta
function openReplyModal(reviewId) {
  const existing = document.getElementById('reply-modal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'reply-modal';
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:1000;padding:16px';
  modal.innerHTML = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:460px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
        <i data-lucide="reply" style="width:14px;height:14px;color:#3483FA"></i>
        <span style="font-size:14px;font-weight:600;color:#E8E8E6">Responder avaliação</span>
      </div>
      <p style="font-size:11px;color:#5E5E5A;margin-bottom:12px">
        Sua resposta será pública e visível para todos os compradores no seu anúncio.
      </p>
      <textarea id="reply-text" placeholder="Escreva sua resposta pública..."
        style="width:100%;height:100px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;line-height:1.5"></textarea>
      <div style="display:flex;gap:8px;margin-top:14px">
        <button onclick="submitReply('${reviewId}')" id="reply-submit-btn" class="btn-primary" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px">
          <i data-lucide="send" style="width:12px;height:12px"></i> Publicar resposta
        </button>
        <button onclick="document.getElementById('reply-modal').remove()" class="btn-secondary">Cancelar</button>
      </div>
    </div>`;
  document.body.appendChild(modal);
  lucide.createIcons();
  setTimeout(() => document.getElementById('reply-text').focus(), 100);
}

async function submitReply(reviewId) {
  const text = document.getElementById('reply-text').value.trim();
  if (!text) { toast('Escreva uma resposta antes de publicar', 'warning'); return; }

  const btn = document.getElementById('reply-submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Publicando...';
  lucide.createIcons();

  try {
    const fd = new FormData();
    fd.append('review_id', reviewId);
    fd.append('reply', text);
    const r = await fetch('/api/sac_reviews.php', { method:'POST', body:fd });
    const d = await r.json();

    if (d.ok) {
      toast('Resposta publicada com sucesso!', 'success');
      document.getElementById('reply-modal').remove();
      loadReviews(); // recarrega lista
    } else {
      toast(d.error || 'Erro ao publicar resposta', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="send" style="width:12px;height:12px"></i> Publicar resposta';
      lucide.createIcons();
    }
  } catch(e) {
    toast('Erro de conexão', 'error');
    btn.disabled = false;
  }
}

// Carrega automaticamente ao abrir a aba
loadReviews();
</script>
<?php endif; ?>

<?php include __DIR__ . '/layout_end.php'; ?>
