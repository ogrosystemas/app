<?php
require_once '../../config/config.php';
checkAuth();

$baseUrl  = defined('BASE_URL') ? BASE_URL : '';
$mensagem = $_SESSION['mensagem'] ?? null;
$erro     = $_SESSION['erro']     ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);

// ── Excluir cliente ──────────────────────────────────────────────────────────
if (isset($_GET['excluir_cliente'])) {
    $eid = (int)$_GET['excluir_cliente'];
    try {
        $db->prepare("DELETE FROM motos    WHERE cliente_id = ?")->execute([$eid]);
        $db->prepare("DELETE FROM clientes WHERE id = ?")        ->execute([$eid]);
        $_SESSION['mensagem'] = 'Cliente excluído com sucesso!';
    } catch (PDOException $e) { $_SESSION['erro'] = 'Erro ao excluir: ' . $e->getMessage(); }
    header('Location: clientes.php'); exit;
}

// ── Salvar / Atualizar ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_cliente'])) {
    csrfVerify();
    $id = (int)($_POST['id'] ?? 0);
    $campos = [
        'nome'        => trim($_POST['nome']        ?? ''),
        'tipo'        => in_array($_POST['tipo']??'',['pf','pj']) ? $_POST['tipo'] : 'pf',
        'cpf_cnpj'    => preg_replace('/\D/','', $_POST['cpf_cnpj']    ?? ''),
        'rg_ie'       => trim($_POST['rg_ie']       ?? ''),
        'telefone'    => preg_replace('/\D/','', $_POST['telefone']    ?? ''),
        'celular'     => preg_replace('/\D/','', $_POST['celular']     ?? ''),
        'email'       => trim($_POST['email']       ?? ''),
        'cep'         => preg_replace('/\D/','', $_POST['cep']         ?? ''),
        'endereco'    => trim($_POST['endereco']    ?? ''),
        'numero'      => trim($_POST['numero']      ?? ''),
        'complemento' => trim($_POST['complemento'] ?? ''),
        'bairro'      => trim($_POST['bairro']      ?? ''),
        'cidade'      => trim($_POST['cidade']      ?? ''),
        'estado'      => strtoupper(substr(trim($_POST['estado'] ?? ''),0,2)),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ];
    if (!$campos['nome']) {
        $_SESSION['erro'] = 'O nome é obrigatório.';
    } else {
        try {
            if ($id) {
                $set = implode(', ', array_map(fn($k)=>"$k = ?", array_keys($campos)));
                $db->prepare("UPDATE clientes SET $set WHERE id = ?")->execute([...array_values($campos),$id]);
                $_SESSION['mensagem'] = 'Cliente atualizado!';
            } else {
                $cols = implode(', ', array_keys($campos));
                $vals = implode(', ', array_fill(0,count($campos),'?'));
                $db->prepare("INSERT INTO clientes ($cols) VALUES ($vals)")->execute(array_values($campos));
                $_SESSION['mensagem'] = 'Cliente cadastrado!';
            }
        } catch (PDOException $e) { $_SESSION['erro'] = 'Erro: ' . $e->getMessage(); }
    }
    header('Location: clientes.php'); exit;
}

// ── Listagem ─────────────────────────────────────────────────────────────────
$busca = trim($_GET['busca'] ?? '');
$where = ''; $params = [];
if ($busca) {
    $where = "WHERE (c.nome LIKE ? OR c.cpf_cnpj LIKE ? OR c.telefone LIKE ? OR c.celular LIKE ?)";
    $params = array_fill(0,4,"%$busca%");
}
$stmt = $db->prepare(
    "SELECT c.*, COUNT(DISTINCT m.id) as qtd_motos, COUNT(DISTINCT os.id) as qtd_os
     FROM clientes c
     LEFT JOIN motos m ON m.cliente_id = c.id
     LEFT JOIN ordens_servico os ON os.cliente_id = c.id
     $where GROUP BY c.id ORDER BY c.nome"
);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Clientes</div>
  <div class="topbar-actions">
    <button class="btn-os btn-os-primary" onclick="abrirModalCliente()">
      <i class="ph-bold ph-user-plus"></i> Novo Cliente
    </button>
  </div>
</header>

<main class="os-content">

<?php if ($mensagem): ?><div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<?php if ($erro):     ?><div class="os-alert os-alert-danger"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?></div><?php endif; ?>

<!-- Busca -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:10px">
      <div style="position:relative;flex:1">
        <i class="ph-bold ph-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
        <input type="text" name="busca" class="os-input" style="padding-left:38px"
               placeholder="Buscar por nome, CPF/CNPJ, telefone..." value="<?= htmlspecialchars($busca) ?>">
      </div>
      <button type="submit" class="btn-os btn-os-primary">Buscar</button>
      <?php if ($busca): ?><a href="clientes.php" class="btn-os btn-os-ghost">Limpar</a><?php endif; ?>
    </form>
  </div>
