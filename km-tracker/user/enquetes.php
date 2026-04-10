<?php
// user/enquetes.php — Responder enquetes
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db  = db();
$me  = currentUser();
$uid = $me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $enqueteId = (int)($_POST['enquete_id'] ?? 0);
    $resposta  = trim($_POST['resposta'] ?? '');

    if ($enqueteId && $resposta) {
        $stmt = $db->prepare("SELECT * FROM enquetes WHERE id=? AND status='ativa'");
        $stmt->execute([$enqueteId]);
        $eq = $stmt->fetch();

        if ($eq) {
            $opcoes = json_decode($eq['opcoes'], true) ?? [];
            if (in_array($resposta, $opcoes)) {
                try {
                    $db->prepare("INSERT INTO enquetes_respostas (enquete_id, user_id, resposta) VALUES (?,?,?)
                                  ON DUPLICATE KEY UPDATE resposta=VALUES(resposta), respondido_em=NOW()")
                       ->execute([$enqueteId, $uid, $resposta]);
                    $_SESSION['flash_success'] = 'Voto registrado!';
                } catch (Throwable $e) {
                    $_SESSION['flash_error'] = 'Erro ao registrar voto.';
                }
            }
        }
    }
    header('Location: ' . BASE_URL . '/user/enquetes.php');
    exit;
}

$stmt = $db->query("SELECT eq.*, e.title as evento_titulo
    FROM enquetes eq
    LEFT JOIN events e ON e.id = eq.event_id
    WHERE eq.status = 'ativa'
    ORDER BY eq.criado_em DESC");
$enquetes = $stmt->fetchAll();

pageOpen('Enquetes', 'enquetes', '📊 Enquetes');
?>

<?php if (isset($_SESSION['flash_success'])): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
<div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <h2>📊 Enquetes</h2>
    <p>Participe das votações do clube</p>
</div>

<?php if (empty($enquetes)): ?>
<div class="card" style="text-align:center;padding:60px 20px">
    <div style="font-size:3rem;margin-bottom:12px">📊</div>
    <div style="color:#6e7485">Nenhuma enquete ativa no momento.</div>
    <div style="font-size:.78rem;color:#6e7485;margin-top:6px">Fique atento às notificações no WhatsApp!</div>
</div>
<?php endif; ?>

<?php foreach ($enquetes as $eq):
    $opcoes = json_decode($eq['opcoes'], true) ?? [];
    $stmtR  = $db->prepare("SELECT resposta FROM enquetes_respostas WHERE enquete_id=? AND user_id=?");
    $stmtR->execute([$eq['id'], $uid]);
    $minhaResp  = $stmtR->fetchColumn();
    $jaRespondeu = $minhaResp !== false;
    $respostas  = $db->query("SELECT resposta, COUNT(*) as total FROM enquetes_respostas WHERE enquete_id={$eq['id']} GROUP BY resposta")->fetchAll();
    $totalResp  = array_sum(array_column($respostas, 'total'));
?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($eq['titulo']) ?></h3>
        <?php if ($eq['evento_titulo']): ?>
        <span style="font-size:.75rem;color:#6e7485">🏍️ <?= htmlspecialchars($eq['evento_titulo']) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p style="font-size:.92rem;margin-bottom:20px;color:#a0a5b5"><?= htmlspecialchars($eq['pergunta']) ?></p>

        <?php if (!$jaRespondeu): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="enquete_id" value="<?= $eq['id'] ?>">
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
                <?php foreach ($opcoes as $op): ?>
                <label style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                              background:#0d0f14;border:2px solid #2a2f3a;border-radius:10px;
                              cursor:pointer;transition:border-color .15s"
                       onmouseover="this.style.borderColor='#f39c12'"
                       onmouseout="this.style.borderColor='#2a2f3a'">
                    <input type="radio" name="resposta" value="<?= htmlspecialchars($op) ?>" required
                           style="accent-color:#f39c12;width:18px;height:18px;flex-shrink:0">
                    <span style="font-weight:500;font-size:.9rem"><?= htmlspecialchars($op) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">✅ Votar</button>
        </form>

        <?php else: ?>
        <div style="background:#28a74515;border:1px solid #28a74540;border-radius:8px;padding:10px 14px;
                    font-size:.82rem;color:#28a745;font-weight:600;margin-bottom:16px">
            ✅ Você votou: <strong><?= htmlspecialchars($minhaResp) ?></strong>
        </div>
        <?php foreach ($opcoes as $op):
            $qt = 0;
            foreach ($respostas as $r) { if ($r['resposta'] === $op) $qt = (int)$r['total']; }
            $pct = $totalResp > 0 ? round($qt / $totalResp * 100) : 0;
        ?>
        <div style="margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:4px">
                <span style="font-weight:<?= $op===$minhaResp?'700':'400' ?>;color:<?= $op===$minhaResp?'#f5b041':'#a0a5b5' ?>">
                    <?= htmlspecialchars($op) ?> <?= $op===$minhaResp?'✅':'' ?>
                </span>
                <span style="color:#6e7485"><?= $qt ?> (<?= $pct ?>%)</span>
            </div>
            <div style="height:8px;background:#2a2f3a;border-radius:4px">
                <div style="height:8px;background:<?= $op===$minhaResp?'#f39c12':'#2a2f3a' ?>;
                            border:<?= $op!==$minhaResp?'1px solid #3a3f4a':'' ?>;
                            border-radius:4px;width:<?= $pct ?>%;transition:width .5s;min-width:<?= $pct>0?'4px':'0' ?>"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="font-size:.75rem;color:#6e7485;margin-top:10px"><?= $totalResp ?> voto(s) no total</div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php pageClose(); ?>
