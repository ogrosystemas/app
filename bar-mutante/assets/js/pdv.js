/* ============================================================
   BAR SYSTEM PRO — PDV JavaScript  (reescrito limpo)
   Requer carregados ANTES: BASE_URL, CAIXA_ID, FORMAS_OK, SMART2_ID
   Bootstrap 5, SweetAlert2, QZ Tray (opcional)
   ============================================================ */

var carrinho       = [];
var formaPagamento = 'dinheiro';
var mpTipoPag      = 'CREDIT_CARD';
var mpOrderId      = null;
var mpPolling      = null;
var _qzConectado   = false;

/* ── Helpers ──────────────────────────────────────────────── */
function parseMoeda(s) {
  return parseFloat((s || '0').replace(/\./g, '').replace(',', '.')) || 0;
}
function fmtMoeda(v) {
  return 'R$ ' + parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function escHtml(s) {
  var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
}

/* ── DOMContentLoaded ─────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

  /* Campos de moeda */
  document.querySelectorAll('.money-input').forEach(function (el) {
    // valorRecebido uses plain number input, not the centavos mask
    if (el.id === 'valorRecebido') {
      el.addEventListener('focus', function () { this.select(); });
      return;
    }
    el.addEventListener('input', function () {
      var v = this.value.replace(/\D/g, '');
      if (!v) v = '0';
      this.value = (parseInt(v, 10) / 100).toFixed(2).replace('.', ',');
    });
    el.addEventListener('focus', function () { this.select(); });
  });

  /* Busca produtos */
  var searchEl = document.getElementById('searchProdutos');
  if (searchEl) {
    searchEl.addEventListener('input', filtrarProdutos);
    document.addEventListener('keydown', function (e) {
      if (['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      if (e.key.length === 1) searchEl.focus();
    });
  }

  /* Filtro categoria */
  document.querySelectorAll('.cat-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.cat-btn').forEach(function (b) { b.classList.remove('active'); });
      this.classList.add('active');
      filtrarProdutos();
    });
  });

  /* Formas de pagamento */
  document.querySelectorAll('.pag-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.pag-btn').forEach(function (b) { b.classList.remove('active'); });
      this.classList.add('active');
      formaPagamento = this.dataset.forma;
      // Mostrar seção de troco só quando dinheiro
      var sec = document.getElementById('trocoSection');
      if (sec) {
        sec.style.display = (formaPagamento === 'dinheiro') ? '' : 'none';
        if (formaPagamento === 'dinheiro') {
          var vrEl = document.getElementById('valorRecebido');
          if (vrEl) { vrEl.value = ''; vrEl.focus(); }
          var td = document.getElementById('trocoDisplay');
          if (td) td.textContent = 'R$ 0,00';
        }
      }
      var subOpc = document.getElementById('mpSubOpcoes');
      if (subOpc) subOpc.classList.toggle('d-none', formaPagamento !== 'mercadopago');
      var dw = document.getElementById('dinheiroWrap');
      if (dw) dw.style.display = formaPagamento === 'dinheiro' ? '' : 'none';
    });
  });

  /* Sub-tipo Mercado Pago */
  document.querySelectorAll('.pag-sub-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.pag-sub-btn').forEach(function (b) { b.classList.remove('active'); });
      this.classList.add('active');
      mpTipoPag = this.dataset.mpTipo || 'CREDIT_DEBIT_CARD';
    });
  });

  /* Sangria */
  var formSangria = document.getElementById('formSangria');
  if (formSangria) {
    formSangria.addEventListener('submit', async function (e) {
      e.preventDefault();
      try {
        var r = await fetch(BASE_URL + 'api/caixa.php', { method: 'POST', body: new FormData(this) });
        var d = await r.json();
        if (d.success) {
          bootstrap.Modal.getInstance(document.getElementById('modalSangria')).hide();
          this.reset();
          Swal.fire({ icon: 'success', title: d.message, timer: 1800, showConfirmButton: false, background: '#1e2330', color: '#f0f2f7' });
        } else {
          Swal.fire({ icon: 'error', title: 'Erro', text: d.message, background: '#1e2330', color: '#f0f2f7' });
        }
      } catch (ex) {
        Swal.fire({ icon: 'error', title: 'Erro de conexão', text: ex.message, background: '#1e2330', color: '#f0f2f7' });
      }
    });
  }

  /* Desconto → recalcular */
  var descontoEl = document.getElementById('descontoInput');
  if (descontoEl) descontoEl.addEventListener('input', atualizarTotais);

  /* Troco ao vivo */
  var vrEl = document.getElementById('valorRecebido');
  if (vrEl) vrEl.addEventListener('input', calcTroco);

  /* Aplicar formas por perfil e inicializar totais */
  aplicarFormasPerfil();
  atualizarTotais();

  // Mostrar trocoSection imediatamente se dinheiro for a forma inicial
  setTimeout(function() {
    var sec = document.getElementById('trocoSection');
    if (sec && formaPagamento === 'dinheiro') {
      sec.style.display = '';
    }
  }, 50);
});