</div>

<!-- Tabela -->
<div class="os-card">
  <div class="os-card-body" style="padding:0">
    <div style="overflow-x:auto">
      <table class="os-table">
        <thead>
          <tr>
            <th>Nome</th><th>CPF/CNPJ</th><th>Contato</th>
            <th>Cidade/UF</th><th style="text-align:center">Motos</th>
            <th style="text-align:center">OS</th><th style="text-align:center">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($clientes)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">
            <?= $busca ? 'Nenhum cliente encontrado.' : 'Nenhum cliente cadastrado ainda.' ?>
          </td></tr>
          <?php endif; ?>
          <?php foreach ($clientes as $c): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($c['nome']) ?></strong>
              <span class="os-badge <?= ($c['tipo']??'pf')==='pj' ? 'os-badge-blue':'os-badge-gray' ?>" style="margin-left:6px;font-size:.65rem"><?= strtoupper($c['tipo']??'PF') ?></span>
            </td>
            <td style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars($c['cpf_cnpj']??'-') ?></td>
            <td>
              <?php $tel = $c['celular'] ?? $c['telefone'] ?? ''; if ($tel): $f = preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $tel); ?>
              <a href="https://wa.me/55<?= $tel ?>" target="_blank" style="color:var(--success);text-decoration:none;font-size:.82rem">
                <i class="ph-bold ph-whatsapp-logo"></i> <?= htmlspecialchars($f) ?>
              </a>
              <?php else: ?><span style="color:var(--text-muted);font-size:.8rem">—</span><?php endif; ?>
            </td>
            <td style="font-size:.82rem"><?= htmlspecialchars(trim(($c['cidade']??'').($c['estado']?' / '.$c['estado']:''))?:'—') ?></td>
            <td style="text-align:center">
              <button style="background:rgba(56,189,248,.12);color:#38bdf8;border:1px solid rgba(56,189,248,.2);padding:3px 10px;border-radius:6px;cursor:pointer;font-size:.78rem;font-weight:600"
                      onclick="verMotos(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>')">
                <i class="ph-bold ph-motorcycle"></i> <?= (int)$c['qtd_motos'] ?>
              </button>
            </td>
            <td style="text-align:center"><span class="os-badge os-badge-green"><?= (int)$c['qtd_os'] ?> OS</span></td>
            <td style="text-align:center">
              <div style="display:flex;gap:5px;justify-content:center">
                <button class="btn-os btn-os-ghost" style="padding:5px 8px" title="Editar"
                        onclick='editarCliente(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)'>
                  <i class="ph-bold ph-pencil-simple"></i>
                </button>
                <button class="btn-os btn-os-ghost" style="padding:5px 8px" title="Adicionar moto"
                        onclick="abrirModalMoto(<?= $c['id'] ?>)">
                  <i class="ph-bold ph-plus"></i>
                </button>
                <a href="<?= $baseUrl ?>/modules/os/os.php?novo_cliente_id=<?= $c['id'] ?>"
                   class="btn-os btn-os-ghost" style="padding:5px 8px" title="Nova OS">
                  <i class="ph-bold ph-wrench"></i>
                </a>
                <button style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;padding:5px 8px;border-radius:7px;cursor:pointer" title="Excluir"
                        onclick="confirmarExclusaoCliente(<?= $c['id'] ?>)">
                  <i class="ph-bold ph-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main>

