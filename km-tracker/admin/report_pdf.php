<?php
// admin/report_pdf.php — Relatório PDF KM Tracker
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

$tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die('<div style="font-family:Arial,sans-serif;padding:40px;max-width:640px;margin:40px auto;background:#fff3cd;border:1px solid #ffc107;border-radius:8px">
        <h2 style="color:#856404">TCPDF não encontrado</h2>
        <ol style="line-height:2.2;margin-top:12px">
            <li>Baixe em: <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip">github.com/tecnickcom/TCPDF</a></li>
            <li>Extraia e renomeie para tcpdf</li>
            <li>Cole em km-system/tcpdf/</li>
        </ol>
        <p><a href="' . BASE_URL . '/admin/reports.php" style="background:#856404;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;display:inline-block;margin-top:12px">Voltar</a></p>
    </div>');
}
require_once $tcpdfPath;

$db       = db();
$year     = (int)($_GET['year']     ?? date('Y'));
$userId   = (int)($_GET['user_id']  ?? 0);
$eventId  = (int)($_GET['event_id'] ?? 0);
$dateFrom = sanitizeDate($_GET['date_from'] ?? '');
$dateTo   = sanitizeDate($_GET['date_to']   ?? '');

$mesesNomes = ['','Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$mesesAbrev = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

// Ranking
$ranking = $db->query("
    SELECT u.name, u.id, u.role,
           COUNT(CASE WHEN a.status='confirmado' AND e.event_date<=CURDATE() THEN 1 END) AS presencas,
           COALESCE(SUM(CASE WHEN a.status='confirmado' AND e.event_date<=CURDATE() THEN e.km_awarded+a.km_extra ELSE 0 END),0) AS total_km,
           COALESCE(SUM(CASE WHEN a.status='confirmado' AND e.event_date<=CURDATE() THEN e.km_awarded ELSE 0 END),0) AS km_eventos,
           COALESCE(SUM(CASE WHEN a.status='confirmado' AND e.event_date<=CURDATE() THEN a.km_extra ELSE 0 END),0) AS km_extra
    FROM users u
    LEFT JOIN attendances a ON a.user_id=u.id
    LEFT JOIN events e ON e.id=a.event_id AND YEAR(e.event_date)=$year
    WHERE u.active=1
    GROUP BY u.id,u.name,u.role
    ORDER BY total_km DESC
")->fetchAll();

// Eventos
$evStmt = $db->prepare("
    SELECT e.title, e.event_date, e.location, e.km_awarded,
           COUNT(CASE WHEN a.status='confirmado' THEN 1 END) AS presentes,
           COALESCE(SUM(CASE WHEN a.status='confirmado' THEN a.km_extra ELSE 0 END),0) AS total_extra
    FROM events e
    LEFT JOIN attendances a ON a.event_id=e.id
    WHERE YEAR(e.event_date)=? AND e.active=1
    GROUP BY e.id ORDER BY e.event_date ASC
");
$evStmt->execute([$year]);
$eventStats = $evStmt->fetchAll();

// KM por mes
$kmMensalStmt = $db->prepare("
    SELECT MONTH(e.event_date) AS mes,
           COALESCE(SUM(CASE WHEN a.status='confirmado' THEN e.km_awarded+a.km_extra ELSE 0 END),0) AS km
    FROM attendances a JOIN events e ON e.id=a.event_id
    WHERE YEAR(e.event_date)=? AND e.active=1 GROUP BY MONTH(e.event_date)
");
$kmMensalStmt->execute([$year]);
$kmPorMes = array_fill(1,12,0);
foreach ($kmMensalStmt->fetchAll() as $r) $kmPorMes[(int)$r['mes']] = (float)$r['km'];

// Sextas
$mesAtual = (int)date('m'); $anoAtual = (int)date('Y');
$sextasStats = $db->query("
    SELECT sc.data_sexta, COUNT(sc.id) as total FROM sextas_confirmacoes sc
    WHERE MONTH(sc.data_sexta)=$mesAtual AND YEAR(sc.data_sexta)=$anoAtual AND sc.status='confirmado'
    GROUP BY sc.data_sexta ORDER BY sc.data_sexta ASC
")->fetchAll();
$sextasMap = [];
foreach ($sextasStats as $s) $sextasMap[$s['data_sexta']] = $s['total'];
$sextasDoMes = [];
$dt = new DateTime("first friday of $anoAtual-$mesAtual-01");
while ($dt->format('m') == $mesAtual) {
    $d = $dt->format('Y-m-d');
    $sextasDoMes[] = ['data'=>$d,'total'=>$sextasMap[$d]??0];
    $dt->modify('+7 days');
}
$totalSextasParticipantes = array_sum(array_column($sextasDoMes,'total'));

// Nomes filtros
$filterUserName = '';
if ($userId>0) { $un=$db->prepare('SELECT name FROM users WHERE id=?'); $un->execute([$userId]); $filterUserName=$un->fetchColumn()?:''; }
$filterEventName = '';
if ($eventId>0) { $en=$db->prepare('SELECT title FROM events WHERE id=?'); $en->execute([$eventId]); $filterEventName=$en->fetchColumn()?:''; }

$filtrosAtivos = ["Ano: $year"];
if ($filterUserName)  $filtrosAtivos[] = "Membro: $filterUserName";
if ($filterEventName) $filtrosAtivos[] = "Evento: $filterEventName";
if ($dateFrom) $filtrosAtivos[] = "De: ".date('d/m/Y',strtotime($dateFrom));
if ($dateTo)   $filtrosAtivos[] = "Ate: ".date('d/m/Y',strtotime($dateTo));
$filtrosStr = implode('  |  ', $filtrosAtivos);

$totalKm        = array_sum(array_column($ranking,'total_km'));
$totalPresencas = array_sum(array_column($ranking,'presencas'));
$totalEventos   = count($eventStats);
$totalUsers     = count($ranking);
$geradoEm       = date('d/m/Y \a\s H:i');

// Logo
// Logo dinâmica das configurações
$logoRelPath = setting('logo_relatorio_path', '');
$logoPadrao  = setting('logo_path', '');
$logoFile = '';
if ($logoRelPath && file_exists(__DIR__ . '/../' . $logoRelPath)) $logoFile = __DIR__ . '/../' . $logoRelPath;
elseif ($logoPadrao && file_exists(__DIR__ . '/../' . $logoPadrao)) $logoFile = __DIR__ . '/../' . $logoPadrao;
elseif (file_exists(__DIR__ . '/../assets/logo_alta.png')) $logoFile = __DIR__ . '/../assets/logo_alta.png';
elseif (file_exists(__DIR__ . '/../assets/logo.png')) $logoFile = __DIR__ . '/../assets/logo.png';
$logoData = $logoFile ? base64_encode(file_get_contents($logoFile)) : '';

// Paleta — cores dinâmicas das configurações
function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return [(int)hexdec(substr($hex,0,2)), (int)hexdec(substr($hex,2,2)), (int)hexdec(substr($hex,4,2))];
}
$GOLD   = hexToRgb(setting('relatorio_cor_primaria','#f39c12'));
$ACCENT2= hexToRgb(setting('relatorio_cor_secundaria','#e67e22'));
$DARK   = [ 13, 15, 20]; $DARK2  = [ 20, 22, 28];
$GRAY   = [ 42, 47, 58]; $MUTED  = [110,116,133]; $LIGHT = [238,240,248];
$WHITE  = [255,255,255]; $GREEN  = [ 39,174, 96]; $BLUE  = [ 52,152,219];
$RED    = [231, 76, 60]; $PURPLE = [142, 68,173];
$PALETTE= [$GOLD,$ACCENT2,[52,152,219],[39,174,96],[231,76,60],[142,68,173],[26,188,156],[41,128,185]];

class KMTrackerPDF extends TCPDF {
    public int $year=0; public string $genDate=''; public string $filtros='';
    public bool $isCover=true; public string $logoData='';

    public function Header(): void {
        if ($this->isCover) return;
        $this->SetFillColor(13,15,20); $this->Rect(0,0,210,16,'F');
        $this->SetFillColor(243,156,18); $this->Rect(0,15.5,210,0.8,'F');
        if ($this->logoData) $this->Image('@'.base64_decode($this->logoData),8,2,11,11,'PNG');
        $this->SetFont('helvetica','B',8); $this->SetTextColor(243,156,18);
        $this->SetXY(22,3); $this->Cell(80,5,strtoupper(setting('clube_nome','KM Tracker')),0,0,'L');
        $this->SetFont('helvetica','',6.5); $this->SetTextColor(110,116,133);
        $this->SetXY(22,9); $this->Cell(80,4,'KM Tracker  |  '.$this->filtros,0,0,'L');
        $this->SetXY(120,3); $this->Cell(80,5,$this->genDate,0,0,'R');
        $this->SetXY(120,9); $this->Cell(80,4,'Pag. '.$this->getAliasNumPage().' de '.$this->getAliasNbPages(),0,0,'R');
    }
    public function Footer(): void {
        if ($this->isCover) return;
        $this->SetY(-10); $this->SetFillColor(20,22,28); $this->Rect(0,$this->GetY(),210,12,'F');
        $this->SetFillColor(243,156,18); $this->Rect(0,$this->GetY(),210,0.5,'F');
        $this->SetFont('helvetica','',6); $this->SetTextColor(110,116,133);
        $this->SetY($this->GetY()+2);
        $this->Cell(0,5,setting('sistema_nome','KM Tracker').' v'.APP_VERSION.'  |  '.$this->genDate.'  |  Pag. '.$this->getAliasNumPage().' de '.$this->getAliasNbPages(),0,0,'C');
    }
}

$pdf = new KMTrackerPDF('P','mm','A4',true,'UTF-8',false);
$pdf->year=$year; $pdf->genDate=$geradoEm; $pdf->filtros=$filtrosStr; $pdf->logoData=$logoData;
$pdf->SetCreator(setting('sistema_nome','KM Tracker')); $pdf->SetAuthor(setting('clube_nome','KM Tracker'));
$pdf->SetTitle(setting('sistema_nome','KM Tracker').' — Relatório '.$year);
$pdf->SetMargins(12,20,12); $pdf->SetHeaderMargin(0); $pdf->SetFooterMargin(12);
$pdf->SetAutoPageBreak(true,18);
$pdf->setPrintHeader(false); $pdf->setPrintFooter(false);

// ════ CAPA ════
$pdf->isCover=true; $pdf->AddPage();
$pdf->SetFillColor(13,15,20); $pdf->Rect(0,0,210,297,'F');
$pdf->SetFillColor(243,156,18); $pdf->Rect(0,0,210,6,'F');
if ($logoData) $pdf->Image('@'.base64_decode($logoData),55,14,100,110,'PNG');
$pdf->SetDrawColor(42,47,58); $pdf->SetLineWidth(0.3); $pdf->Line(20,132,190,132);
$pdf->SetFont('helvetica','B',30); $pdf->SetTextColor(243,156,18);
$pdf->SetXY(0,137); $pdf->Cell(210,14,strtoupper(setting('clube_nome','KM Tracker')),0,1,'C');
$pdf->SetFont('helvetica','',11); $pdf->SetTextColor(238,240,248);
$pdf->SetXY(0,153); $pdf->Cell(210,7,setting('relatorio_titulo', 'Relatório de Quilometragem e Presença em Eventos'),0,1,'C');
$pdf->SetFont('helvetica','B',70); $pdf->SetTextColor(28,32,42);
$pdf->SetXY(0,158); $pdf->Cell(210,42,(string)$year,0,1,'C');
if (count($filtrosAtivos)>1) {
    $pdf->SetFont('helvetica','I',8); $pdf->SetTextColor(110,116,133);
    $pdf->SetXY(0,202); $pdf->Cell(210,6,$filtrosStr,0,1,'C');
}
$cards=[
    [number_format($totalKm,0,',','.').' km','KM TOTAL',$GOLD],
    [(string)$totalPresencas,'PRESENCAS',$BLUE],
    [(string)$totalEventos,'EVENTOS',$GREEN],
    [(string)$totalUsers,'MEMBROS',$PURPLE],
];
$cW=40; $cH=28; $cGap=6; $cTotal=count($cards)*$cW+(count($cards)-1)*$cGap;
$cStart=(210-$cTotal)/2; $cY=213;
foreach ($cards as $i=>$c) {
    $cx=$cStart+$i*($cW+$cGap);
    $pdf->SetFillColor(22,25,34); $pdf->RoundedRect($cx,$cY,$cW,$cH,3,'1111','F');
    $pdf->SetFillColor(...$c[2]); $pdf->RoundedRect($cx,$cY,$cW,2.5,3,'1111','F'); $pdf->Rect($cx,$cY+1,$cW,2,'F');
    $pdf->SetFont('helvetica','B',14); $pdf->SetTextColor(...$c[2]);
    $pdf->SetXY($cx,$cY+6); $pdf->Cell($cW,10,$c[0],0,0,'C');
    $pdf->SetFont('helvetica','',6); $pdf->SetTextColor(110,116,133);
    $pdf->SetXY($cx,$cY+18); $pdf->Cell($cW,5,$c[1],0,0,'C');
}
$pdf->SetFont('helvetica','',7.5); $pdf->SetTextColor(110,116,133);
$pdf->SetXY(0,254); $pdf->Cell(210,6,'Gerado em '.$geradoEm.'  |  '.setting('sistema_nome','KM Tracker'),0,0,'C');
$pdf->SetFillColor(243,156,18); $pdf->Rect(0,291,210,6,'F');

// ════ PÁGINAS CONTEÚDO ════
$pdf->isCover=false; $pdf->setPrintHeader(true); $pdf->setPrintFooter(true);

$drawSection = function(string $txt, string $sub='') use ($pdf,$GOLD,$DARK,$MUTED) {
    $y=$pdf->GetY()+3;
    $pdf->SetFillColor(...$GOLD); $pdf->Rect(12,$y,3.5,8,'F');
    $pdf->SetFont('helvetica','B',10.5); $pdf->SetTextColor(...$DARK);
    $pdf->SetXY(18,$y+0.5); $pdf->Cell(170,6,$txt,0,0,'L');
    if ($sub) { $pdf->SetFont('helvetica','',7); $pdf->SetTextColor(...$MUTED); $pdf->SetXY(18,$y+7); $pdf->Cell(170,4,$sub,0,0,'L'); }
    $pdf->SetDrawColor(228,230,240); $pdf->SetLineWidth(0.2); $pdf->Line(12,$y+9,198,$y+9);
    $pdf->SetY($y+13);
};

$drawTableHeader = function(array $cols) use ($pdf,$DARK,$GOLD) {
    $pdf->SetFillColor(...$DARK); $pdf->SetTextColor(...$GOLD); $pdf->SetFont('helvetica','B',6.5);
    foreach ($cols as $lbl=>$cfg) {
        $w=is_array($cfg)?$cfg[0]:$cfg; $a=is_array($cfg)?$cfg[1]:'C';
        $pdf->Cell($w,7,$lbl,0,0,$a,true);
    }
    $pdf->Ln();
};

// ════ PÁG 2 — VISÃO GERAL ════
$pdf->AddPage();
$pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22);

$kpis=[[number_format($totalKm,0,',','.').' km','KM Total',$GOLD],[(string)$totalPresencas,'Presenças',$BLUE],[(string)$totalEventos,'Eventos',$GREEN],[(string)$totalUsers,'Membros',$PURPLE]];
$kpiW=44; $kpiH=20; $kpiGap=4; $kpiStart=12;
foreach ($kpis as $i=>$k) {
    $kx=$kpiStart+$i*($kpiW+$kpiGap);
    $pdf->SetFillColor(255,255,255); $pdf->RoundedRect($kx,22,$kpiW,$kpiH,2,'1111','F');
    $pdf->SetFillColor(...$k[2]); $pdf->RoundedRect($kx,22,$kpiW,2,2,'1111','F'); $pdf->Rect($kx,23,$kpiW,1,'F');
    $pdf->SetFont('helvetica','B',13); $pdf->SetTextColor(...$k[2]);
    $pdf->SetXY($kx,26); $pdf->Cell($kpiW,8,$k[0],0,0,'C');
    $pdf->SetFont('helvetica','',6); $pdf->SetTextColor(110,116,133);
    $pdf->SetXY($kx,35); $pdf->Cell($kpiW,4,strtoupper($k[1]),0,0,'C');
}
$pdf->SetY(50);

// Gráfico KM mensal
$drawSection('Evolução Mensal de KM — '.$year, $filterUserName?'Filtro: '.$filterUserName:'');
$gX=12; $gY=$pdf->GetY(); $gW=186; $gH=52; $pL=20; $pR=5; $pT=5; $pB=14;
$iW=$gW-$pL-$pR; $iH=$gH-$pT-$pB; $maxVal=max(array_values($kmPorMes)?:[1]);
$pdf->SetFillColor(255,255,255); $pdf->RoundedRect($gX,$gY,$gW,$gH,2,'1111','F');
for ($g=0;$g<=4;$g++) {
    $gy=$gY+$pT+($iH/4)*$g;
    $pdf->SetDrawColor(235,237,245); $pdf->SetLineWidth(0.2); $pdf->Line($gX+$pL,$gy,$gX+$gW-$pR,$gy);
    $pdf->SetFont('helvetica','',5); $pdf->SetTextColor(110,116,133);
    $pdf->SetXY($gX,$gy-2); $pdf->Cell($pL-2,4,number_format($maxVal*(1-$g/4),0,',','.'),'',0,'R');
}
$barSlot=$iW/12; $barW=$barSlot*0.6;
for ($m=1;$m<=12;$m++) {
    $bx=$gX+$pL+($m-1)*$barSlot+($barSlot-$barW)/2;
    $bh=$maxVal>0?($kmPorMes[$m]/$maxVal)*$iH:0;
    $by=$gY+$pT+$iH-$bh;
    $pdf->SetFillColor(235,237,245); $pdf->RoundedRect($bx,$gY+$pT,$barW,$iH,1,'1111','F');
    if ($bh>0) { $pdf->SetFillColor(243,156,18); $pdf->RoundedRect($bx,$by,$barW,$bh,1,'1111','F'); }
    $pdf->SetFont('helvetica','',5); $pdf->SetTextColor(110,116,133);
    $pdf->SetXY($bx-2,$gY+$gH-$pB+2); $pdf->Cell($barW+4,4,$mesesAbrev[$m],'',0,'C');
}
$pdf->SetY($gY+$gH+5);

// Sextas
if (!empty($sextasDoMes)) {
    $drawSection('Participação nas Sextas-feiras — '.$mesesNomes[$mesAtual].'/'.$anoAtual);
    $colsSx=['Data'=>[38,'C'],'Dia da Semana'=>[55,'C'],'Confirmados'=>[50,'C'],'Status'=>[43,'C']];
    $drawTableHeader($colsSx);
    $diasSemana=['Monday'=>'Segunda','Tuesday'=>'Terca','Wednesday'=>'Quarta','Thursday'=>'Quinta','Friday'=>'Sexta','Saturday'=>'Sabado','Sunday'=>'Domingo'];
    foreach ($sextasDoMes as $i=>$sx) {
        if ($pdf->GetY()>268) { $pdf->AddPage(); $pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22); $drawTableHeader($colsSx); }
        $even=$i%2===0;
        $pdf->SetFillColor($even?255:245,$even?255:246,$even?255:252);
        $dObj=new DateTime($sx['data']); $nomeDia=($diasSemana[$dObj->format('l')]??$dObj->format('l')).'-feira';
        $pdf->SetFont('helvetica','',7); $pdf->SetTextColor(50,55,70);
        $pdf->Cell(38,6.5,$dObj->format('d/m/Y'),0,0,'C',true);
        $pdf->Cell(55,6.5,$nomeDia,0,0,'C',true);
        if ($sx['total']>0) {
            $pdf->SetFont('helvetica','B',7); $pdf->SetTextColor(243,156,18);
            $pdf->Cell(50,6.5,(string)$sx['total'],0,0,'C',true);
            $pdf->SetTextColor(39,174,96); $pdf->Cell(43,6.5,'Confirmada',0,1,'C',true);
        } else {
            $pdf->SetFont('helvetica','',7); $pdf->SetTextColor(110,116,133);
            $pdf->Cell(50,6.5,'0',0,0,'C',true);
            $pdf->SetTextColor(243,156,18); $pdf->Cell(43,6.5,'Aguardando',0,1,'C',true);
        }
    }
    $pdf->SetFillColor(13,15,20); $pdf->SetFont('helvetica','B',7);
    $pdf->SetTextColor(243,156,18); $pdf->Cell(93,7,'TOTAL:',0,0,'R',true);
    $pdf->SetTextColor(255,255,255); $pdf->Cell(50,7,(string)$totalSextasParticipantes,0,0,'C',true);
    $pdf->Cell(43,7,'',0,1,'C',true);
    $pdf->Ln(4);
}

// ════ PÁG 3 — RANKING ════
if (!empty($ranking)) {
    $pdf->AddPage();
    $pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22);
    $drawSection('Ranking de KM — '.$year, $filterEventName?'Evento: '.$filterEventName:'Todos os eventos');

    // Pódio top 3
    $podio=array_slice($ranking,0,3);
    $pY=$pdf->GetY(); $pW=55; $pGap=6;
    $pCols=[$GOLD,[192,192,192],[180,120,60]]; $pLbls=['1 LUGAR','2 LUGAR','3 LUGAR']; $pHs=[32,26,22];
    $pStart=(210-(count($podio)*$pW+(count($podio)-1)*$pGap))/2;
    foreach ($podio as $i=>$row) {
        $px=$pStart+$i*($pW+$pGap); $py=$pY+(32-$pHs[$i]);
        $pdf->SetFillColor(255,255,255); $pdf->RoundedRect($px,$py,$pW,$pHs[$i]+20,2,'1111','F');
        $pdf->SetFillColor(...$pCols[$i]); $pdf->RoundedRect($px,$py,$pW,3,2,'1111','F'); $pdf->Rect($px,$py+1.5,$pW,1.5,'F');
        $pdf->SetFont('helvetica','B',7); $pdf->SetTextColor(...$pCols[$i]);
        $pdf->SetXY($px,$py+4); $pdf->Cell($pW,5,$pLbls[$i],0,0,'C');
        $nm=mb_strlen($row['name'])>18?mb_substr($row['name'],0,16).'.':$row['name'];
        $pdf->SetFont('helvetica','B',8); $pdf->SetTextColor(30,35,50);
        $pdf->SetXY($px,$py+10); $pdf->Cell($pW,5,$nm,0,0,'C');
        $pdf->SetFont('helvetica','B',11); $pdf->SetTextColor(...$pCols[$i]);
        $pdf->SetXY($px,$py+17); $pdf->Cell($pW,6,number_format($row['total_km'],0,',','.').' km',0,0,'C');
    }
    $pdf->SetY($pY+56);

    // Barras horizontais
    $drawSection('Distribuição de KM por Membro');
    $top=array_slice($ranking,0,15);
    $maxKm=!empty($top)?(float)$top[0]['total_km']:1;
    $lbW=50; $barW2=110; $valW2=26; $bH=5.5; $bGap=2.5;
    $bgY=$pdf->GetY(); $bgH=count($top)*($bH+$bGap)+8;
    $pdf->SetFillColor(255,255,255); $pdf->RoundedRect(12,$bgY,$lbW+$barW2+$valW2+4,$bgH,2,'1111','F');
    foreach ($top as $i=>$row) {
        $rowY=$bgY+4+$i*($bH+$bGap); $pct=$maxKm>0?$row['total_km']/$maxKm:0;
        $bw=max(1,$pct*$barW2);
        $col=$i===0?$GOLD:($i===1?[160,165,180]:($i===2?[180,120,60]:$PALETTE[$i%count($PALETTE)]));
        $nm=mb_strlen($row['name'])>22?mb_substr($row['name'],0,20).'.':$row['name'];
        $pdf->SetFont('helvetica',$i<3?'B':'',6.5); $pdf->SetTextColor(110,116,133);
        $pdf->SetXY(12,$rowY); $pdf->Cell($lbW,5.5,($i+1).'. '.$nm,0,0,'L');
        $pdf->SetFillColor(235,237,245); $pdf->RoundedRect(12+$lbW,$rowY+0.5,$barW2,$bH-1,1,'1111','F');
        $pdf->SetFillColor(...$col); $pdf->RoundedRect(12+$lbW,$rowY+0.5,$bw,$bH-1,1,'1111','F');
        $pdf->SetFont('helvetica','B',6); $pdf->SetTextColor(...$col);
        $pdf->SetXY(12+$lbW+$barW2+2,$rowY); $pdf->Cell($valW2,5.5,number_format($row['total_km'],0,',','.').' km',0,0,'L');
    }
    $pdf->SetY($bgY+$bgH+5);

    // Tabela detalhada
    if ($pdf->GetY()>200) { $pdf->AddPage(); $pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22); }
    $drawSection('Tabela Detalhada de Membros');
    $colsM=['#'=>[7,'C'],'Membro'=>[52,'L'],'Perfil'=>[22,'C'],'Presenças'=>[20,'C'],'KM Eventos'=>[25,'R'],'KM Extra'=>[22,'R'],'KM Total'=>[28,'R'],'%'=>[10,'C']];
    $drawTableHeader($colsM);
    $maxKm1=!empty($ranking)?(float)$ranking[0]['total_km']:1;
    foreach ($ranking as $i=>$row) {
        if ($pdf->GetY()>268) { $pdf->AddPage(); $pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22); $drawTableHeader($colsM); }
        $even=$i%2===0; $pdf->SetFillColor($even?255:246,$even?255:247,$even?255:252);
        $pct2=$maxKm1>0?round($row['total_km']/$maxKm1*100):0;
        $isAdmin=$row['role']==='admin';
        $nCol=$i===0?$GOLD:($i===1?[160,165,180]:($i===2?[180,120,60]:$MUTED));
        $pdf->SetFont('helvetica','B',6.5); $pdf->SetTextColor(...$nCol);
        $pdf->Cell(7,6,(string)($i+1),0,0,'C',true);
        $pdf->SetFont('helvetica',$i<3?'B':'',7); $pdf->SetTextColor(30,35,50);
        $pdf->Cell(52,6,$row['name'],0,0,'L',true);
        $pdf->SetFont('helvetica','',6); $pdf->SetTextColor($isAdmin?142:52,$isAdmin?68:152,$isAdmin?173:219);
        $pdf->Cell(22,6,$isAdmin?'Admin':'Membro',0,0,'C',true);
        $pdf->SetTextColor(52,152,219); $pdf->SetFont('helvetica','',7);
        $pdf->Cell(20,6,(string)$row['presencas'],0,0,'C',true);
        $pdf->SetTextColor(50,55,70);
        $pdf->Cell(25,6,number_format($row['km_eventos'],0,',','.').' km',0,0,'R',true);
        $pdf->SetTextColor(52,152,219);
        $pdf->Cell(22,6,$row['km_extra']>0?'+'.number_format($row['km_extra'],0,',','.'):'-',0,0,'R',true);
        $pdf->SetFont('helvetica','B',7); $pdf->SetTextColor(243,156,18);
        $pdf->Cell(28,6,number_format($row['total_km'],0,',','.').' km',0,0,'R',true);
        $pdf->SetFont('helvetica','',6); $pdf->SetTextColor(110,116,133);
        $pdf->Cell(10,6,$pct2.'%',0,1,'C',true);
    }
    $pdf->SetFillColor(13,15,20); $pdf->SetTextColor(243,156,18); $pdf->SetFont('helvetica','B',7);
    $pdf->Cell(7+52+22+20+25+22,7,'TOTAL',0,0,'R',true);
    $pdf->SetTextColor(255,255,255);
    $pdf->Cell(28,7,number_format($totalKm,0,',','.').' km',0,0,'R',true);
    $pdf->Cell(10,7,'',0,1,'C',true);
    $pdf->Ln(4);
}