/* ── Formas por perfil ────────────────────────────────────── */
function aplicarFormasPerfil() {
  if (typeof FORMAS_OK === 'undefined') return;
  var first = null;
  document.querySelectorAll('.pag-btn').forEach(function (btn) {
    if (!FORMAS_OK.includes(btn.dataset.forma)) {
      btn.style.display = 'none';
    } else if (!first) {
      first = btn;
    }
  });
  if (first) first.click();
}

/* ── Carrinho ─────────────────────────────────────────────── */
function addCarrinho(prodId) {
  var card = document.querySelector('.produto-card[data-id="' + prodId + '"]');
  if (!card) return;
  var tipo   = card.dataset.tipo || 'unidade';
  var est    = parseFloat(card.dataset.estoque) || 0;
  var minimo = parseFloat(card.dataset.minimo)  || 0;
  var noCarr = null;
  for (var i = 0; i < carrinho.length; i++) { if (carrinho[i].id === prodId) { noCarr = carrinho[i]; break; } }
  var qAtual = noCarr ? noCarr.qty : 0;

  var controlaEst = !['drink','combo'].includes(tipo);
  if (controlaEst) {
    if (est <= 0) {
      Swal.fire({ icon: 'error', title: 'Esgotado', text: 'Sem estoque.', background: '#1e2330', color: '#f0f2f7', timer: 1800, showConfirmButton: false });
      return;
    }
    if (minimo > 0 && est <= minimo) {
      Swal.fire({ icon: 'warning', title: 'Estoque Mínimo', text: 'Produto atingiu o estoque mínimo.', background: '#1e2330', color: '#f0f2f7' });
      return;
    }
    var disp = minimo > 0 ? est - minimo : est;
    if (qAtual >= disp) {
      Swal.fire({ icon: 'warning', title: 'Limite', text: 'Disponível: ' + Math.floor(disp) + ' un.', background: '#1e2330', color: '#f0f2f7', timer: 1800, showConfirmButton: false });
      return;
    }
  }

  if (noCarr) { noCarr.qty++; }
  else { carrinho.push({ id: prodId, nome: card.dataset.nome, preco: parseFloat(card.dataset.preco), tipo: tipo, qty: 1 }); }
  card.classList.add('adding'); setTimeout(function () { card.classList.remove('adding'); }, 300);
  renderCarrinho();
}