<!-- ═══ MODAL CLIENTE ════════════════════════════════════════════════════════ -->
<div id="modalCliente" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:720px;max-width:100%;margin:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0" id="modalClienteTitulo">Novo Cliente</h5>
      <button onclick="fecharModalCliente()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form method="POST" id="formCliente">
      <input type="hidden" name="salvar_cliente" value="1">
      <input type="hidden" name="id" id="cid">
      <?= csrfField() ?>
      <div style="padding:20px 24px">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px">
          <div class="os-form-group">
            <label class="os-label">Nome *</label>
            <input type="text" name="nome" id="cNome" class="os-input" required>
          </div>
          <div class="os-form-group">
            <label class="os-label">Tipo</label>
            <select name="tipo" id="cTipo" class="os-select" onchange="toggleTipo()">
              <option value="pf">Pessoa Física</option>
              <option value="pj">Pessoa Jurídica</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="os-form-group">
            <label class="os-label" id="lblCpf">CPF</label>
            <input type="text" name="cpf_cnpj" id="cCpfCnpj" class="os-input">
          </div>
          <div class="os-form-group">
            <label class="os-label" id="lblRg">RG</label>
            <input type="text" name="rg_ie" id="cRgIe" class="os-input">
          </div>
          <div class="os-form-group">
            <label class="os-label">Email</label>
            <input type="email" name="email" id="cEmail" class="os-input">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="os-form-group">
            <label class="os-label">Telefone</label>
            <input type="text" name="telefone" id="cTelefone" class="os-input" placeholder="(00) 0000-0000">
          </div>
          <div class="os-form-group">
            <label class="os-label">Celular / WhatsApp</label>
            <input type="text" name="celular" id="cCelular" class="os-input" placeholder="(00) 00000-0000">
          </div>
        </div>
        <!-- Endereço -->
        <div style="background:var(--bg-card2);border-radius:10px;padding:14px;border:1px solid var(--border);margin-bottom:14px">
          <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">Endereço</div>
          <div style="display:grid;grid-template-columns:130px 1fr 1fr;gap:12px;margin-bottom:10px">
            <div class="os-form-group">
              <label class="os-label">CEP</label>
              <input type="text" name="cep" id="cCep" class="os-input" placeholder="00000-000"
                     oninput="maskCep(this)" onblur="buscarCep(this.value)">
            </div>
            <div class="os-form-group">
              <label class="os-label">Logradouro</label>
              <input type="text" name="endereco" id="cEndereco" class="os-input">
            </div>
            <div class="os-form-group">
              <label class="os-label">Bairro</label>
              <input type="text" name="bairro" id="cBairro" class="os-input">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:80px 1fr 80px;gap:12px">
            <div class="os-form-group">
              <label class="os-label">Número</label>
              <input type="text" name="numero" id="cNumero" class="os-input">
            </div>
            <div class="os-form-group">
              <label class="os-label">Cidade</label>
              <input type="text" name="cidade" id="cCidade" class="os-input">
            </div>
            <div class="os-form-group">
              <label class="os-label">UF</label>
              <input type="text" name="estado" id="cEstado" class="os-input" maxlength="2" style="text-transform:uppercase">
            </div>
          </div>
        </div>
        <div class="os-form-group">
          <label class="os-label">Observações</label>
          <textarea name="observacoes" id="cObs" class="os-input" rows="2"></textarea>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid var(--border)">
        <button type="button" class="btn-os btn-os-ghost" onclick="fecharModalCliente()">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-floppy-disk"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL MOTOS ══════════════════════════════════════════════════════════ -->
<div id="modalMotos" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:580px;max-width:95vw;max-height:85vh;display:flex;flex-direction:column">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);flex-shrink:0">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0" id="modalMotosTitulo">Motos</h5>
      <button onclick="document.getElementById('modalMotos').style.display='none'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <div id="listaMotos" style="flex:1;overflow-y:auto;padding:16px 24px"></div>
  </div>
</div>

<!-- ═══ MODAL ADD MOTO ═══════════════════════════════════════════════════════ -->
<div id="modalAdicionarMoto" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3100;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:520px;max-width:95vw">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0">Adicionar Moto</h5>
      <button onclick="document.getElementById('modalAdicionarMoto').style.display='none'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form action="salvar_moto.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="cliente_id" id="moto_cliente_id">
      <div style="padding:20px 24px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="os-form-group"><label class="os-label">Placa *</label><input type="text" name="placa" class="os-input" required style="text-transform:uppercase" placeholder="ABC-1234"></div>
        <div class="os-form-group">
          <label class="os-label">Marca *</label>
          <input type="text" name="marca" class="os-input" required list="listaMarcas" placeholder="Honda...">
          <datalist id="listaMarcas">
            <?php foreach (['Honda','Yamaha','Suzuki','Kawasaki','BMW','Harley-Davidson','Ducati','KTM','Triumph','Shineray','Dafra','Haojue'] as $m): ?><option value="<?= $m ?>"><?php endforeach; ?>
          </datalist>
        </div>
        <div class="os-form-group"><label class="os-label">Modelo *</label><input type="text" name="modelo" class="os-input" required></div>
        <div class="os-form-group"><label class="os-label">Ano</label><input type="number" name="ano" class="os-input" min="1950" max="2030"></div>
        <div class="os-form-group"><label class="os-label">Cor</label><input type="text" name="cor" class="os-input"></div>
        <div class="os-form-group"><label class="os-label">Cilindrada (cc)</label><input type="text" name="cilindrada" class="os-input" placeholder="150"></div>
        <div class="os-form-group"><label class="os-label">KM Atual</label><input type="number" name="km_atual" class="os-input" min="0" value="0"></div>
        <div class="os-form-group"><label class="os-label">Chassi</label><input type="text" name="chassi" class="os-input" style="text-transform:uppercase"></div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid var(--border)">
        <button type="button" class="btn-os btn-os-ghost" onclick="document.getElementById('modalAdicionarMoto').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-check"></i> Salvar Moto</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE = '<?= $baseUrl ?>';
