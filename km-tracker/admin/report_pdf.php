<?php
// admin/report_pdf.php — Relatório PDF com logo Mutantes + filtros
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

// ── TCPDF ────────────────────────────────────────────────────
$tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die('
    <div style="font-family:Arial,sans-serif;padding:40px;max-width:640px;margin:40px auto;
                background:#fff3cd;border:1px solid #ffc107;border-radius:8px">
        <h2 style="color:#856404">TCPDF nao encontrado</h2>
        <ol style="line-height:2.2;margin-top:12px">
            <li>Baixe em: <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip">github.com/tecnickcom/TCPDF</a></li>
            <li>Extraia e renomeie para <code>tcpdf</code></li>
            <li>Cole em <code>km-system/tcpdf/</code></li>
        </ol>
        <p><a href="' . BASE_URL . '/admin/reports.php"
              style="background:#856404;color:#fff;padding:8px 16px;border-radius:6px;
                     text-decoration:none;display:inline-block;margin-top:12px">&larr; Voltar</a></p>
    </div>');
}
require_once $tcpdfPath;

// ── Filtros via GET ──────────────────────────────────────────
$db       = db();
$year     = (int)($_GET['year']     ?? date('Y'));
$userId   = (int)($_GET['user_id']  ?? 0);
$eventId  = (int)($_GET['event_id'] ?? 0);
$dateFrom = sanitizeDate($_GET['date_from'] ?? '');
$dateTo   = sanitizeDate($_GET['date_to']   ?? '');
$type     = in_array($_GET['type'] ?? '', array('ranking','events','complete')) ? $_GET['type'] : 'complete';

// ── Monta WHERE dinâmico ─────────────────────────────────────
$whereBase  = array('YEAR(e.event_date) = ?');
$paramsBase = array($year);

if ($userId  > 0) { $whereBase[] = 'a.user_id = ?';  $paramsBase[] = $userId; }
if ($eventId > 0) { $whereBase[] = 'a.event_id = ?'; $paramsBase[] = $eventId; }
if ($dateFrom)    { $whereBase[] = 'e.event_date >= ?'; $paramsBase[] = $dateFrom; }
if ($dateTo)      { $whereBase[] = 'e.event_date <= ?'; $paramsBase[] = $dateTo; }

$whereStr = 'WHERE ' . implode(' AND ', $whereBase);

// ============================================================
// FUNÇÃO PARA NOME DO MÊS EM PORTUGUÊS
// ============================================================
function getMesEmPortugues($mesNumero = null) {
    $meses = array(
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    );
    if ($mesNumero === null) {
        $mesNumero = (int)date('m');
    }
    return $meses[$mesNumero];
}

// ============================================================
// PARTICIPAÇÃO NAS SEXTAS-FEIRAS (APENAS MÊS ATUAL)
// ============================================================

// Função para gerar as Sextas-feiras APENAS do mês atual
function getSextasDoMesAtual() {
    $sextas = array();
    $mesAtual = (int)date('m');
    $anoAtual = (int)date('Y');
    $data = new DateTime("first friday of $anoAtual-$mesAtual-01");
    
    while ($data->format('m') == $mesAtual && $data->format('Y') == $anoAtual) {
        $sextas[] = $data->format('Y-m-d');
        $data->modify('+7 days');
    }
    return $sextas;
}