function renderCarrinho() {
  var wrap   = document.getElementById('carrinhoItens');
  var empty  = document.getElementById('carrinhoEmpty');
  var badge  = document.getElementById('badgeItens');
  var btnFin = document.getElementById('btnFinalizar');
  if (!wrap) return;
  wrap.querySelectorAll('.carrinho-item').forEach(function (el) { el.remove(); });
  if (!carrinho.length) {
    if (empty)  empty.style.display  = 'flex';
    if (badge)  badge.textContent    = '0';
    if (btnFin) btnFin.disabled      = true;
    atualizarTotais(); return;
  }
  if (empty) empty.style.display = 'none';
  carrinho.forEach(function (item, idx) {
    var div = document.createElement('div'); div.className = 'carrinho-item';
    div.innerHTML =
      '<div class="item-nome">' + escHtml(item.nome) + '</div>' +
      '<div class="item-qty-wrap">' +
        '<button class="qty-btn" onclick="changeQty(' + idx + ',-1)"><i class="ph-bold ph-minus"></i></button>' +
        '<span class="qty-num">' + item.qty + '</span>' +
        '<button class="qty-btn" onclick="changeQty(' + idx + ',1)"><i class="ph-bold ph-plus"></i></button>' +
      '</div>' +
      '<div class="item-preco">' + fmtMoeda(item.preco * item.qty) + '</div>' +
      '<button class="item-del" onclick="removeItem(' + idx + ')"><i class="ph-bold ph-x"></i></button>';
    wrap.appendChild(div);
  });
  if (badge)  badge.textContent = carrinho.reduce(function (s, i) { return s + i.qty; }, 0);
  if (btnFin) btnFin.disabled   = false;
  atualizarTotais();
}

function changeQty(idx, d) {
  if (!carrinho[idx]) return;
  var prodId = carrinho[idx].id;
  carrinho[idx].qty += d;
  if (carrinho[idx].qty <= 0) carrinho.splice(idx, 1);
  renderCarrinho();
  // Atualizar badge do card
  var card = document.querySelector('.produto-card[data-id="' + prodId + '"]');
  if (card) atualizarBadgeCard(card, prodId);
}
function removeItem(idx) {
  var prodId = carrinho[idx] ? carrinho[idx].id : null;
  carrinho.splice(idx, 1);
  renderCarrinho();
  // Restaurar badge do card
  if (prodId) {
    var card = document.querySelector('.produto-card[data-id="' + prodId + '"]');
    if (card) atualizarBadgeCard(card, prodId);
  }
}
function limparCarrinho() {
  if (!carrinho.length) return;
  Swal.fire({ title: 'Limpar pedido?', icon: 'question', showCancelButton: true, confirmButtonText: 'Limpar', cancelButtonText: 'Cancelar', background: '#1e2330', color: '#f0f2f7', confirmButtonColor: '#ef4444' })
    .then(function (r) {
      if (r.isConfirmed) {
        // Restaurar badges de todos os produtos que estavam no carrinho
        var ids = carrinho.map(function(i){ return i.id; });
        carrinho = [];
        renderCarrinho();
        ids.forEach(function(pid) {
          var card = document.querySelector('.produto-card[data-id="' + pid + '"]');
          if (card) atualizarBadgeCard(card, pid);
        });
      }
    });
}

/* ── Totais ───────────────────────────────────────────────── */
function atualizarTotais() {
  var sub = carrinho.reduce(function (a, i) { return a + i.preco * i.qty; }, 0);
  var desEl = document.getElementById('descontoInput');
  var des = parseMoeda(desEl ? desEl.value : '0');
  var tot = Math.max(0, sub - des);
  var es = document.getElementById('totSubtotal'); if (es) es.textContent = fmtMoeda(sub);
  var et = document.getElementById('totTotal');    if (et) et.textContent = fmtMoeda(tot);
  calcTroco(); return tot;
}
function atualizarTotaisRaw() {
  var sub = carrinho.reduce(function (a, i) { return a + i.preco * i.qty; }, 0);
  var desEl = document.getElementById('descontoInput');
  return Math.max(0, sub - parseMoeda(desEl ? desEl.value : '0'));
}
function calcTroco() {
  var tot  = atualizarTotaisRaw();
  var vrEl = document.getElementById('valorRecebido');
  // type=number returns plain float, no comma formatting needed
  var rec  = vrEl ? (parseFloat(vrEl.value) || 0) : 0;
  var el   = document.getElementById('trocoDisplay');
  if (!el) return;
  if (rec <= 0) {
    el.textContent = 'R$ 0,00';
    el.style.color = '#22c55e';
  } else if (rec < tot) {
    el.textContent = '⚠ Valor insuficiente';
    el.style.color = '#ef4444';
  } else {
    el.textContent = fmtMoeda(rec - tot);
    el.style.color = '#22c55e';
  }
}