function abrirModalCliente() {
    document.getElementById('modalClienteTitulo').textContent = 'Novo Cliente';
    ['cid','cNome','cRgIe','cCpfCnpj','cEmail','cTelefone','cCelular','cCep','cEndereco','cNumero','cBairro','cCidade','cEstado','cObs'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
    document.getElementById('cTipo').value = 'pf'; toggleTipo();
    document.getElementById('modalCliente').style.display = 'flex';
}
function fecharModalCliente() { document.getElementById('modalCliente').style.display = 'none'; }
function editarCliente(c) {
    document.getElementById('modalClienteTitulo').textContent = 'Editar Cliente';
    const m = {cid:c.id,cNome:c.nome,cTipo:c.tipo||'pf',cCpfCnpj:c.cpf_cnpj||'',cRgIe:c.rg_ie||'',cEmail:c.email||'',cTelefone:c.telefone||'',cCelular:c.celular||'',cCep:c.cep||'',cEndereco:c.endereco||'',cNumero:c.numero||'',cBairro:c.bairro||'',cCidade:c.cidade||'',cEstado:c.estado||'',cObs:c.observacoes||''};
    for(const [id,val] of Object.entries(m)){const el=document.getElementById(id);if(el)el.value=val;}
    toggleTipo();
    document.getElementById('modalCliente').style.display = 'flex';
}
function toggleTipo(){const pj=document.getElementById('cTipo').value==='pj';document.getElementById('lblCpf').textContent=pj?'CNPJ':'CPF';document.getElementById('lblRg').textContent=pj?'Inscrição Estadual':'RG';}
function abrirModalMoto(cid){document.getElementById('moto_cliente_id').value=cid;document.getElementById('modalAdicionarMoto').style.display='flex';}
async function verMotos(cid,nome){
    document.getElementById('modalMotosTitulo').textContent='Motos — '+nome;
    document.getElementById('listaMotos').innerHTML='<p style="color:var(--text-muted);text-align:center;padding:20px">Carregando...</p>';
    document.getElementById('modalMotos').style.display='flex';
    try{
        const r=await fetch(`${BASE}/api/motos.php?cliente_id=${cid}`);
        const motos=await r.json();
        if(!motos.length){document.getElementById('listaMotos').innerHTML='<p style="color:var(--text-muted);text-align:center;padding:20px">Nenhuma moto cadastrada.</p>';return;}
        document.getElementById('listaMotos').innerHTML=motos.map(m=>`
            <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
              <div><strong>${m.marca||''} ${m.modelo}</strong>
                <div style="font-size:.8rem;color:var(--text-muted);margin-top:3px">Placa: <strong>${m.placa}</strong>${m.ano?' · '+m.ano:''}${m.cor?' · '+m.cor:''}${m.km_atual?' · '+parseInt(m.km_atual).toLocaleString()+' km':''}</div></div>
              <button onclick="confirmarExclusaoMoto(${m.id})" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;padding:5px 10px;border-radius:7px;cursor:pointer;font-size:.8rem"><i class="ph-bold ph-trash"></i></button>
            </div>`).join('')+`<button class="btn-os btn-os-primary" style="width:100%;margin-top:6px;justify-content:center" onclick="document.getElementById('modalMotos').style.display='none';abrirModalMoto(${cid})"><i class="ph-bold ph-plus"></i> Adicionar Moto</button>`;
    }catch(e){document.getElementById('listaMotos').innerHTML='<p style="color:#ef4444;text-align:center;padding:20px">Erro ao carregar.</p>';}
}
function confirmarExclusaoCliente(id){Swal.fire({title:'Excluir cliente?',text:'Todas as motos serão excluídas também.',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#64748b',confirmButtonText:'Sim, excluir',cancelButtonText:'Cancelar',background:'var(--bg-card)',color:'var(--text)'}).then(r=>{if(r.isConfirmed)location.href=`clientes.php?excluir_cliente=${id}`;});}
function confirmarExclusaoMoto(id){Swal.fire({title:'Excluir moto?',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#64748b',confirmButtonText:'Sim',cancelButtonText:'Não',background:'var(--bg-card)',color:'var(--text)'}).then(r=>{if(r.isConfirmed)location.href=`excluir_moto.php?id=${id}`;});}
function maskCep(el){let v=el.value.replace(/\D/g,'').substring(0,8);if(v.length>5)v=v.replace(/(\d{5})(\d)/,'$1-$2');el.value=v;}
async function buscarCep(cep){cep=cep.replace(/\D/g,'');if(cep.length!==8)return;try{const r=await fetch(`https://viacep.com.br/ws/${cep}/json/`);const d=await r.json();if(!d.erro){document.getElementById('cEndereco').value=d.logradouro||'';document.getElementById('cBairro').value=d.bairro||'';document.getElementById('cCidade').value=d.localidade||'';document.getElementById('cEstado').value=d.uf||'';}}catch(e){}}
</script>

<?php include '../../includes/footer.php'; ?>