// ════ PÁG 4 — EVENTOS ════
if (!empty($eventStats)) {
    $pdf->AddPage();
    $pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22);
    $drawSection('Presença por Evento — '.$year,$filterUserName?'Filtro: '.$filterUserName:'');

    // Gráfico barras verticais
    $gX2=12; $gY2=$pdf->GetY(); $gW2=186; $gH2=52;
    $pL2=8; $pR2=5; $pT2=5; $pB2=14;
    $iW2=$gW2-$pL2-$pR2; $iH2=$gH2-$pT2-$pB2;
    $pdf->SetFillColor(255,255,255); $pdf->RoundedRect($gX2,$gY2,$gW2,$gH2,2,'1111','F');
    $maxPres=max(array_column($eventStats,'presentes')?:[1]);
    $n=count($eventStats); $slotW2=$iW2/max($n,1); $bwE=max(6,min(20,$slotW2*0.65));
    foreach ($eventStats as $ei=>$ev) {
        $bx=$gX2+$pL2+$ei*$slotW2+($slotW2-$bwE)/2;
        $bh=$maxPres>0?($ev['presentes']/$maxPres)*$iH2:0; $by=$gY2+$pT2+$iH2-$bh;
        $col=$PALETTE[$ei%count($PALETTE)];
        $pdf->SetFillColor(235,237,245); $pdf->RoundedRect($bx,$gY2+$pT2,$bwE,$iH2,1,'1111','F');
        if ($bh>0) { $pdf->SetFillColor(...$col); $pdf->RoundedRect($bx,$by,$bwE,$bh,1,'1111','F'); }
        if ($ev['presentes']>0) {
            $pdf->SetFont('helvetica','B',5.5); $pdf->SetTextColor(...$col);
            $pdf->SetXY($bx-2,max($gY2+$pT2+1,$by-5)); $pdf->Cell($bwE+4,4,(string)$ev['presentes'],0,0,'C');
        }
        $lbl=mb_strlen($ev['title'])>12?mb_substr($ev['title'],0,10).'.':$ev['title'];
        $pdf->SetFont('helvetica','',5); $pdf->SetTextColor(110,116,133);
        $pdf->SetXY($bx-3,$gY2+$gH2-$pB2+2); $pdf->Cell($bwE+6,10,$lbl,0,0,'C');
    }
    $pdf->SetY($gY2+$gH2+5);

    // Tabela eventos
    $drawSection('Detalhamento por Evento');
    $colsEv=['Evento'=>[55,'L'],'Data'=>[20,'C'],'Local'=>[36,'L'],'KM Rota'=>[20,'R'],'Presentes'=>[18,'C'],'KM Extra'=>[20,'R'],'KM Total'=>[25,'R']];
    $drawTableHeader($colsEv);
    $totalDist=0;
    foreach ($eventStats as $ei=>$ev) {
        if ($pdf->GetY()>268) { $pdf->AddPage(); $pdf->SetFillColor(248,249,252); $pdf->Rect(0,0,210,297,'F'); $pdf->SetY(22); $drawTableHeader($colsEv); }
        $even=$ei%2===0; $pdf->SetFillColor($even?255:246,$even?255:247,$even?255:252);
        $totEv=($ev['km_awarded']*$ev['presentes'])+$ev['total_extra']; $totalDist+=$totEv;
        $loc=mb_strlen($ev['location']??'')>20?mb_substr((string)$ev['location'],0,18).'.':($ev['location']??'-');
        $pdf->SetFont('helvetica','B',7); $pdf->SetTextColor(30,35,50);
        $pdf->Cell(55,6.5,$ev['title'],0,0,'L',true);
        $pdf->SetFont('helvetica','',7); $pdf->SetTextColor(110,116,133);
        $pdf->Cell(20,6.5,date('d/m/Y',strtotime($ev['event_date'])),0,0,'C',true);
        $pdf->Cell(36,6.5,$loc,0,0,'L',true);
        $pdf->SetTextColor(243,156,18);
        $pdf->Cell(20,6.5,number_format($ev['km_awarded'],0,',','.'),0,0,'R',true);
        $pdf->SetTextColor(39,174,96);
        $pdf->Cell(18,6.5,(string)$ev['presentes'],0,0,'C',true);
        $pdf->SetTextColor(52,152,219);
        $pdf->Cell(20,6.5,$ev['total_extra']>0?'+'.number_format($ev['total_extra'],0,',','.'):'-',0,0,'R',true);
        $pdf->SetFont('helvetica','B',7); $pdf->SetTextColor(243,156,18);
        $pdf->Cell(25,6.5,number_format($totEv,0,',','.').' km',0,0,'R',true);
        $pdf->Ln();
    }
    $pdf->SetFillColor(13,15,20); $pdf->SetTextColor(243,156,18); $pdf->SetFont('helvetica','B',7.5);
    $pdf->Cell(55+20+36+20+18+20,7.5,'TOTAL GERAL',0,0,'R',true);
    $pdf->SetTextColor(255,255,255);
    $pdf->Cell(25,7.5,number_format($totalDist,0,',','.').' km',0,0,'R',true);
    $pdf->Ln(8);

    $noteY=$pdf->GetY();
    $pdf->SetFillColor(240,242,250); $pdf->RoundedRect(12,$noteY,186,12,2,'1111','F');
    $pdf->SetFont('helvetica','',6.5); $pdf->SetTextColor(110,116,133);
    $pdf->SetXY(16,$noteY+3);
    $pdf->MultiCell(178,4,'KM Total = KM padrao x presentes + KM extras  |  Relatorio gerado em '.$geradoEm.'  |  '.setting('sistema_nome','KM Tracker'),0,'L');
}

$suffix=$filterUserName?'_'.preg_replace('/[^a-zA-Z0-9]/','_',$filterUserName):'';
$suffix.=$filterEventName?'_'.preg_replace('/[^a-zA-Z0-9]/','_',$filterEventName):'';
$sistNome = preg_replace('/[^a-zA-Z0-9_]/', '_', setting('sistema_nome','km_tracker'));
$filename = strtolower($sistNome).'_'.$year.$suffix.'_'.date('Ymd_His').'.pdf';
$pdf->Output($filename,'D');