/* ── Estoque ao vivo nos cards ───────────────────────────── */
function atualizarEstoquePDV(estoques) {
  if (!estoques) return;
  Object.keys(estoques).forEach(function (pid) {
    var qty  = parseFloat(estoques[pid]) || 0;
    var card = document.querySelector('.produto-card[data-id="' + pid + '"]');
    if (!card) return;
    card.dataset.estoque = qty;
    var badge  = card.querySelector('.prod-estoque');
    var tipo   = card.dataset.tipo || 'unidade';
    var un     = card.dataset.unidade || 'un';
    var minimo = parseFloat(card.dataset.minimo || 0);
    if (!badge) return;
    var esgotado = qty <= 0 || (minimo > 0 && qty <= minimo);
    if (esgotado) {
      badge.innerHTML = '<i class="ph-bold ph-x me-1"></i>' + (qty <= 0 ? 'Esgotado' : 'Mínimo');
      badge.className = 'prod-estoque esgotado';
      card.classList.add('esgotado');
      card.setAttribute('onclick', 'alertaEsgotado()');
    } else {
      var icoMap = { chopp_barril: 'beer-bottle', chopp_lata: 'wine', garrafa: 'wine', dose: 'tumbler', drink: 'martini', combo: 'fork-knife' };
      var ico = icoMap[tipo] || 'cubes';
      badge.innerHTML = '<i class="ph-bold ph-' + ico + '" style="margin-right:3px"></i>' + Math.floor(qty) + ' ' + un;
      badge.className = 'prod-estoque' + (qty <= 3 ? ' baixo' : '');
      card.classList.remove('esgotado');
      card.setAttribute('onclick', 'addCarrinho(' + pid + ')');
    }
  });
}

/* ── Topbar / Alertas ────────────────────────────────────── */
function atualizarTopBar(total, n) {
  var t = document.getElementById('topTotal');  if (t) t.textContent = fmtMoeda(total);
  var v = document.getElementById('topVendas'); if (v) v.textContent = n;
}
function alertaEsgotado() {
  Swal.fire({ icon: 'warning', title: 'Produto esgotado', text: 'Faça a reposição no estoque.', background: '#1e2330', color: '#f0f2f7' });
}

/* ── Finalizar venda ──────────────────────────────────────── */
async function finalizarVenda() {
  if (!carrinho.length) return;
  var total = atualizarTotaisRaw();
  if (total <= 0 && formaPagamento !== 'cortesia') {
    Swal.fire({ icon: 'warning', title: 'Total inválido', text: 'O total deve ser maior que zero.', background: '#1e2330', color: '#f0f2f7' }); return;
  }
  if (formaPagamento === 'mercadopago') { await finalizarMercadoPago(total); }
  else if (formaPagamento === 'dinheiro') { await salvarVenda(total, 'dinheiro', null); }
  else {
    var conf = await Swal.fire({
      icon: 'question', title: 'Confirmar Pagamento',
      html: 'Total: <strong style="color:#f59e0b;font-size:1.3rem">' + fmtMoeda(total) + '</strong>',
      showCancelButton: true, confirmButtonText: '✅ Confirmar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#22c55e', background: '#1e2330', color: '#f0f2f7'
    });
    if (conf.isConfirmed) await salvarVenda(total, formaPagamento, null);
  }
}