// Buscar todas as confirmações de Sextas-feiras
$sextasStats = $db->query("
    SELECT 
        sc.data_sexta,
        COUNT(sc.id) as total_confirmacoes
    FROM sextas_confirmacoes sc
    GROUP BY sc.data_sexta
")->fetchAll();

// Mapa de confirmações
$sextasMap = array();
foreach ($sextasStats as $s) {
    $sextasMap[$s['data_sexta']] = $s['total_confirmacoes'];
}

// Lista de Sextas APENAS do mês atual
$sextasDoMes = getSextasDoMesAtual();
$sextasCompletas = array();
foreach ($sextasDoMes as $data) {
    $sextasCompletas[] = array(
        'data' => $data,
        'total' => isset($sextasMap[$data]) ? $sextasMap[$data] : 0
    );
}

$totalSextasParticipantes = 0;
foreach ($sextasCompletas as $s) {
    $totalSextasParticipantes += $s['total'];
}

// ── Dados ────────────────────────────────────────────────────

// Ranking - incluindo administradores (todos os usuários ativos)
if ($userId === 0) {
    $rankSQL = "
        SELECT u.name, u.id, u.role,
               COUNT(a.id) AS presencas,
               COALESCE(SUM(e.km_awarded + a.km_extra), 0) AS total_km,
               COALESCE(SUM(e.km_awarded), 0) AS km_eventos,
               COALESCE(SUM(a.km_extra),  0)  AS km_extra
        FROM users u
        LEFT JOIN attendances a ON a.user_id = u.id
        LEFT JOIN events e ON e.id = a.event_id
            AND YEAR(e.event_date) = ?
            " . ($eventId > 0 ? "AND a.event_id = ?" : "") . "
            " . ($dateFrom      ? "AND e.event_date >= ?" : "") . "
            " . ($dateTo        ? "AND e.event_date <= ?" : "") . "
        WHERE u.active = 1
        GROUP BY u.id, u.name, u.role
        ORDER BY total_km DESC
    ";
    $rankParams = array_filter(array($year,
        $eventId > 0 ? $eventId  : null,
        $dateFrom    ? $dateFrom : null,
        $dateTo      ? $dateTo   : null,
    ));
} else {
    $rankSQL = "
        SELECT u.name, u.id, u.role,
               COUNT(a.id) AS presencas,
               COALESCE(SUM(e.km_awarded + a.km_extra), 0) AS total_km,
               COALESCE(SUM(e.km_awarded), 0) AS km_eventos,
               COALESCE(SUM(a.km_extra),  0)  AS km_extra
        FROM attendances a
        JOIN users u  ON u.id = a.user_id
        JOIN events e ON e.id = a.event_id
        $whereStr
        AND u.active = 1
        GROUP BY u.id, u.name, u.role
        ORDER BY total_km DESC
    ";
    $rankParams = $paramsBase;
}

$rankStmt = $db->prepare($rankSQL);
$rankStmt->execute(array_values($rankParams));
$ranking = $rankStmt->fetchAll();

// Eventos
$evWhereBase  = array('YEAR(e.event_date) = ?');
$evParamsBase = array($year);
if ($eventId > 0) { $evWhereBase[] = 'e.id = ?'; $evParamsBase[] = $eventId; }
if ($dateFrom)    { $evWhereBase[] = 'e.event_date >= ?'; $evParamsBase[] = $dateFrom; }
if ($dateTo)      { $evWhereBase[] = 'e.event_date <= ?'; $evParamsBase[] = $dateTo; }
if ($userId > 0)  { $evWhereBase[] = 'EXISTS (SELECT 1 FROM attendances ax WHERE ax.event_id=e.id AND ax.user_id=?)'; $evParamsBase[] = $userId; }
$evWhereStr = 'WHERE ' . implode(' AND ', $evWhereBase);

$evStmt = $db->prepare("
    SELECT e.title, e.event_date, e.location, e.km_awarded,
           COUNT(a.id) AS presentes,
           COALESCE(SUM(a.km_extra), 0) AS total_extra
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id
    $evWhereStr
    GROUP BY e.id
    ORDER BY e.event_date ASC
");
$evStmt->execute($evParamsBase);
$eventStats = $evStmt->fetchAll();

// KM por mês
$kmMensalStmt = $db->prepare("
    SELECT MONTH(e.event_date) AS mes,
           COALESCE(SUM(e.km_awarded + a.km_extra), 0) AS km
    FROM attendances a
    JOIN events e ON e.id = a.event_id
    $whereStr
    GROUP BY MONTH(e.event_date)
");
$kmMensalStmt->execute($paramsBase);
$kmPorMes = array_fill(1, 12, 0);
foreach ($kmMensalStmt->fetchAll() as $r) {
    $kmPorMes[(int)$r['mes']] = (float)$r['km'];
}

// Nome do usuário filtrado
$filterUserName = '';
if ($userId > 0) {
    $un = $db->prepare('SELECT name FROM users WHERE id=?');
    $un->execute([$userId]);
    $filterUserName = $un->fetchColumn() ?: '';
}

// Nome do evento filtrado
$filterEventName = '';
if ($eventId > 0) {
    $en = $db->prepare('SELECT title FROM events WHERE id=?');
    $en->execute([$eventId]);
    $filterEventName = $en->fetchColumn() ?: '';
}

// Totais
$totalKm        = array_sum(array_column($ranking,   'total_km'));
$totalPresencas = array_sum(array_column($ranking,   'presencas'));
$totalEventos   = count($eventStats);
$totalUsers     = count($ranking);
$geradoEm       = date('d/m/Y \a\s H:i');
$meses          = array('Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez');

// Descrição dos filtros ativos
$filtrosAtivos = array("Ano: $year");
if ($filterUserName)  $filtrosAtivos[] = "Membro: $filterUserName";
if ($filterEventName) $filtrosAtivos[] = "Evento: $filterEventName";
if ($dateFrom)        $filtrosAtivos[] = "De: " . date('d/m/Y', strtotime($dateFrom));
if ($dateTo)          $filtrosAtivos[] = "Até: " . date('d/m/Y', strtotime($dateTo));
$filtrosStr = implode('   |   ', $filtrosAtivos);

// ── Logo em base64 ──────────────────────────────────────────
$logoPath = __DIR__ . '/../assets/logo.png';
$logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';

// ── Paleta ──────────────────────────────────────────────────
$GOLD    = array(201, 168,  76);
$DARK    = array( 13,  15,  20);
$MUTED   = array( 85,  94, 126);
$WHITE   = array(238, 240, 248);
$ACCENT  = array( 79, 124, 255);
$GREEN   = array( 61, 186, 124);
$ORANGE  = array(224, 124,  76);
$ADMIN   = array(108, 92, 231);
$PALETTE = array(
    array(201,168, 76), array(79,124,255), array(61,186,124), array(224,124,76),
    array(155,79,255), array(76,201,201), array(224,92,92), array(124,186,61),
);

// ── Classe PDF ──────────────────────────────────────────────
class MutantesPDF extends TCPDF
{
    public int    $year        = 0;
    public string $genDate     = '';
    public string $filtros     = '';
    public bool   $isCover     = true;
    public string $logoData    = '';

    public function Header(): void
    {
        if ($this->isCover) return;

        $this->SetFillColor(13, 15, 20);
        $this->Rect(0, 0, 210, 14, 'F');

        if ($this->logoData) {
            $this->Image('@' . base64_decode($this->logoData), 11, 1, 11, 11, 'PNG');
        }

        $this->SetFont('helvetica', 'B', 8.5);
        $this->SetTextColor(201, 168, 76);
        $this->SetXY(24, 2.5);
        $this->Cell(100, 5, 'MUTANTES MC BRASIL — KM Tracker', 0, 0, 'L');
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(85, 94, 126);
        $this->SetXY(24, 8);
        $this->Cell(100, 4, $this->filtros, 0, 0, 'L');

        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(85, 94, 126);
        $this->SetXY(130, 4);
        $this->Cell(70, 6, $this->genDate, 0, 0, 'R');

        $this->SetDrawColor(201, 168, 76);
        $this->SetLineWidth(0.4);
        $this->Line(0, 14, 210, 14);
    }

    public function Footer(): void
    {
        if ($this->isCover) return;
        $this->SetY(-10);
        $this->SetFillColor(240, 241, 246);
        $this->Rect(0, $this->GetY(), 210, 12, 'F');
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(85, 94, 126);
        $this->Cell(0, 8,
            'Mutantes KM Tracker v' . APP_VERSION .
            '   |   Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(),
            0, 0, 'C');
    }
}

// ── Instancia ───────────────────────────────────────────────
$pdf = new MutantesPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->year     = $year;
$pdf->genDate  = 'Gerado em ' . $geradoEm;
$pdf->filtros  = $filtrosStr;
$pdf->logoData = $logoData;
$pdf->SetCreator('Mutantes KM Tracker v' . APP_VERSION);
$pdf->SetAuthor('Mutantes MC Brasil');
$pdf->SetTitle('Relatorio KM Mutantes ' . $year);
$pdf->SetMargins(12, 17, 12);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(11);
$pdf->SetAutoPageBreak(true, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// ════════════════════════════════════════════════════════════
// CAPA - Primeira página
// ════════════════════════════════════════════════════════════
$pdf->isCover = true;
$pdf->AddPage();

$pdf->SetFillColor(13, 15, 20);
$pdf->Rect(0, 0, 210, 297, 'F');

$pdf->SetFillColor(201, 168, 76);
$pdf->Rect(0, 0, 210, 5, 'F');

$pdf->SetFillColor(26, 29, 40);
$pdf->Rect(0, 5, 210, 100, 'F');

$pdf->SetDrawColor(201, 168, 76);
$pdf->SetLineWidth(0.5);
$pdf->Line(0, 105, 210, 105);

if ($logoData) {
    $logoW = 59; $logoH = 70;
    $logoX = (210 - $logoW) / 2;
    $pdf->Image('@' . base64_decode($logoData), $logoX, 12, $logoW, $logoH, 'PNG');
}

$pdf->SetFont('helvetica', 'B', 26);
$pdf->SetTextColor(201, 168, 76);
$pdf->SetXY(0, 110);
$pdf->Cell(210, 14, 'MUTANTES MC BRASIL', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(238, 240, 248);
$pdf->SetXY(0, 125);
$pdf->Cell(210, 8, 'Relatorio de Quilometragem e Presenca em Eventos', 0, 1, 'C');

$pdf->SetDrawColor(42, 47, 69);
$pdf->SetLineWidth(0.4);
$pdf->Line(30, 142, 180, 142);

$pdf->SetFont('helvetica', 'I', 9);
$pdf->SetTextColor(201, 168, 76);
$pdf->SetXY(0, 147);
$pdf->Cell(210, 7, $filtrosStr, 0, 1, 'C');

$pdf->Line(30, 157, 180, 157);

$pdf->SetFont('helvetica', 'B', 64);
$pdf->SetTextColor(30, 34, 50);
$pdf->SetXY(0, 160);
$pdf->Cell(210, 36, (string)$year, 0, 1, 'C');

$pdf->SetDrawColor(42, 47, 69);
$pdf->Line(20, 200, 190, 200);

$statData = array(
    array(number_format($totalKm, 0, ',', '.') . ' km', 'Total KM'),
    array((string)$totalPresencas, 'Presencas'),
    array((string)$totalEventos,   'Eventos'),
    array((string)$totalUsers,     'Membros'),
);
$cw = 42; $gap = 4; $sx = 15; $sy = 208;
foreach ($statData as $i => $s) {
    $bx = $sx + $i * ($cw + $gap);
    $pdf->SetFillColor(26, 29, 40);
    $pdf->RoundedRect($bx, $sy, $cw, 26, 3, '1111', 'F');
    $pdf->SetFillColor(201, 168, 76);
    $pdf->Rect($bx, $sy, $cw, 1.8, 'F');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(201, 168, 76);
    $pdf->SetXY($bx, $sy + 4);
    $pdf->Cell($cw, 9, $s[0], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(85, 94, 126);
    $pdf->SetXY($bx, $sy + 15);
    $pdf->Cell($cw, 6, strtoupper($s[1]), 0, 1, 'C');
}

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(42, 47, 69);
$pdf->SetXY(0, 258);
$pdf->Cell(210, 6, $geradoEm . '   |   Mutantes KM Tracker v' . APP_VERSION, 0, 0, 'C');

if ($logoData) {
    $pdf->Image('@' . base64_decode($logoData), 94, 267, 22, 26, 'PNG');
}

$pdf->SetFillColor(201, 168, 76);
$pdf->Rect(0, 292, 210, 5, 'F');

// ════════════════════════════════════════════════════════════
// PÁGINAS DE CONTEÚDO (começa na página 2 - sem página em branco)
// ════════════════════════════════════════════════════════════
$pdf->isCover = false;
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Helper: título de seção
$drawSection = function(string $txt) use ($pdf, $GOLD, $DARK) {
    $y = $pdf->GetY();
    $pdf->SetFillColor(...$GOLD);
    $pdf->Rect(12, $y, 3, 7, 'F');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...$DARK);
    $pdf->SetXY(17, $y);
    $pdf->Cell(180, 7, $txt, 0, 1, 'L');
    $pdf->SetDrawColor(220, 222, 235);
    $pdf->SetLineWidth(0.25);
    $pdf->Line(12, $pdf->GetY(), 198, $pdf->GetY());
    $pdf->Ln(4);
};

// ── PÁGINA 2: VISÃO GERAL + PARTICIPAÇÃO SEXTAS ─────────────
$pdf->AddPage();
$pdf->SetFillColor(247, 248, 252);
$pdf->Rect(0, 0, 210, 297, 'F');
$pdf->SetY(20);

// KPIs
$kpis = array(
    array(number_format($totalKm, 0, ',', '.') . ' km', 'KM Total', $GOLD),
    array((string)$totalPresencas, 'Presencas', $ACCENT),
    array((string)$totalEventos,   'Eventos',   $GREEN),
    array((string)$totalUsers,     'Membros',   $ORANGE),
);
foreach ($kpis as $i => $k) {
    $bx = 12 + $i * 46;
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect($bx, 20, 43, 18, 2, '1111', 'F');
    $pdf->SetFillColor(...$k[2]);
    $pdf->Rect($bx, 20, 43, 1.5, 'F');
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(...$k[2]);
    $pdf->SetXY($bx, 23);
    $pdf->Cell(43, 8, $k[0], 0, 0, 'C');
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(...$MUTED);
    $pdf->SetXY($bx, 32);
    $pdf->Cell(43, 4, strtoupper($k[1]), 0, 0, 'C');
}
$pdf->SetY(45);

// Gráfico linha KM mensal
$drawSection('Evolucao Mensal de KM — ' . $year . ($filterUserName ? ' — ' . $filterUserName : ''));

$lX=12; $lY=$pdf->GetY(); $lW=186; $lH=50;
$pL=18; $pR=4; $pT=4; $pB=14;
$iW=$lW-$pL-$pR; $iH=$lH-$pT-$pB;

$pdf->SetFillColor(255,255,255);
$pdf->RoundedRect($lX,$lY,$lW,$lH,2,'1111','F');
$maxVal = max(array_values($kmPorMes) ?: array(1));

for ($g=0; $g<=4; $g++) {
    $gy = $lY+$pT+($iH/4)*$g;
    $pdf->SetDrawColor(220,222,235); $pdf->SetLineWidth(0.2);
    $pdf->Line($lX+$pL,$gy,$lX+$lW-$pR,$gy);
    $pdf->SetFont('helvetica','',5.5); $pdf->SetTextColor(...$MUTED);
    $pdf->SetXY($lX,$gy-2);
    $pdf->Cell($pL-1,4,number_format($maxVal*(1-$g/4),0,',','.'),'',0,'R');
}
for ($m=1; $m<=12; $m++) {
    $mx=$lX+$pL+(($m-1)/11)*$iW;
    $pdf->SetFont('helvetica','',5.5); $pdf->SetTextColor(...$MUTED);
    $pdf->SetXY($mx-4,$lY+$lH-$pB+1);
    $pdf->Cell(8,4,$meses[$m-1],'',0,'C');
}
$pts=array();
for ($m=1;$m<=12;$m++) {
    $pts[$m]=array(
        $lX+$pL+(($m-1)/11)*$iW,
        $lY+$pT+$iH-($maxVal>0?($kmPorMes[$m]/$maxVal)*$iH:0),
    );
}
$pdf->SetDrawColor(...$GOLD); $pdf->SetLineWidth(0.8);
for ($m=1;$m<=11;$m++) $pdf->Line($pts[$m][0],$pts[$m][1],$pts[$m+1][0],$pts[$m+1][1]);
$pdf->SetLineWidth(0.15); $pdf->SetDrawColor(201,168,76);
foreach ($pts as $m=>$p) {
    if ($kmPorMes[$m]>0) {
        $pdf->Line($p[0],$p[1],$p[0],$lY+$pT+$iH);
        $pdf->SetFillColor(...$GOLD); $pdf->SetDrawColor(255,255,255); $pdf->SetLineWidth(0.5);
        $pdf->Circle($p[0],$p[1],1.3,0,360,'FD');
    }
}
$pdf->SetY($lY+$lH+8);

// SEÇÃO: PARTICIPAÇÃO NAS SEXTAS-FEIRAS
if (!empty($sextasCompletas)) {
    $drawSection('Participacao nas Sextas-feiras — ' . getMesEmPortugues() . ' de ' . date('Y'));
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(...$DARK);
    $pdf->SetTextColor(255, 255, 255);
    
    $wCols = array(70, 40, 40, 36);
    $pdf->Cell($wCols[0], 8, 'Data', 0, 0, 'C', true);
    $pdf->Cell($wCols[1], 8, 'Dia da Semana', 0, 0, 'C', true);
    $pdf->Cell($wCols[2], 8, 'Participantes', 0, 0, 'C', true);
    $pdf->Cell($wCols[3], 8, 'Status', 0, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 250);
    
    foreach ($sextasCompletas as $sexta) {
        $dataObj = new DateTime($sexta['data']);
        $dataFormatada = $dataObj->format('d/m/Y');
        $diaSemana = $dataObj->format('l');
        $diasSemana = array(
            'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira',
            'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira',
            'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        );
        $nomeDia = isset($diasSemana[$diaSemana]) ? $diasSemana[$diaSemana] : $diaSemana;
        $participantes = $sexta['total'];
        
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($wCols[0], 7, $dataFormatada, 0, 0, 'C', true);
        $pdf->Cell($wCols[1], 7, $nomeDia, 0, 0, 'C', true);
        
        if ($participantes > 0) {
            $pdf->SetTextColor(201, 168, 76);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($wCols[2], 7, $participantes, 0, 0, 'C', true);
            $pdf->SetTextColor(40, 167, 69);
            $pdf->Cell($wCols[3], 7, 'Confirmada', 0, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 8);
        } else {
            $pdf->SetTextColor(108, 117, 125);
            $pdf->Cell($wCols[2], 7, '0', 0, 0, 'C', true);
            $pdf->SetTextColor(243, 156, 18);
            $pdf->Cell($wCols[3], 7, 'Aguardando', 0, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
        }
    }
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 242, 245);
    $pdf->SetTextColor(201, 168, 76);
    $pdf->Cell($wCols[0] + $wCols[1], 8, 'TOTAL DE CONFIRMACOES:', 0, 0, 'R', true);
    $pdf->SetTextColor(201, 168, 76);
    $pdf->Cell($wCols[2], 8, $totalSextasParticipantes, 0, 0, 'C', true);
    $pdf->Cell($wCols[3], 8, '', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Ln(6);
}

// ── PÁGINA 3: RANKING DE KM + TABELA DE MEMBROS ────────────
if (!empty($ranking)) {
    $pdf->AddPage();
    $pdf->SetFillColor(247, 248, 252);
    $pdf->Rect(0, 0, 210, 297, 'F');
    $pdf->SetY(20);

    // Gráfico barras — ranking
    $drawSection('Ranking de KM' . ($filterEventName ? ' — Evento: '.$filterEventName : ''));
    
    $top = $ranking;
    $maxKm0 = !empty($top) ? (float)$top[0]['total_km'] : 1;
    $lbW=46; $barAreaW=116; $valW=26;
    $bH2=6; $gap2=3;
    
    $displayLimit = min(count($top), 20);
    $bgH = $displayLimit * ($bH2 + $gap2) + 8;
    
    $bgY = $pdf->GetY();
    
    $pdf->SetFillColor(255,255,255);
    $pdf->RoundedRect(12,$bgY,$lbW+$barAreaW+$valW+4,$bgH,2,'1111','F');

    for ($i=0; $i<$displayLimit; $i++) {
        $row = $top[$i];
        $rowY = $bgY + 4 + $i * ($bH2 + $gap2);
        $pct = $maxKm0 > 0 ? $row['total_km'] / $maxKm0 : 0;
        $bw = max(2, $pct * $barAreaW);
        $col = ($row['role'] === 'admin') ? $ADMIN : $PALETTE[$i % count($PALETTE)];

        $nm = mb_strlen($row['name']) > 20 ? mb_substr($row['name'], 0, 18) . '.' : $row['name'];
        $nm .= ($row['role'] === 'admin') ? ' (Admin)' : '';
        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(...$MUTED);
        $pdf->SetXY(12, $rowY);
        $pdf->Cell($lbW, $bH2, $nm, 0, 0, 'R');

        $pdf->SetFillColor(215, 218, 232);
        $pdf->RoundedRect(12 + $lbW, $rowY, $barAreaW, $bH2, 1.5, '1111', 'F');
        $pdf->SetFillColor(...$col);
        $pdf->RoundedRect(12 + $lbW, $rowY, $bw, $bH2, 1.5, '1111', 'F');

        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetTextColor(...$col);
        $pdf->SetXY(12 + $lbW + $barAreaW + 2, $rowY);
        $pdf->Cell($valW, $bH2, number_format($row['total_km'], 0, ',', '.') . ' km', 0, 0, 'L');
    }
    $pdf->SetY($bgY + $bgH + 8);
    
    // Tabela de Membros
    $drawSection('Tabela de Membros — Quilometragem Detalhada');
    $pdf->Ln(2);

    $hCols = array('#' => 8, 'Membro' => 52, 'Perfil' => 22, 'Presencas' => 18, 'KM Eventos' => 24, 'KM Extra' => 22, 'KM Total' => 26, 'Indice' => 18);
    $pdf->SetFillColor(...$DARK);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 6.5);
    foreach ($hCols as $lbl => $w) {
        $pdf->Cell($w, 7, $lbl, 0, 0, 'C', true);
    }
    $pdf->Ln();

    $maxKm1 = !empty($ranking) ? (float)$ranking[0]['total_km'] : 1;
    foreach ($ranking as $i => $row) {
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
            $pdf->SetY(20);
            $pdf->SetFillColor(...$DARK);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 6.5);
            foreach ($hCols as $lbl => $w) {
                $pdf->Cell($w, 7, $lbl, 0, 0, 'C', true);
            }
            $pdf->Ln();
        }
        
        $even = $i % 2 === 0;
        $pdf->SetFillColor($even ? 255 : 246, $even ? 255 : 247, $even ? 255 : 253);
        $pct = $maxKm1 > 0 ? round($row['total_km'] / $maxKm1 * 100) : 0;
        $rCol = $i === 0 ? $GOLD : ($i === 1 ? array(160, 165, 180) : ($i === 2 ? array(180, 120, 60) : $MUTED));
        $roleColor = ($row['role'] === 'admin') ? $ADMIN : $rCol;

        $pdf->SetTextColor(...$roleColor);
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->Cell(8, 6, $i + 1, 0, 0, 'C', true);
        $pdf->SetTextColor(30, 30, 55);
        $pdf->SetFont('helvetica', $i < 3 ? 'B' : '', 7);
        $pdf->Cell(52, 6, $row['name'], 0, 0, 'L', true);
        
        $profileText = ($row['role'] === 'admin') ? 'Administrador' : 'Membro';
        $profileColor = ($row['role'] === 'admin') ? $ADMIN : $ACCENT;
        $pdf->SetTextColor(...$profileColor);
        $pdf->SetFont('helvetica', '', 6);
        $pdf->Cell(22, 6, $profileText, 0, 0, 'C', true);
        
        $pdf->SetTextColor(...$ACCENT);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(18, 6, $row['presencas'], 0, 0, 'C', true);
        $pdf->SetTextColor(70, 70, 90);
        $pdf->Cell(24, 6, number_format($row['km_eventos'], 0, ',', '.') . ' km', 0, 0, 'R', true);
        $pdf->SetTextColor(...$ACCENT);
        $pdf->Cell(22, 6, $row['km_extra'] > 0 ? '+' . number_format($row['km_extra'], 0, ',', '.') . ' km' : '-', 0, 0, 'R', true);
        $pdf->SetTextColor(...$GOLD);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(26, 6, number_format($row['total_km'], 0, ',', '.') . ' km', 0, 0, 'R', true);
        
        $bx = $pdf->GetX() + 1;
        $by = $pdf->GetY() + 1.5;
        $pdf->SetFillColor(215, 218, 230);
        $pdf->RoundedRect($bx, $by, 15, 3, 1, '1111', 'F');
        $pdf->SetFillColor(...$GOLD);
        $pdf->RoundedRect($bx, $by, max(0.5, $pct / 100 * 15), 3, 1, '1111', 'F');
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetTextColor(...$MUTED);
        $pdf->SetXY($bx, $by + 3.2);
        $pdf->Cell(15, 3, $pct . '%', 0, 0, 'C');
        $pdf->SetXY(12, $pdf->GetY() + 6);
    }
}

// ── PÁGINA 4: EVENTOS ───────────────────────────────────────
if (!empty($eventStats)) {
    $pdf->AddPage();
    $pdf->SetFillColor(247, 248, 252);
    $pdf->Rect(0, 0, 210, 297, 'F');
    $pdf->SetY(20);

    $drawSection('Presencas por Evento' . ($filterUserName ? ' — ' . $filterUserName : ''));

    $eX = 12;
    $eY = $pdf->GetY();
    $eW = 186;
    $eH = 50;
    $ePL = 8;
    $ePR = 8;
    $ePT = 5;
    $ePB = 14;
    $eIW = $eW - $ePL - $ePR;
    $eIH = $eH - $ePT - $ePB;

    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect($eX, $eY, $eW, $eH, 2, '1111', 'F');

    $maxPres = max(array_column($eventStats, 'presentes') ?: array(1));
    $n = count($eventStats);
    $slotW = $eIW / max($n, 1);
    $bwE = max(5, $slotW * 0.65);

    foreach ($eventStats as $ei => $ev) {
        $bx = $eX + $ePL + $ei * $slotW + ($slotW - $bwE) / 2;
        $bh = $maxPres > 0 ? ($ev['presentes'] / $maxPres) * $eIH : 0;
        $by = $eY + $ePT + $eIH - $bh;
        $col = $PALETTE[$ei % count($PALETTE)];

        $pdf->SetFillColor(215, 218, 232);
        $pdf->RoundedRect($bx, $eY + $ePT, $bwE, $eIH, 1.5, '1111', 'F');
        $pdf->SetFillColor(...$col);
        $pdf->RoundedRect($bx, $by, $bwE, max(0.5, $bh), 1.5, '1111', 'F');
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetTextColor(...$col);
        $pdf->SetXY($bx - 1, max($eY + $ePT, $by) - 5);
        $pdf->Cell($bwE + 2, 4, (string)$ev['presentes'], 0, 0, 'C');
        $lbl = mb_strlen($ev['title']) > 13 ? mb_substr($ev['title'], 0, 11) . '.' : $ev['title'];
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetTextColor(...$MUTED);
        $pdf->SetXY($bx - 2, $eY + $eH - $ePB + 1);
        $pdf->Cell($bwE + 4, 10, $lbl, 0, 0, 'C');
    }
    $pdf->SetY($eY + $eH + 8);

    $drawSection('Detalhamento de Eventos');
    $pdf->Ln(2);

    $evH = array('Evento' => 55, 'Data' => 20, 'Local' => 36, 'KM/Pres' => 19, 'Presentes' => 19, 'KM Extra' => 19, 'KM Total' => 22);
    $pdf->SetFillColor(...$DARK);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 7);
    foreach ($evH as $lbl => $w) {
        $pdf->Cell($w, 7, $lbl, 0, 0, 'C', true);
    }
    $pdf->Ln();

    $totalDist = 0;
    foreach ($eventStats as $ei => $ev) {
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
            $pdf->SetY(20);
            $pdf->SetFillColor(...$DARK);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 7);
            foreach ($evH as $lbl => $w) {
                $pdf->Cell($w, 7, $lbl, 0, 0, 'C', true);
            }
            $pdf->Ln();
        }
        $even = $ei % 2 === 0;
        $pdf->SetFillColor($even ? 255 : 246, $even ? 255 : 247, $even ? 255 : 253);
        $totEv = ($ev['km_awarded'] * $ev['presentes']) + $ev['total_extra'];
        $totalDist += $totEv;
        $kmEx = $ev['total_extra'] > 0 ? '+' . number_format($ev['total_extra'], 0, ',', '.') : '-';
        $loc = mb_strlen($ev['location'] ?? '') > 18 ? mb_substr((string)$ev['location'], 0, 16) . '.' : ($ev['location'] ?? '-');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(30, 30, 55);
        $pdf->Cell(55, 6, $ev['title'], 0, 0, 'L', true);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(...$MUTED);
        $pdf->Cell(20, 6, date('d/m/Y', strtotime($ev['event_date'])), 0, 0, 'C', true);
        $pdf->Cell(36, 6, $loc, 0, 0, 'L', true);
        $pdf->SetTextColor(...$GOLD);
        $pdf->Cell(19, 6, number_format($ev['km_awarded'], 0, ',', '.'), 0, 0, 'R', true);
        $pdf->SetTextColor(...$GREEN);
        $pdf->Cell(19, 6, (string)$ev['presentes'], 0, 0, 'C', true);
        $pdf->SetTextColor(...$ACCENT);
        $pdf->Cell(19, 6, $kmEx, 0, 0, 'R', true);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(...$GOLD);
        $pdf->Cell(22, 6, number_format($totEv, 0, ',', '.') . ' km', 0, 0, 'R', true);
        $pdf->Ln();
    }

    // Total geral
    $pdf->SetFillColor(...$GOLD);
    $pdf->SetTextColor(13, 15, 20);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(55 + 20 + 36 + 19 + 19 + 19, 7, 'TOTAL GERAL', 0, 0, 'R', true);
    $pdf->Cell(22, 7, number_format($totalDist, 0, ',', '.') . ' km', 0, 0, 'R', true);
    $pdf->Ln(8);

    // Nota rodapé
    $noteY = $pdf->GetY();
    $pdf->SetFillColor(240, 242, 250);
    $pdf->RoundedRect(12, $noteY, 186, 14, 2, '1111', 'F');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(...$MUTED);
    $pdf->SetXY(16, $noteY + 3);
    $pdf->MultiCell(178, 4.5,
        'Relatorio gerado automaticamente em ' . $geradoEm . ' | Mutantes KM Tracker v' . APP_VERSION .
        ' | KM Extra = quilometros adicionais individuais. KM Total = KM padrao x presentes + KM extras.' .
        ' | Confirmacoes de Sexta-feira: ' . $totalSextasParticipantes . ' no total.',
        0, 'L');
}

// ── Output ───────────────────────────────────────────────────
$suffix = $filterUserName ? '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $filterUserName) : '';
$suffix .= $filterEventName ? '_ev' . preg_replace('/[^a-zA-Z0-9]/', '_', $filterEventName) : '';
$filename = 'mutantes_km_' . $year . $suffix . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
exit;