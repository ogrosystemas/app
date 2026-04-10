<?php
// admin/security_log.php — Visualização de logs de segurança
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireAdmin();

$logDir = __DIR__ . '/../logs/security/';
$bfDir  = __DIR__ . '/../logs/bf/';

// Ação: desbloquear IP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_ip'])) {
    verifyCsrf();
    $ip   = $_POST['unblock_ip'] ?? '';
    $file = $bfDir . preg_replace('/[^a-zA-Z0-9_]/', '_', $ip) . '.json';
    if (file_exists($file)) {
        unlink($file);
        flash('success', "IP $ip desbloqueado com sucesso.");
    }
    redirect(BASE_URL . '/admin/security_log.php');
}

// Lê log do mês atual
$logFile = $logDir . 'security_' . date('Y-m') . '.log';
$lines   = [];
if (file_exists($logFile)) {
    $raw   = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($raw ?? []);
    $lines = array_slice($lines, 0, 200); // últimas 200 entradas
}

// IPs bloqueados atualmente
$blockedIps = [];
if (is_dir($bfDir)) {
    foreach (glob($bfDir . '*.json') as $f) {
        $raw  = @file_get_contents($f);
        $data = $raw ? (json_decode($raw, true) ?? []) : [];
        $now  = time();
        $recent = array_filter($data, fn($t) => $t > $now - 900);
        if (count($recent) >= 5) {
            // Extrai IP do nome do arquivo
            $ip = str_replace('_', '.', preg_replace('/\.json$/', '', basename($f)));
            $blockedIps[] = [
                'ip'       => $ip,
                'attempts' => count($recent),
                'until'    => date('H:i:s', min($recent) + 900),
            ];
        }
    }
}

// Contadores por tipo
$counts = [];
foreach ($lines as $line) {
    if (preg_match('/\|\s+([A-Z_]+)\s+\|/', $line, $m)) {
        $type = $m[1];
        $counts[$type] = ($counts[$type] ?? 0) + 1;
    }
}

pageOpen('Log de Segurança', 'security_log', 'Monitor de Segurança');
?>

<div class="page-header">
  <h2>Monitor de Segurança</h2>
  <p>Eventos de segurança e IPs bloqueados — <?= date('F Y') ?></p>
</div>

<?php $f = getFlash('success'); if ($f): ?>
  <div class="alert alert-success">✓ <?= e($f) ?></div>
<?php endif; ?>

<!-- Contadores -->
<div class="stats-grid" style="margin-bottom:24px">
  <?php
  $badges = [
    'LOGIN_FAILED'             => ['Logins Falhos',      'stat-card'],
    'BRUTE_FORCE_BLOCKED'      => ['Brute Force',        'stat-card'],
    'CSRF_VIOLATION'           => ['Violações CSRF',     'stat-card'],
    'ATTACK_DETECTED'          => ['Ataques Detectados', 'stat-card'],
    'UNAUTHORIZED_ADMIN_ACCESS'=> ['Acesso Não-Auth.',   'stat-card'],
    'LOGIN_SUCCESS'            => ['Logins Bem-suc.',    'stat-card success'],
  ];
  foreach ($badges as $key => [$label, $cls]): ?>
  <div class="<?= $cls ?>">
    <div class="stat-label"><?= $label ?></div>
    <div class="stat-value" style="font-size:1.8rem"><?= $counts[$key] ?? 0 ?></div>
    <div class="stat-sub">este mês</div>
  </div>
  <?php endforeach; ?>
</div>

<!-- IPs Bloqueados -->
<?php if (!empty($blockedIps)): ?>
<div class="card mb-6" style="border-color:rgba(224,92,92,.3)">
  <div class="card-header">
    <span class="card-title" style="color:var(--danger)">🚫 IPs Bloqueados Agora</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>IP</th><th>Tentativas</th><th>Bloqueado até</th><th>Ação</th></tr>
      </thead>
      <tbody>
        <?php foreach ($blockedIps as $b): ?>
        <tr>
          <td><strong><?= e($b['ip']) ?></strong></td>
          <td><span class="badge badge-danger"><?= $b['attempts'] ?></span></td>
          <td><?= e($b['until']) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf_token"  value="<?= csrfToken() ?>">
              <input type="hidden" name="unblock_ip" value="<?= e($b['ip']) ?>">
              <button class="btn btn-ghost btn-sm">Desbloquear</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Log de eventos -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Últimos Eventos de Segurança</span>
    <span class="text-muted" style="font-size:.8rem"><?= count($lines) ?> entradas</span>
  </div>
  <?php if (empty($lines)): ?>
    <p class="text-muted text-center" style="padding:24px">Nenhum evento registrado este mês.</p>
  <?php else: ?>
  <div class="table-wrap" style="max-height:520px;overflow-y:auto">
    <table>
      <thead>
        <tr><th>Data/Hora</th><th>Tipo</th><th>IP</th><th>Detalhe</th></tr>
      </thead>
      <tbody>
        <?php foreach ($lines as $line):
          // Parse: 2024-01-15 14:32:11 | LOGIN_FAILED | IP:1.2.3.4 | UA:... | URI:... | detail
          $parts = array_map('trim', explode('|', $line));
          $date   = $parts[0] ?? '';
          $type   = $parts[1] ?? '';
          $ip     = str_replace('IP:', '', $parts[2] ?? '');
          // Pula UA e URI, pega o detalhe se houver
          $detail = '';
          for ($i = 3; $i < count($parts); $i++) {
              if (!str_starts_with($parts[$i], 'UA:') && !str_starts_with($parts[$i], 'URI:')) {
                  $detail = $parts[$i];
                  break;
              }
          }
          $badgeClass = match(true) {
              str_contains($type, 'SUCCESS') => 'badge-success',
              str_contains($type, 'FAILED') || str_contains($type, 'BLOCKED')
              || str_contains($type, 'ATTACK') || str_contains($type, 'CSRF')
              || str_contains($type, 'HIJACK') => 'badge-danger',
              str_contains($type, 'UNAUTHORIZED') => 'badge-gold',
              default => 'badge-muted',
          };
        ?>
        <tr>
          <td style="font-size:.8rem;white-space:nowrap"><?= e($date) ?></td>
          <td><span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><?= e($type) ?></span></td>
          <td style="font-size:.82rem;font-family:monospace"><?= e($ip) ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= e($detail) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php pageClose(); ?>