/* ── Salvar venda ─────────────────────────────────────────── */
async function salvarVenda(total, forma, mpData) {
  var payload = {
    action: 'finalizar', caixa_id: CAIXA_ID, itens: carrinho,
    subtotal: carrinho.reduce(function (s, i) { return s + i.preco * i.qty; }, 0),
    desconto: parseMoeda((document.getElementById('descontoInput') || {}).value || '0'),
    total: total, forma_pagamento: forma,
    mesa: (document.getElementById('mesaInput') || {}).value || '',
    mp_data: mpData || null
  };
  var btnFin = document.getElementById('btnFinalizar');
  if (btnFin) btnFin.disabled = true;
  try {
    var r = await fetch(BASE_URL + 'api/venda.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    var data = await r.json();
    if (data.success) {
      atualizarTopBar(data.total_caixa, data.vendas_n);
      if (data.estoques) atualizarEstoquePDV(data.estoques);
      carrinho = []; renderCarrinho();
      var mesaEl = document.getElementById('mesaInput'); if (mesaEl) mesaEl.value = '';
      var desEl  = document.getElementById('descontoInput'); if (desEl) desEl.value = '0,00';
      Swal.fire({
        icon: 'success', title: '✅ Venda #' + data.numero,
        html: 'Total: <b style="color:#f59e0b">' + fmtMoeda(total) + '</b>' +
          (data.total_tickets > 0 ? '<br><small style="color:#8892a4">🎟️ ' + data.total_tickets + ' ticket(s) imprimindo...</small>' : ''),
        timer: 3000, showConfirmButton: false, background: '#1e2330', color: '#f0f2f7'
      });
      if (data.total_tickets > 0 && data.print_url) imprimirTicketsAuto(data.print_url, data.venda_id);
    } else {
      Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao salvar.', background: '#1e2330', color: '#f0f2f7' });
    }
  } catch (ex) {
    Swal.fire({ icon: 'error', title: 'Erro de conexão', text: ex.message, background: '#1e2330', color: '#f0f2f7' });
  } finally {
    if (btnFin) btnFin.disabled = carrinho.length === 0;
  }
}

/* ── Mercado Pago ─────────────────────────────────────────── */
async function finalizarMercadoPago(total) {
  var terminalId = (typeof SMART2_ID !== 'undefined' && SMART2_ID) ? SMART2_ID : (document.getElementById('psTerminal') || {}).value;
  if (!terminalId) { Swal.fire({ icon: 'warning', title: 'Terminal não configurado', text: 'Configure o Device ID em Configurações → Mercado Pago.', background: '#1e2330', color: '#f0f2f7' }); return; }
  window._mpTotalPendente = total;
  var tipoLabel = { CREDIT_DEBIT_CARD: 'Crédito ou Débito', CREDIT_CARD: 'Cartão de Crédito', DEBIT_CARD: 'Cartão de Débito', PIX: 'PIX' };
  var elVal = document.getElementById('psValorDisplay'); if (elVal) elVal.textContent = fmtMoeda(total);
  var elSub = document.getElementById('psLoaderSub');   if (elSub) elSub.textContent = 'Modalidade: ' + (tipoLabel[mpTipoPag] || mpTipoPag) + ' — realize o pagamento na maquininha';
  var loaderEl   = document.getElementById('psLoader');    if (loaderEl)   loaderEl.classList.remove('d-none');
  var resultadoEl = document.getElementById('psResultado'); if (resultadoEl) resultadoEl.classList.add('d-none');
  var cancelBtn  = document.getElementById('btnCancelarMP'); if (cancelBtn)  cancelBtn.classList.remove('d-none');
  var modalEl = document.getElementById('modalMercadoPago');
  var modal   = modalEl ? new bootstrap.Modal(modalEl) : null;
  if (modal) modal.show();
  try {
    var r = await fetch(BASE_URL + 'api/mercadopago.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'cobrar', device_id: terminalId, valor: total, tipo_pagamento: mpTipoPag, referencia: 'VND-' + Date.now() })
    });
    var data = await r.json();
    if (!data.success) { _mpErro(data.message || 'Erro ao criar cobrança.'); return; }
    mpOrderId = data.order_id || data.id;
    var intentId = data.intent_id || data.id;
    var pollCount = 0;
    mpPolling = setInterval(async function () {
      if (++pollCount > 90) { clearInterval(mpPolling); mpPolling = null; _mpErro('Tempo esgotado.'); return; }
      try {
        var pr = await fetch(BASE_URL + 'api/mercadopago.php?action=status&order_id=' + mpOrderId + '&intent_id=' + intentId + '&device_id=' + terminalId);
        var pd = await pr.json();
        var st = (pd.status || pd.state || 'unknown').toLowerCase();
        if (['finished','paid','approved'].includes(st)) {
          clearInterval(mpPolling); mpPolling = null; _mpSucesso();
          setTimeout(async function () {
            if (modal) modal.hide();
            await salvarVenda(total, 'mercadopago', { order_id: mpOrderId, intent_id: intentId, device_id: terminalId, status: st });
            mpOrderId = null;
          }, 800);
        } else if (['cancelled','canceled','error','expired'].includes(st)) {
          clearInterval(mpPolling); mpPolling = null;
          _mpErro({ cancelled: 'Pagamento cancelado.', canceled: 'Pagamento cancelado.', error: 'Erro no pagamento.', expired: 'Tempo esgotado.' }[st] || 'Não aprovado.');
        }
      } catch (e) { /* silencioso */ }
    }, 2000);
  } catch (ex) { _mpErro('Erro: ' + ex.message); }
}

function _mpSucesso() {
  var l = document.getElementById('psLoader');       if (l) l.classList.add('d-none');
  var r = document.getElementById('psResultado');    if (r) r.classList.remove('d-none');
  var i = document.getElementById('psIconResult');   if (i) i.innerHTML = '<i class="ph-bold ph-check-circle" style="font-size:3rem;color:#22c55e"></i>';
  var m = document.getElementById('psMsgResult');    if (m) { m.textContent = '✅ Pagamento aprovado!'; m.className = 'text-success fw-bold'; }
  var b = document.getElementById('psRetryBtns');    if (b) b.innerHTML = '';
  var c = document.getElementById('btnCancelarMP');  if (c) c.classList.add('d-none');
}

function _mpErro(texto) {
  var l = document.getElementById('psLoader');       if (l) l.classList.add('d-none');
  var r = document.getElementById('psResultado');    if (r) r.classList.remove('d-none');
  var i = document.getElementById('psIconResult');   if (i) i.innerHTML = '<i class="ph-bold ph-x-circle" style="font-size:3rem;color:#ef4444"></i>';
  var m = document.getElementById('psMsgResult');    if (m) { m.textContent = (texto || 'Não aprovado.') + ' Escolha outra forma:'; m.className = 'text-danger fw-bold'; }
  var c = document.getElementById('btnCancelarMP');  if (c) c.classList.remove('d-none');
  var b = document.getElementById('psRetryBtns');
  if (b && typeof FORMAS_OK !== 'undefined') {
    var labels = { dinheiro: '💵 Dinheiro', mercadopago: '📱 Maquininha' };
    b.innerHTML = FORMAS_OK.map(function (f) {
      return '<button class="btn btn-outline-secondary btn-sm mt-1 me-1" onclick="trocarFormaETentar(\'' + f + '\',' + (window._mpTotalPendente || 0) + ')" style="font-size:.75rem">' + (labels[f] || f) + '</button>';
    }).join('');
  }
}

async function trocarFormaETentar(novaForma, total) {
  formaPagamento = novaForma;
  document.querySelectorAll('.pag-btn').forEach(function (b) { b.classList.toggle('active', b.dataset.forma === novaForma); });
  bootstrap.Modal.getInstance(document.getElementById('modalMercadoPago')).hide();
  if (novaForma === 'mercadopago') await finalizarMercadoPago(total);
  else if (novaForma === 'dinheiro') await finalizarDinheiro(total);
  else await salvarVenda(total, novaForma, null);
}

function cancelarMercadoPago() {
  if (mpPolling) { clearInterval(mpPolling); mpPolling = null; }
  if (mpOrderId) { fetch(BASE_URL + 'api/mercadopago.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'cancelar', order_id: mpOrderId }) }).catch(function(){}); mpOrderId = null; }
  var modalInst = bootstrap.Modal.getInstance(document.getElementById('modalMercadoPago'));
  if (modalInst) modalInst.hide();
}

/* ── Sangria / Fechar Caixa ──────────────────────────────── */
function abrirSangria() { new bootstrap.Modal(document.getElementById('modalSangria')).show(); }
function setTipo(tipo) {
  document.getElementById('tipoMovimento').value = tipo;
  document.querySelectorAll('.tipo-btn').forEach(function (b) {
    b.classList.remove('active-tipo','btn-outline-danger','btn-outline-success');
    var isSangria = b.dataset.tipo === 'sangria';
    b.classList.add(isSangria ? 'btn-outline-danger' : 'btn-outline-success');
    if (b.dataset.tipo === tipo) b.classList.add('active-tipo');
  });
}

async function confirmarFecharCaixa() {
  try {
    var r = await fetch(BASE_URL + 'api/caixa.php?action=resumo&id=' + CAIXA_ID);
    var d = await r.json(); if (!d.success) return;
    var cx = d.caixa;
    var el = document.getElementById('resumoCaixa');
    if (el) el.innerHTML =
      '<div class="col-6"><div class="text-muted text-xs">Saldo Inicial</div><div class="fw-bold">' + fmtMoeda(cx.saldo_inicial) + '</div></div>' +
      '<div class="col-6"><div class="text-muted text-xs">Total Vendas</div><div class="fw-bold text-amber">' + fmtMoeda(cx.total_vendas) + '</div></div>' +
      '<div class="col-6"><div class="text-muted text-xs">Suprimentos</div><div class="fw-bold text-success">+' + fmtMoeda(cx.total_suprimentos) + '</div></div>' +
      '<div class="col-6"><div class="text-muted text-xs">Sangrias</div><div class="fw-bold text-danger">-' + fmtMoeda(cx.total_sangrias) + '</div></div>' +
      '<div class="col-12 border-top pt-2 mt-1"><div class="text-muted text-xs">Saldo Esperado</div><div class="fw-bold fs-5">' + fmtMoeda(cx.saldo_esperado) + '</div></div>';
    window._saldoEsperado = parseFloat(cx.saldo_esperado);
    new bootstrap.Modal(document.getElementById('modalFecharCaixa')).show();
  } catch (ex) { Swal.fire({ icon: 'error', title: 'Erro', text: ex.message, background: '#1e2330', color: '#f0f2f7' }); }
}

function calcDiferenca() {
  var saldoEl = document.getElementById('saldoContado');
  var contado = parseMoeda(saldoEl ? saldoEl.value : '0');
  var esp = window._saldoEsperado || 0; var dif = contado - esp;
  var el = document.getElementById('difInfo'); if (!el) return;
  el.className = 'alert ' + (Math.abs(dif) < 0.01 ? 'alert-success' : Math.abs(dif) < 1 ? 'alert-warning' : 'alert-danger');
  el.classList.remove('d-none');
  el.textContent = Math.abs(dif) < 0.01 ? '✓ Sem diferença' : (dif > 0 ? '↑ Sobra: ' + fmtMoeda(dif) : '↓ Falta: ' + fmtMoeda(Math.abs(dif)));
}

async function confirmarFechamento() {
  var s = (document.getElementById('saldoContado') || {}).value || '0';
  var o = (document.getElementById('obsCaixa') || {}).value || '';
  try {
    var r = await fetch(BASE_URL + 'api/caixa.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'fechar', caixa_id: CAIXA_ID, saldo_contado: parseMoeda(s), observacoes: o }) });
    var d = await r.json();
    if (d.success) window.location.reload();
    else Swal.fire({ icon: 'error', title: 'Erro', text: d.message, background: '#1e2330', color: '#f0f2f7' });
  } catch (ex) { Swal.fire({ icon: 'error', title: 'Erro', text: ex.message, background: '#1e2330', color: '#f0f2f7' }); }
}

/* ── Impressão silenciosa — sem janela, sem diálogo ─────────────
   Ordem de tentativas:
   1. PHP TCP proxy → impressora via rede (porta 9100)
   2. QZ Tray       → impressora via driver local
   3. Falha silenciosa → notificação discreta no canto da tela
─────────────────────────────────────────────────────────────── */
async function imprimirTicketsAuto(printUrl, vendaId) {
  var nome      = (typeof PRINTER_NAME !== 'undefined' && PRINTER_NAME) ? PRINTER_NAME : '';
  var printerIP = (typeof PRINTER_IP   !== 'undefined' && PRINTER_IP)   ? PRINTER_IP   : '';

  // ── Tentativa 1: QZ Tray (USB/driver Windows) ────────────────────
  // Primeira tentativa quando nome da impressora está configurado
  if (nome && typeof qz !== 'undefined') {
    try {
      if (!_qzConectado || !qz.websocket.isActive()) {
        await qz.websocket.connect();
        _qzConectado = true;
      }
      // Buscar ESC/POS bytes do servidor
      var re = await fetch(BASE_URL + 'api/imprimir.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ venda_id: vendaId })
      });
      var de = await re.json();
      if (de.escpos) {
        await qz.print(
          qz.configs.create(nome),
          [{ type: 'raw', format: 'base64', data: de.escpos }]
        );
        return; // ✅ Imprimiu via QZ Tray (USB/driver) — silencioso
      }
    } catch (e) { console.warn('QZ Tray:', e.message || e); }
  }

  // ── Tentativa 2: PHP TCP socket (impressora de rede) ─────────────
  if (printerIP) {
    try {
      var rt = await fetch(BASE_URL + 'api/imprimir.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ venda_id: vendaId })
      });
      var dt = await rt.json();
      if (dt.success && dt.via_tcp) {
        return; // ✅ Imprimiu via TCP — silencioso
      }
    } catch (e) { console.warn('TCP print:', e.message || e); }
  }

  // ── Sem impressora configurada: toast discreto ────────────────────
  _toastImpressao(printUrl);
}

/* Notificação discreta no canto — sem bloquear o fluxo do caixa */
function _toastImpressao(printUrl) {
  var div = document.createElement('div');
  div.id = 'toastImpressao';
  div.style.cssText =
    'position:fixed;bottom:20px;right:20px;z-index:9999;' +
    'background:#1e2330;border:1px solid #f59e0b;border-radius:10px;' +
    'padding:12px 16px;max-width:300px;box-shadow:0 4px 20px rgba(0,0,0,.5);' +
    'font-size:.82rem;color:#f0f2f7';
  var html =
    '<div style="font-weight:700;color:#f59e0b;margin-bottom:4px">' +
    '<i class="ph-bold ph-printer"></i> Impressão pendente</div>' +
    '<div style="color:#8892a4;font-size:.75rem;margin-bottom:8px">' +
    'QZ Tray não detectado. Instale em <strong>qz.io</strong> para impressão automática via USB.</div>' +
    '<div style="display:flex;gap:6px">';
  if (printUrl) {
    html += '<button style="background:#f59e0b;color:#000;border:none;border-radius:6px;' +
      'padding:5px 12px;font-size:.75rem;font-weight:700;cursor:pointer;flex:1" ' +
      'id="btnToastPrint">🖨️ Imprimir agora</button>';
  }
  html += '<button style="background:transparent;color:#8892a4;border:1px solid #2d3447;' +
    'border-radius:6px;padding:5px 10px;font-size:.75rem;cursor:pointer" ' +
    'id="btnToastClose">✕</button></div>';
  div.innerHTML = html;
  document.body.appendChild(div);
  if (printUrl) {
    document.getElementById('btnToastPrint').onclick = function() {
      window.open(printUrl, '_blank'); div.remove();
    };
  }
  document.getElementById('btnToastClose').onclick = function() { div.remove(); };
  document.body.appendChild(div);
  // Auto-remove após 15 segundos
  setTimeout(function() { if (div.parentNode) div.remove(); }, 15000);
}

/* ── Polling alertas ─────────────────────────────────────── */
setInterval(async function () {
  try {
    var r = await fetch(BASE_URL + 'api/alertas.php');
    var d = await r.json();
    var el = document.querySelector('.alerta-btn .badge-count');
    if (el) el.textContent = d.count > 0 ? d.count : '';
  } catch (e) {}
}, 120000);
