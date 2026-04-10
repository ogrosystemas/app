let secaoAtual = 'dashboard';
let lembreteOSId = null;
let novaVersaoDisponivel = false;

// ==================== SISTEMA DE LICENÇA (TESTE GRÁTIS 15 DIAS) ====================
function verificarLicenca() {
    const primeiroAcesso = localStorage.getItem('primeiroAcesso');
    const licencaTipo = localStorage.getItem('licencaTipo');
    const licencaExpiracao = localStorage.getItem('licencaExpiracao');
    
    // Primeiro acesso: libera 15 dias grátis
    if (!primeiroAcesso) {
        const dataAtual = new Date();
        const dataExpiracao = new Date();
        dataExpiracao.setDate(dataAtual.getDate() + 15);
        
        localStorage.setItem('primeiroAcesso', dataAtual.toISOString());
        localStorage.setItem('licencaExpiracao', dataExpiracao.toISOString());
        localStorage.setItem('licencaTipo', 'trial');
        
        alert('🎉 Bem-vindo ao Auto Care!\n\nVocê tem 15 dias de teste grátis para conhecer todas as funcionalidades.');
        return true;
    }
    
    // Verifica se a licença expirou
    if (licencaExpiracao) {
        const dataExpiracao = new Date(licencaExpiracao);
        const hoje = new Date();
        
        if (hoje > dataExpiracao) {
            return false;
        }
    }
    
    return true;
}

function getDiasRestantes() {
    const licencaExpiracao = localStorage.getItem('licencaExpiracao');
    if (!licencaExpiracao) return 0;
    
    const hoje = new Date();
    const expiracao = new Date(licencaExpiracao);
    const diff = expiracao - hoje;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function mostrarTelaExpirado() {
    const diasRestantes = getDiasRestantes();
    const html = `
        <div style="text-align:center; padding:50px 20px; color:white; max-width:400px; margin:0 auto;">
            <div style="font-size:64px; margin-bottom:20px;">⏰</div>
            <h2>${diasRestantes <= 0 ? 'Teste Grátis Expirado' : 'Licença Expirada'}</h2>
            <p style="margin:20px 0; color:rgba(255,255,255,0.7);">
                ${diasRestantes <= 0 ? 'Seu período de 15 dias terminou.' : 'Sua licença expirou.'}<br>
                Para continuar usando o Auto Care, adquira uma licença.
            </p>
            <div style="background:#151624; padding:20px; border-radius:16px; margin:20px 0; text-align:left;">
                <p style="text-align:center; margin-bottom:15px;"><strong>Planos disponíveis:</strong></p>
                <p>💰 <strong>Mensal:</strong> R$ 49,90/mês</p>
                <p>💰 <strong>Trimestral:</strong> R$ 129,90 (10% OFF)</p>
                <p>💰 <strong>Anual:</strong> R$ 449,90 (25% OFF)</p>
                <p style="margin-top:15px; text-align:center;">📱 WhatsApp: (XX) XXXXX-XXXX</p>
            </div>
            <button class="primary" onclick="mostrarTelaAtivacao()">🔑 JÁ TENHO UMA CHAVE</button>
            <button class="secondary" onclick="location.reload()">🔄 TENTAR NOVAMENTE</button>
        </div>
    `;
    document.getElementById('conteudo').innerHTML = html;
    document.querySelector('header').style.display = 'none';
    document.querySelector('.menu-principal').style.display = 'none';
}

function mostrarTelaAtivacao() {
    const html = `
        <div style="text-align:center; padding:50px 20px; color:white; max-width:400px; margin:0 auto;">
            <div style="font-size:64px; margin-bottom:20px;">🔐</div>
            <h2>Ativar Licença</h2>
            <p style="margin:20px 0; color:rgba(255,255,255,0.7);">
                Insira a chave de ativação fornecida após a compra.
            </p>
            <input type="text" id="chaveAtivacaoInput" placeholder="Ex: OGRO-1505-26-3535" style="text-align:center; font-size:16px; text-transform:uppercase;">
            <button class="primary" onclick="ativarChave()" style="margin-top:15px;">🔓 ATIVAR</button>
            <button class="secondary" onclick="location.reload()">⬅️ VOLTAR</button>
        </div>
    `;
    document.getElementById('conteudo').innerHTML = html;
}

function ativarChave() {
    const chave = document.getElementById('chaveAtivacaoInput').value.trim().toUpperCase();
    
    // 1. Valida o formato da chave (OGRO-DDMM-AA-CHECKSUM)
    const regex = /^OGRO-(\d{4})-(\d{2})-(\d{4})$/;
    const match = chave.match(regex);
    
    if (!match) {
        alert('❌ Chave inválida! Formato incorreto.');
        return;
    }

    const bloco1 = match[1]; // DDMM
    const bloco2 = match[2]; // AA (ano)
    const bloco3 = match[3]; // Checksum
    
    // 2. Verifica o checksum (anti-adivinhação)
    const checksumCalculado = (parseInt(bloco1) * 7).toString().slice(-4);
    if (bloco3 !== checksumCalculado) {
        alert('❌ Chave inválida! Código de segurança não confere.');
        return;
    }

    // 3. Extrai a data de expiração
    const dia = parseInt(bloco1.substring(0, 2));
    const mes = parseInt(bloco1.substring(2, 4)) - 1; // Mês em JS é 0-11
    const ano = parseInt('20' + bloco2);
    
    const dataExpiracao = new Date(ano, mes, dia);
    const hoje = new Date();
    
    // 4. Valida se a data é válida e futura
    if (isNaN(dataExpiracao.getTime())) {
        alert('❌ Chave inválida! Data corrompida.');
        return;
    }
    
    if (dataExpiracao <= hoje) {
        alert('❌ Esta chave já expirou!');
        return;
    }

    // 5. Tudo certo! Ativa a licença
    localStorage.setItem('licencaExpiracao', dataExpiracao.toISOString());
    localStorage.setItem('licencaTipo', 'paga');
    localStorage.setItem('chaveAtivacao', chave);
    
    alert(`✅ Licença ativada com sucesso! Válida até ${dataExpiracao.toLocaleDateString('pt-BR')}`);
    location.reload();
}

// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', async () => {
    // VERIFICA LICENÇA PRIMEIRO
    const licencaValida = verificarLicenca();
    if (!licencaValida) {
        mostrarTelaExpirado();
        return;
    }
    
    try {
        await abrirDB();
    } catch(e) {
        console.error('Erro no banco:', e);
        document.getElementById('conteudo').innerHTML = '<div style="color:white;padding:50px;text-align:center;"><h2>Erro ao carregar</h2><p>Tente limpar os dados do navegador.</p></div>';
        return;
    }
    
    document.getElementById('nomeOficinaHeader').textContent = getNomeOficina();
    
    document.querySelectorAll('.menu-linha button').forEach(btn => {
        btn.addEventListener('click', () => {
            const secao = btn.dataset.secao;
            mostrarSecao(secao);
        });
    });
    
    solicitarPermissaoNotificacao();
    mostrarSecao('dashboard');
    atualizarSaldo();
    registrarServiceWorker();
    verificarLembretesPendentes();
    configurarAutoUpdate();
});

// ==================== SERVIÇO DE NOTIFICAÇÕES ====================
async function registrarServiceWorker() {
    if ('serviceWorker' in navigator) {
        try { await navigator.serviceWorker.register('sw.js'); } catch (e) {}
    }
}

async function solicitarPermissaoNotificacao() {
    if ('Notification' in window) await Notification.requestPermission();
}

// ==================== AUTO-UPDATE ====================
function configurarAutoUpdate() {
    if (!('serviceWorker' in navigator)) return;
    navigator.serviceWorker.register('sw.js').then(registration => {
        setInterval(() => registration.update(), 30 * 60 * 1000);
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    novaVersaoDisponivel = true;
                    mostrarAvisoAtualizacao();
                }
            });
        });
    });
}

function mostrarAvisoAtualizacao() {
    const banner = document.createElement('div');
    banner.style.cssText = `position:fixed;bottom:90px;left:20px;right:20px;background:#3b82f6;color:white;padding:15px;border-radius:16px;z-index:2000;display:flex;justify-content:space-between;`;
    banner.innerHTML = `<span>🔄 Nova versão!</span><button onclick="aplicarAtualizacao()" style="background:white;color:#3b82f6;padding:10px 20px;border-radius:30px;border:none;font-weight:bold;">ATUALIZAR</button>`;
    document.body.appendChild(banner);
}

async function aplicarAtualizacao() {
    const banner = document.querySelector('div[style*="position:fixed"]');
    if (banner) banner.remove();
    if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration && registration.waiting) {
            registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
    }
    setTimeout(() => window.location.reload(), 1000);
}

async function verificarAtualizacaoManual() {
    const registration = await navigator.serviceWorker.getRegistration();
    if (registration) {
        await registration.update();
        setTimeout(() => alert(novaVersaoDisponivel ? 'Nova versão disponível!' : '✅ Já está atualizado!'), 1000);
    }
}

// ==================== WHATSAPP HELPER ====================
function abrirWhatsApp(telefone, mensagem) {
    try {
        const telLimpo = telefone.replace(/\D/g, '');
        const msg = encodeURIComponent(mensagem);
        window.location.href = `whatsapp://send?phone=55${telLimpo}&text=${msg}`;
    } catch (e) {}
}

// ==================== LEMBRETES ====================
async function verificarLembretesPendentes() {
    try {
        const lembretes = await listar('lembretes') || [];
        const agora = new Date();
        let algumEnviado = false;
        
        for (const l of lembretes) {
            if (l.status === 'pendente' && new Date(l.dataHora) <= agora) {
                l.status = 'enviado';
                await atualizar('lembretes', l);
                algumEnviado = true;

                abrirWhatsApp(l.telefone, `👤 ${l.clienteNome}\n\n✅ ${l.mensagem}\n\n📍 Venha buscar na ${getNomeOficina()}!`);
                
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('🔔 Lembrete', { body: `${l.clienteNome}, ${l.mensagem}`, icon: 'icon-192.png', vibrate: [200, 100, 200] });
                }
            }
        }
        
        if (algumEnviado && secaoAtual === 'dashboard') await carregarDashboard();
    } catch (e) {}
    setTimeout(verificarLembretesPendentes, 30000);
}

async function dispararLembreteManual(lembreteId) {
    const lembrete = await buscarPorId('lembretes', lembreteId);
    if (!lembrete) return;
    
    lembrete.status = 'enviado';
    await atualizar('lembretes', lembrete);

    abrirWhatsApp(lembrete.telefone, `👤 ${lembrete.clienteNome}\n\n✅ ${lembrete.mensagem}\n\n📍 Venha buscar na ${getNomeOficina()}!`);
    
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('🔔 Lembrete', { body: `${lembrete.clienteNome}, ${lembrete.mensagem}`, icon: 'icon-192.png', vibrate: [200, 100, 200] });
    }
    
    if (secaoAtual === 'dashboard') await carregarDashboard();
    alert('✅ Lembrete enviado!');
}

function abrirModalLembrete(osId, clienteNome, clienteTelefone) {
    lembreteOSId = osId;
    document.getElementById('clienteLembreteNome').textContent = `Cliente: ${clienteNome}`;
    const amanha = new Date(); amanha.setDate(amanha.getDate() + 1);
    document.getElementById('dataLembrete').value = amanha.toISOString().split('T')[0];
    document.getElementById('horaLembrete').value = '18:00';
    document.getElementById('msgLembrete').value = 'Seu veículo está pronto! Venha buscar.';
    document.getElementById('modalLembrete').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modalLembrete').style.display = 'none';
    lembreteOSId = null;
}

async function salvarLembrete() {
    const data = document.getElementById('dataLembrete').value;
    const hora = document.getElementById('horaLembrete').value;
    const mensagem = document.getElementById('msgLembrete').value;
    if (!data || !hora) { alert('Preencha data e hora!'); return; }
    const dataHora = new Date(`${data}T${hora}`);
    const servico = await buscarPorId('servicos', lembreteOSId);
    const cliente = await buscarPorId('clientes', servico.clienteId);
    await salvar('lembretes', {
        osId: lembreteOSId, clienteId: servico.clienteId, clienteNome: cliente.nome,
        telefone: cliente.telefone, dataHora: dataHora.toISOString(), mensagem,
        status: 'pendente', dataCriacao: new Date().toISOString()
    });
    alert(`✅ Lembrete agendado para ${data} às ${hora}!`);
    fecharModal();
}

// ==================== NAVEGAÇÃO ====================
function mostrarSecao(secao) {
    secaoAtual = secao;
    const telas = {
        dashboard: () => carregarDashboard(),
        clientes: () => carregarClientes(),
        orcamentos: () => carregarOrcamentos(),
        os: () => carregarOS(),
        financeiro: () => carregarFinanceiro(),
        relatorios: () => carregarRelatorios(),
        config: () => carregarConfig(),
        configuracoes: () => carregarConfiguracoes(),
        backup: () => carregarBackup()
    };
    document.querySelectorAll('.menu-linha button').forEach(btn => {
        btn.classList.remove('ativo');
        if (btn.dataset.secao === secao) btn.classList.add('ativo');
    });
    if (telas[secao]) telas[secao]();
}

// ==================== CONFIGURAÇÕES ====================
function getNomeOficina() {
    return localStorage.getItem('nomeOficina') || 'Auto Care';
}

function carregarConfig() {
    const diasRestantes = getDiasRestantes();
    const licencaTipo = localStorage.getItem('licencaTipo') || 'trial';
    
    const html = `
        <h2>⚙️ Configurações</h2>
        <div class="card">
            <p style="color:white; margin-bottom:20px; text-align:center;">Gerencie as configurações do sistema</p>
            
            <div style="background:#0a0b14; padding:15px; border-radius:12px; margin-bottom:20px; text-align:center;">
                <div style="font-size:14px; color:rgba(255,255,255,0.6);">Status da Licença</div>
                <div style="font-size:24px; font-weight:bold; color:${licencaTipo === 'trial' ? '#fbbf24' : '#4ade80'};">
                    ${licencaTipo === 'trial' ? '⏳ Teste Grátis' : '✅ Ativa'}
                </div>
                <div style="font-size:14px; color:rgba(255,255,255,0.5); margin-top:5px;">
                    ${diasRestantes} dias restantes
                </div>
            </div>
            
            <div class="backup-container">
                <div class="backup-card-item" onclick="carregarConfiguracoes()">
                    <div class="backup-emoji">🏢</div>
                    <div class="backup-info"><div class="backup-titulo">Nome da Oficina</div><div class="backup-desc">Personalizar nome nos documentos</div></div>
                    <div class="backup-seta">›</div>
                </div>
                <div class="backup-card-item" onclick="exportarBackup()">
                    <div class="backup-emoji">📤</div>
                    <div class="backup-info"><div class="backup-titulo">Exportar Dados</div><div class="backup-desc">Salvar backup dos dados</div></div>
                    <div class="backup-seta">›</div>
                </div>
                <div class="backup-card-item" onclick="document.getElementById('inputBackup').click()">
                    <div class="backup-emoji">📥</div>
                    <div class="backup-info"><div class="backup-titulo">Importar Dados</div><div class="backup-desc">Restaurar um backup salvo</div></div>
                    <div class="backup-seta">›</div>
                </div>
                <div class="backup-card-item" onclick="verificarAtualizacaoManual()">
                    <div class="backup-emoji">🔄</div>
                    <div class="backup-info"><div class="backup-titulo">Verificar Atualização</div><div class="backup-desc">Buscar nova versão do app</div></div>
                    <div class="backup-seta">›</div>
                </div>
                ${licencaTipo === 'trial' ? `
                <div class="backup-card-item" onclick="mostrarTelaAtivacao()">
                    <div class="backup-emoji">🔑</div>
                    <div class="backup-info"><div class="backup-titulo">Ativar Licença</div><div class="backup-desc">Inserir chave de ativação</div></div>
                    <div class="backup-seta">›</div>
                </div>
                ` : ''}
            </div>
            <input type="file" id="inputBackup" accept=".json,application/json" style="display:none;" onchange="importarBackup(event)">
        </div>
    `;
    document.getElementById('conteudo').innerHTML = html;
}

function carregarConfiguracoes() {
    const nomeOficina = getNomeOficina();
    const html = `
        <h2>🏢 Nome da Oficina</h2>
        <div class="card">
            <label>Nome que aparecerá nos documentos:</label>
            <input type="text" id="nomeOficinaInput" value="${nomeOficina}" placeholder="Ex: Oficina do Zé">
            <p style="color:rgba(255,255,255,0.5); font-size:13px; margin:5px 0 20px;">Este nome será usado nos orçamentos, OS, relatórios e lembretes.</p>
            <button class="primary" onclick="salvarConfiguracoes()">💾 SALVAR</button>
            <button class="secondary" onclick="mostrarSecao('config')">⬅️ VOLTAR</button>
        </div>
    `;
    document.getElementById('conteudo').innerHTML = html;
}

function salvarConfiguracoes() {
    const nome = document.getElementById('nomeOficinaInput').value.trim();
    if (!nome) { alert('Digite um nome para a oficina!'); return; }
    localStorage.setItem('nomeOficina', nome);
    document.getElementById('nomeOficinaHeader').textContent = nome;
    alert('✅ Nome da oficina salvo!');
    mostrarSecao('config');
}

async function carregarBackup() {
    mostrarSecao('config');
}

async function exportarBackup() {
    const backup = { 
        clientes: await listar('clientes'), 
        veiculos: await listar('veiculos'),
        servicos: await listar('servicos'), 
        caixa: await listar('caixa'), 
        lembretes: await listar('lembretes'),
        configuracoes: { nomeOficina: getNomeOficina() }
    };
    const blob = new Blob([JSON.stringify(backup)], {type:'application/json'});
    const a = document.createElement('a'); 
    a.href = URL.createObjectURL(blob); 
    a.download = `auto-care-backup-${new Date().toISOString().split('T')[0]}.json`; 
    a.click();
    alert('✅ Backup exportado!');
}

async function importarBackup(event) {
    const file = event.target.files[0]; 
    if (!file) return;
    const reader = new FileReader();
    reader.onload = async (e) => {
        const backup = JSON.parse(e.target.result);
        if (!confirm('Substituir todos os dados?')) return;
        for (const store of ['clientes','veiculos','servicos','caixa','lembretes']) { 
            const tx = db.transaction(store, 'readwrite'); 
            await tx.objectStore(store).clear(); 
        }
        for (const c of backup.clientes||[]) await salvar('clientes', c);
        for (const v of backup.veiculos||[]) await salvar('veiculos', v);
        for (const s of backup.servicos||[]) await salvar('servicos', s);
        for (const x of backup.caixa||[]) await salvar('caixa', x);
        for (const l of backup.lembretes||[]) await salvar('lembretes', l);
        if (backup.configuracoes && backup.configuracoes.nomeOficina) {
            localStorage.setItem('nomeOficina', backup.configuracoes.nomeOficina);
            document.getElementById('nomeOficinaHeader').textContent = backup.configuracoes.nomeOficina;
        }
        alert('✅ Dados importados!'); 
        mostrarSecao('dashboard');
    };
    reader.readAsDataURL(file);
}

// ==================== DASHBOARD ====================
async function carregarDashboard() {
    try {
        const servicos = await listar('servicos');
        const clientes = await listar('clientes');
        const lembretes = await listar('lembretes');
        const hoje = new Date().toISOString().split('T')[0];
        const osAbertas = servicos.filter(s => s.status !== 'finalizado').length;
        const aguardando = servicos.filter(s => s.status === 'aguardando').length;
        const andamento = servicos.filter(s => s.status === 'andamento').length;
        const prontos = servicos.filter(s => s.status === 'pronto').length;
        const lembretesHoje = lembretes.filter(l => l.status === 'pendente' && l.dataHora.startsWith(hoje));
        const proximos = lembretes.filter(l => l.status === 'pendente').sort((a,b)=>new Date(a.dataHora)-new Date(b.dataHora)).slice(0,3);
        
        let html = `<h2>📊 Dashboard</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
                <div class="card" style="text-align:center;" onclick="mostrarSecao('os')"><div style="font-size:32px;">🔨</div><div style="font-size:24px;">${osAbertas}</div><div>OS em aberto</div></div>
                <div class="card" style="text-align:center;" onclick="mostrarSecao('clientes')"><div style="font-size:32px;">👥</div><div style="font-size:24px;">${clientes.length}</div><div>Clientes</div></div>
            </div>
            <div class="card"><div class="card-header">📊 Status</div>
                <div style="display:flex;justify-content:space-around;margin:15px 0;">
                    <div style="text-align:center;" onclick="mostrarSecao('orcamentos')"><span class="status aguardando">🟡</span><div>${aguardando}</div><div>Aguardando</div></div>
                    <div style="text-align:center;" onclick="mostrarSecao('os')"><span class="status andamento">🔵</span><div>${andamento}</div><div>Andamento</div></div>
                    <div style="text-align:center;" onclick="mostrarSecao('os')"><span class="status pronto">🟢</span><div>${prontos}</div><div>Prontos</div></div>
                </div>
            </div>
            <div class="card"><div class="card-header">🔔 Lembretes de Hoje</div>`;
        
        if (lembretesHoje.length === 0) html += '<div class="lista-vazia">Nenhum</div>';
        else lembretesHoje.forEach(l => html += `<div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.1);cursor:pointer;" onclick="dispararLembreteManual(${l.id})"><div style="display:flex;justify-content:space-between;"><strong>${l.clienteNome}</strong><span>${new Date(l.dataHora).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</span></div><div>${l.mensagem}</div></div>`);
        html += `</div>`;
        
        if (proximos.length > 0) {
            html += `<div class="card"><div class="card-header">📅 Próximos</div>`;
            proximos.forEach(l => { const d = new Date(l.dataHora); html += `<div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.1);cursor:pointer;" onclick="dispararLembreteManual(${l.id})"><div style="display:flex;justify-content:space-between;"><strong>${l.clienteNome}</strong><span>${d.toLocaleDateString('pt-BR')} ${d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</span></div><div>${l.mensagem}</div></div>`; });
            html += `</div>`;
        }
        
        document.getElementById('conteudo').innerHTML = html;
    } catch(e) {
        document.getElementById('conteudo').innerHTML = `<div style="color:white;padding:50px;text-align:center;"><h2>Erro</h2><p>${e.message}</p></div>`;
    }
}

// ==================== ORDENS DE SERVIÇO ====================
async function carregarOS() {
    const servicos = await listar('servicos');
    const clientes = await listar('clientes');
    const veiculos = await listar('veiculos');
    let html = '<h2>🔨 Ordens de Serviço</h2><div class="lista">';
    const ativos = servicos.filter(s => s.status !== 'finalizado' && s.status !== 'aguardando').sort((a,b) => b.id - a.id);
    if (ativos.length === 0) html += '<div class="lista-vazia">Nenhuma OS em aberto</div>';
    else ativos.forEach(s => {
        const c = clientes.find(x => x.id === s.clienteId) || { nome: 'Cliente removido' };
        const v = veiculos.find(x => x.id === s.veiculoId) || { tipo: '', modelo: '' };
        html += `<div class="card" onclick="verOS(${s.id})"><div class="card-header"><span>${c.nome}</span><span class="status ${s.status}">${s.status.toUpperCase()}</span></div><div>${v.tipo} ${v.modelo}</div><p>${s.descricao}</p><div class="valor-destaque">R$ ${(s.valor||0).toFixed(2)}</div></div>`;
    });
    html += '</div><button class="primary" onclick="novaOS()" style="margin-top:20px;">📝 Nova OS</button>';
    document.getElementById('conteudo').innerHTML = html;
}

async function novaOS() { await abrirFormOS('andamento'); }
async function novoOrcamento() { await abrirFormOS('aguardando'); }

async function abrirFormOS(statusInicial) {
    const clientes = await listar('clientes');
    let html = `<h2>${statusInicial === 'aguardando' ? '📋 Novo Orçamento' : '📝 Nova OS'}</h2><div class="card">
        <label>Cliente:</label><select id="clienteSelect" onchange="carregarVeiculosDoCliente()"><option value="">Selecione</option>`;
    clientes.sort((a,b)=>a.nome.localeCompare(b.nome)).forEach(c => html += `<option value="${c.id}">${c.nome}</option>`);
    html += `</select><button class="secondary" onclick="novoCliente()">➕ Novo cliente</button>
        <label>Veículo:</label><select id="veiculoSelect" disabled><option>Selecione um cliente</option></select>
        <label>Descrição:</label><textarea id="descricao" rows="3"></textarea>
        <label>Valor R$:</label><input type="number" id="valor" step="0.01">
        <label>📸 Foto:</label><input type="file" id="fotoInput" accept="image/*" capture="environment"><div id="fotoPreview" class="foto-preview" onclick="document.getElementById('fotoInput').click()"></div>
        <button class="primary" onclick="salvarServico('${statusInicial}')">💾 SALVAR</button>
        <button class="secondary" onclick="mostrarSecao('${statusInicial === 'aguardando' ? 'orcamentos' : 'os'}')">❌ Cancelar</button></div>`;
    document.getElementById('conteudo').innerHTML = html;
    document.getElementById('fotoInput').addEventListener('change', e => {
        const f = e.target.files[0]; if(f) { const r = new FileReader(); r.onload = ev => { const p = document.getElementById('fotoPreview'); p.style.backgroundImage = `url(${ev.target.result})`; p.dataset.foto = ev.target.result; }; r.readAsDataURL(f); }
    });
}

async function carregarVeiculosDoCliente() {
    const clienteId = document.getElementById('clienteSelect').value;
    const veiculoSelect = document.getElementById('veiculoSelect');
    if (!clienteId) { veiculoSelect.innerHTML = '<option>Selecione um cliente</option>'; veiculoSelect.disabled = true; return; }
    const veiculos = await listarVeiculosPorCliente(parseInt(clienteId));
    veiculoSelect.innerHTML = veiculos.length === 0 ? '<option>Nenhum veículo</option>' : '<option>Selecione</option>' + veiculos.map(v => `<option value="${v.id}">${v.tipo} - ${v.modelo} (${v.placa||'Sem placa'})</option>`).join('');
    veiculoSelect.disabled = false;
}

async function salvarServico(status) {
    const clienteId = document.getElementById('clienteSelect').value;
    const veiculoId = document.getElementById('veiculoSelect').value;
    const descricao = document.getElementById('descricao').value;
    const valor = parseFloat(document.getElementById('valor').value) || 0;
    const preview = document.getElementById('fotoPreview');
    const foto = (preview && preview.dataset && preview.dataset.foto) ? preview.dataset.foto : '';
    if (!clienteId || !veiculoId || !descricao) { alert('Preencha todos os campos!'); return; }
    const servico = { clienteId: parseInt(clienteId), veiculoId: parseInt(veiculoId), descricao, valor, foto, status, dataCriacao: new Date().toISOString() };
    await salvar('servicos', servico);
    const cliente = await buscarPorId('clientes', parseInt(clienteId));
    if (cliente && cliente.telefone) {
        const msg = status === 'aguardando' ? `🔧 *ORÇAMENTO - ${getNomeOficina()}*\n\n👤 ${cliente.nome}\n📝 ${descricao}\n💰 R$ ${valor.toFixed(2)}\n\nResponda APROVADO para confirmar.` : `🔨 *OS INICIADA - ${getNomeOficina()}*\n\n👤 ${cliente.nome}\n📝 ${descricao}\n💰 R$ ${valor.toFixed(2)}`;
        abrirWhatsApp(cliente.telefone, msg);
    }
    alert('✅ Salvo!'); mostrarSecao(status === 'aguardando' ? 'orcamentos' : 'os');
}

async function verOS(id) {
    const s = await buscarPorId('servicos', id);
    const c = await buscarPorId('clientes', s.clienteId) || { nome: 'N/I' };
    const v = (await listar('veiculos')).find(x => x.id === s.veiculoId) || { tipo: '', modelo: '' };
    let html = `<h2>${s.status === 'aguardando' ? '📋 Orçamento' : '🔨 OS'} #${id}</h2><div class="card">
        <div class="card-header"><span>${c.nome}</span><span class="status ${s.status}">${s.status.toUpperCase()}</span></div>
        <div>📱 ${c.telefone||'Sem'}</div><div>🚗 ${v.tipo} ${v.modelo}</div><h3>Descrição</h3><p>${s.descricao}</p><div class="valor-destaque">R$ ${(s.valor||0).toFixed(2)}</div>`;
    if (s.foto) html += `<img src="${s.foto}" style="width:100%;border-radius:12px;margin:15px 0;">`;
    if (s.status === 'aguardando') html += `<button class="primary" onclick="aprovarOS(${id})" style="background:#4ade80;">✅ APROVAR</button>`;
    html += `<h3>Status</h3><select id="novoStatus">
        <option value="aguardando" ${s.status==='aguardando'?'selected':''}>Aguardando</option>
        <option value="andamento" ${s.status==='andamento'?'selected':''}>Em andamento</option>
        <option value="pronto" ${s.status==='pronto'?'selected':''}>Pronto</option>
        <option value="finalizado" ${s.status==='finalizado'?'selected':''}>Finalizado</option>
    </select>
    <div style="display:flex;gap:10px;margin:15px 0;">
        <button class="primary" onclick="atualizarStatus(${id})">📌 ATUALIZAR</button>
        ${s.status === 'aguardando' 
            ? `<button class="secondary" onclick="gerarPDFOrcamento(${id})">🖨️ PDF ORÇAMENTO</button>`
            : `<button class="secondary" onclick="gerarPDFOS(${id})">🖨️ PDF OS</button>`
        }
    </div>
    ${c.telefone?`<button class="secondary" style="background:#25D366;" onclick="enviarWhatsApp(${id})">💬 WHATSAPP</button>`:''}
    ${s.foto ? `
        <button class="secondary" style="background:#128C7E;color:white;" onclick="enviarFotosWhatsApp(${id})">📸 ENVIAR FOTOS</button>
        <button class="secondary" style="background:#f59e0b;color:white;" onclick="baixarFoto('${s.foto}')">⬇️ BAIXAR FOTO</button>
    ` : ''}
    <button class="secondary" onclick="abrirModalLembrete(${id},'${c.nome.replace(/'/g,"\\'")}','${c.telefone||''}')">🔔 LEMBRETE</button>
    <button class="secondary" onclick="mostrarSecao('${s.status === 'aguardando' ? 'orcamentos' : 'os'}')">⬅️ VOLTAR</button></div>`;
    document.getElementById('conteudo').innerHTML = html;
}

async function aprovarOS(id) {
    const s = await buscarPorId('servicos', id);
    s.status = 'andamento';
    await atualizar('servicos', s);
    alert('✅ Orçamento aprovado!');
    verOS(id);
}

async function atualizarStatus(id) {
    const s = await buscarPorId('servicos', id);
    const novo = document.getElementById('novoStatus').value;
    s.status = novo;
    if (novo === 'pronto') {
        const c = await buscarPorId('clientes', s.clienteId);
        if (c && c.telefone) abrirWhatsApp(c.telefone, `🚗 *${getNomeOficina()}*\n\nSeu veículo está pronto para retirada!`);
    }
    await atualizar('servicos', s);
    alert('✅ Status atualizado!');
    verOS(id);
}

async function enviarWhatsApp(id) {
    const s = await buscarPorId('servicos', id);
    const c = await buscarPorId('clientes', s.clienteId);
    if (c && c.telefone) abrirWhatsApp(c.telefone, `🚗 *${getNomeOficina()}*\n\n👤 ${c.nome}\n📝 ${s.descricao}\n💰 R$ ${(s.valor||0).toFixed(2)}`);
}

async function enviarFotosWhatsApp(id) {
    const servico = await buscarPorId('servicos', id);
    const cliente = await buscarPorId('clientes', servico.clienteId);
    const veiculos = await listar('veiculos');
    const veiculo = veiculos.find(v => v.id === servico.veiculoId) || { tipo: 'Veículo', modelo: '' };
    if (!cliente || !cliente.telefone) { alert('Cliente não tem WhatsApp!'); return; }
    if (!servico.foto) { alert('Esta OS não tem foto!'); return; }
    abrirWhatsApp(cliente.telefone, `📸 *FOTOS DO SERVIÇO - ${getNomeOficina()}*\n\n👤 ${cliente.nome}\n🚗 ${veiculo.tipo} - ${veiculo.modelo}\n📝 ${servico.descricao}`);
    alert(`📸 Agora é só anexar a foto no WhatsApp!\n\n1. Toque no ícone de anexar (📎)\n2. Selecione "Galeria" e escolha a foto`);
}

function baixarFoto(fotoBase64) {
    const link = document.createElement('a');
    link.href = fotoBase64;
    link.download = `auto-care-${Date.now()}.jpg`;
    link.click();
    alert('✅ Foto salva na galeria!');
}

// ==================== CLIENTES ====================
async function carregarClientes() {
    const clientes = await listar('clientes');
    let html = '<h2>👥 Clientes</h2><div class="lista">';
    if (clientes.length === 0) html += '<div class="lista-vazia">Nenhum</div>';
    else clientes.sort((a,b)=>a.nome.localeCompare(b.nome)).forEach(c => html += `<div class="card" onclick="verCliente(${c.id})"><div class="card-header">${c.nome}</div><div>📱 ${c.telefone||'Sem'}</div></div>`);
    html += '</div><button class="primary" onclick="novoCliente()" style="margin-top:20px;">👤 Novo Cliente</button>';
    document.getElementById('conteudo').innerHTML = html;
}

async function novoCliente() {
    document.getElementById('conteudo').innerHTML = `<h2>👤 Novo Cliente</h2><div class="card">
        <input id="nomeCliente" placeholder="Nome *"><input id="telefoneCliente" placeholder="WhatsApp *"><input id="emailCliente" placeholder="E-mail">
        <h3>🚗 Veículos</h3><div id="listaVeiculosCadastro"></div>
        <button class="secondary" onclick="adicionarVeiculoCadastro()">➕ Adicionar</button>
        <button class="primary" onclick="salvarClienteCompleto()">💾 SALVAR</button>
        <button class="secondary" onclick="mostrarSecao('clientes')">❌ Cancelar</button></div>`;
}

function adicionarVeiculoCadastro() {
    const div = document.createElement('div');
    div.innerHTML = `<div class="veiculo-item" style="background:#0a0b14;padding:15px;border-radius:12px;margin-bottom:10px;">
        <select class="tipoVeiculo"><option>Moto</option><option>Carro</option><option>Caminhão</option></select>
        <input class="modeloVeiculo" placeholder="Modelo"><input class="placaVeiculo" placeholder="Placa">
        <input class="anoVeiculo" placeholder="Ano"><input class="corVeiculo" placeholder="Cor">
        <button class="secondary" onclick="this.parentElement.remove()">🗑️ Remover</button></div>`;
    document.getElementById('listaVeiculosCadastro').appendChild(div.firstElementChild);
}

async function salvarClienteCompleto() {
    const nome = document.getElementById('nomeCliente').value.trim();
    if (!nome) { alert('Nome obrigatório!'); return; }
    const clienteId = await salvar('clientes', { nome, telefone: document.getElementById('telefoneCliente').value, email: document.getElementById('emailCliente').value, dataCadastro: new Date().toISOString() });
    document.querySelectorAll('.veiculo-item').forEach(async item => {
        const modelo = item.querySelector('.modeloVeiculo').value.trim();
        if (modelo) await salvar('veiculos', { clienteId, tipo: item.querySelector('.tipoVeiculo').value, modelo, placa: item.querySelector('.placaVeiculo').value, ano: item.querySelector('.anoVeiculo').value, cor: item.querySelector('.corVeiculo').value });
    });
    alert('✅ Cliente salvo!'); mostrarSecao('clientes');
}

async function verCliente(id) {
    const c = await buscarPorId('clientes', id);
    const veiculos = await listarVeiculosPorCliente(id);
    const servicos = (await listar('servicos')).filter(s => s.clienteId === id);
    let html = `<h2>👤 ${c.nome}</h2><div class="card"><div>📱 ${c.telefone||'Sem'}</div></div>
        <div style="display:flex;gap:10px;margin:15px 0;"><button class="primary" onclick="editarCliente(${id})" style="flex:1;">✏️ EDITAR</button></div>
        <h3>🚗 Veículos</h3>`;
    veiculos.forEach(v => html += `<div class="card"><strong>${v.tipo} - ${v.modelo}</strong><br>Placa: ${v.placa||'N/I'}</div>`);
    html += `<h3>Histórico</h3>`;
    servicos.sort((a,b)=>b.id-a.id).forEach(s => { const v = veiculos.find(x=>x.id===s.veiculoId)||{modelo:'N/I'}; html += `<div class="card" onclick="verOS(${s.id})"><div class="card-header">OS #${s.id}<span class="status ${s.status}">${s.status.toUpperCase()}</span></div><p>${v.modelo} - ${s.descricao}</p><div class="valor-destaque">R$ ${(s.valor||0).toFixed(2)}</div></div>`; });
    html += `<button class="primary" onclick="novaOSParaCliente(${id})">📝 Nova OS</button><button class="secondary" onclick="mostrarSecao('clientes')">⬅️ Voltar</button>`;
    document.getElementById('conteudo').innerHTML = html;
}

async function editarCliente(id) {
    const cliente = await buscarPorId('clientes', id);
    const veiculos = await listarVeiculosPorCliente(id);
    let html = `<h2>✏️ Editar Cliente</h2><div class="card">
        <label>Nome:</label><input id="editNomeCliente" value="${cliente.nome}">
        <label>WhatsApp:</label><input id="editTelefoneCliente" value="${cliente.telefone||''}">
        <label>E-mail:</label><input id="editEmailCliente" value="${cliente.email||''}">
        <h3>🚗 Veículos</h3><div id="editListaVeiculos"></div>
        <button class="secondary" onclick="adicionarVeiculoEdicao()">➕ Adicionar</button>
        <button class="primary" onclick="salvarEdicaoCliente(${id})">💾 SALVAR</button>
        <button class="secondary" onclick="verCliente(${id})">❌ CANCELAR</button></div>`;
    document.getElementById('conteudo').innerHTML = html;
    veiculos.forEach(v => adicionarVeiculoEdicao(v));
}

function adicionarVeiculoEdicao(veiculo = null) {
    const div = document.createElement('div');
    div.className = 'veiculo-item';
    div.style.cssText = 'background:#0a0b14;padding:15px;border-radius:12px;margin-bottom:10px;';
    div.innerHTML = `<select class="editTipoVeiculo"><option value="Moto" ${veiculo&&veiculo.tipo==='Moto'?'selected':''}>Moto</option><option value="Carro" ${veiculo&&veiculo.tipo==='Carro'?'selected':''}>Carro</option><option value="Caminhao" ${veiculo&&veiculo.tipo==='Caminhao'?'selected':''}>Caminhão</option></select>
        <input class="editModeloVeiculo" placeholder="Modelo" value="${veiculo?veiculo.modelo:''}"><input class="editPlacaVeiculo" placeholder="Placa" value="${veiculo?veiculo.placa:''}">
        <input class="editAnoVeiculo" placeholder="Ano" value="${veiculo?veiculo.ano:''}"><input class="editCorVeiculo" placeholder="Cor" value="${veiculo?veiculo.cor:''}">
        ${veiculo?`<input type="hidden" class="editVeiculoId" value="${veiculo.id}">`:''}
        <button class="secondary" onclick="this.parentElement.remove()">🗑️ Remover</button>`;
    document.getElementById('editListaVeiculos').appendChild(div);
}

async function salvarEdicaoCliente(id) {
    const nome = document.getElementById('editNomeCliente').value.trim();
    if (!nome) { alert('Nome obrigatório!'); return; }
    const cliente = await buscarPorId('clientes', id);
    cliente.nome = nome;
    cliente.telefone = document.getElementById('editTelefoneCliente').value;
    cliente.email = document.getElementById('editEmailCliente').value;
    await atualizar('clientes', cliente);
    const veiculoItems = document.querySelectorAll('#editListaVeiculos .veiculo-item');
    for (const item of veiculoItems) {
        const veiculoId = item.querySelector('.editVeiculoId') ? parseInt(item.querySelector('.editVeiculoId').value) : null;
        const tipo = item.querySelector('.editTipoVeiculo').value;
        const modelo = item.querySelector('.editModeloVeiculo').value.trim();
        const placa = item.querySelector('.editPlacaVeiculo').value.trim();
        const ano = item.querySelector('.editAnoVeiculo').value.trim();
        const cor = item.querySelector('.editCorVeiculo').value.trim();
        if (modelo) {
            if (veiculoId) {
                const v = await buscarPorId('veiculos', veiculoId);
                v.tipo = tipo; v.modelo = modelo; v.placa = placa; v.ano = ano; v.cor = cor;
                await atualizar('veiculos', v);
            } else {
                await salvar('veiculos', { clienteId: id, tipo, modelo, placa, ano, cor });
            }
        }
    }
    alert('✅ Cliente atualizado!'); verCliente(id);
}

async function novaOSParaCliente(id) { await novaOS(); setTimeout(() => { document.getElementById('clienteSelect').value = id; carregarVeiculosDoCliente(); }, 100); }

// ==================== ORÇAMENTOS ====================
async function carregarOrcamentos() {
    const servicos = await listar('servicos');
    const clientes = await listar('clientes');
    const veiculos = await listar('veiculos');
    let html = '<h2>📋 Orçamentos</h2><div class="lista">';
    const orcamentos = servicos.filter(s => s.status === 'aguardando').sort((a,b)=>b.id-a.id);
    if (orcamentos.length === 0) html += '<div class="lista-vazia">Nenhum pendente</div>';
    else orcamentos.forEach(o => { const c = clientes.find(x=>x.id===o.clienteId)||{nome:'N/I'}; const v = veiculos.find(x=>x.id===o.veiculoId)||{tipo:'',modelo:''}; html += `<div class="card" onclick="verOS(${o.id})"><div class="card-header"><span>${c.nome}</span><span class="status aguardando">AGUARDANDO</span></div><p>${v.tipo} ${v.modelo} - ${o.descricao}</p><div class="valor-destaque">R$ ${(o.valor||0).toFixed(2)}</div></div>`; });
    html += '</div><button class="primary" onclick="novoOrcamento()" style="margin-top:20px;">📋 Novo Orçamento</button>';
    document.getElementById('conteudo').innerHTML = html;
}

// ==================== FINANCEIRO ====================
async function carregarFinanceiro() {
    const lancamentos = await listar('caixa');
    const hoje = new Date().toISOString().split('T')[0];
    const doDia = lancamentos.filter(l => l.data && l.data.startsWith(hoje));
    let saldo = 0; doDia.forEach(l => saldo += l.tipo==='entrada'?l.valor:-l.valor);
    let html = `<h2>💵 Caixa</h2><div class="card" style="background:#151624;"><div>Saldo do dia</div><div style="font-size:32px;">R$ ${saldo.toFixed(2)}</div></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:15px 0;">
            <button class="primary" onclick="mostrarOSFinalizadasParaPagamento()">💰 Pagamento de OS</button>
            <button class="secondary" onclick="novaTransacao()">📝 Lançamento Manual</button>
        </div>
        <h3>Lançamentos de Hoje</h3><div class="lista">`;
    if (doDia.length === 0) html += '<div class="lista-vazia">Nenhum</div>';
    else doDia.sort((a,b)=>b.id-a.id).forEach(l => html += `<div class="card"><div class="card-header"><span>${l.descricao}</span><span style="color:${l.tipo==='entrada'?'#4ade80':'#ef4444'}">${l.tipo==='entrada'?'+':'-'} R$ ${l.valor.toFixed(2)}</span></div><div>${l.hora}</div></div>`);
    html += '</div>'; document.getElementById('conteudo').innerHTML = html;
}

async function novaTransacao() {
    document.getElementById('conteudo').innerHTML = `<h2>💵 Novo Lançamento</h2><select id="tipoTransacao"><option value="entrada">💰 Entrada</option><option value="saida">💸 Saída</option></select><input id="valorTransacao" placeholder="Valor" step="0.01"><input id="descricaoTransacao" placeholder="Descrição"><button class="primary" onclick="salvarTransacao()">💾 SALVAR</button><button class="secondary" onclick="mostrarSecao('financeiro')">❌ Cancelar</button>`;
}

async function salvarTransacao() {
    const tipo = document.getElementById('tipoTransacao').value;
    const valor = parseFloat(document.getElementById('valorTransacao').value);
    const descricao = document.getElementById('descricaoTransacao').value.trim();
    if (!valor || !descricao) { alert('Preencha!'); return; }
    await salvar('caixa', { tipo, valor, descricao, data: new Date().toISOString(), hora: new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}) });
    alert('✅ Salvo!'); mostrarSecao('financeiro');
}

async function atualizarSaldo() {
    const caixa = await listar('caixa');
    const hoje = new Date().toISOString().split('T')[0];
    let saldo = 0; caixa.filter(l => l.data && l.data.startsWith(hoje)).forEach(l => saldo += l.tipo==='entrada'?l.valor:-l.valor);
    document.getElementById('saldoDia').textContent = saldo.toFixed(2);
}

async function mostrarOSFinalizadasParaPagamento() {
    const servicos = await listar('servicos');
    const clientes = await listar('clientes');
    const caixa = await listar('caixa');
    
    const osFinalizadas = servicos.filter(s => s.status === 'finalizado');
    
    const osNaoPagas = osFinalizadas.filter(os => {
        const temPagamento = caixa.some(c => 
            c.tipo === 'entrada' && 
            c.descricao && 
            c.descricao.includes(`OS #${os.id}`)
        );
        return !temPagamento;
    });
    
    if (osNaoPagas.length === 0) { 
        alert('✅ Todas as OS finalizadas já foram pagas!'); 
        return; 
    }
    
    let html = `<h2>💰 Selecionar OS para Pagamento</h2>
        <p style="color:rgba(255,255,255,0.7); margin-bottom:15px;">${osNaoPagas.length} OS aguardando pagamento</p>
        <div class="lista">`;
    
    osNaoPagas.sort((a,b) => b.id - a.id).forEach(os => {
        const cliente = clientes.find(c => c.id === os.clienteId) || { nome: 'Cliente removido' };
        html += `
            <div class="card" onclick="lancarPagamentoOS(${os.id}, ${os.valor || 0}, '${cliente.nome.replace(/'/g, "\\'")}')">
                <div class="card-header">
                    <span>${cliente.nome}</span>
                    <span>OS #${os.id}</span>
                </div>
                <p>${os.descricao || 'Sem descrição'}</p>
                <div class="valor-destaque">R$ ${(os.valor || 0).toFixed(2)}</div>
                <div style="font-size:12px;color:#4ade80;margin-top:5px;">✅ Clique para lançar pagamento</div>
            </div>
        `;
    });
    
    html += `</div><button class="secondary" onclick="mostrarSecao('financeiro')">⬅️ VOLTAR</button>`;
    document.getElementById('conteudo').innerHTML = html;
}

function lancarPagamentoOS(osId, valor, clienteNome) {
    if (confirm(`Lançar pagamento de R$ ${(valor||0).toFixed(2)} para OS #${osId} - ${clienteNome}?`)) {
        salvar('caixa', { tipo: 'entrada', valor: valor||0, descricao: `OS #${osId} - ${clienteNome}`, data: new Date().toISOString(), hora: new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}) }).then(() => {
            alert('✅ Pagamento lançado!'); mostrarSecao('financeiro'); atualizarSaldo();
        });
    }
}

// ==================== RELATÓRIOS ====================
async function carregarRelatorios() {
    document.getElementById('conteudo').innerHTML = `
        <h2>📊 Relatórios</h2>
        <div class="card">
            <select id="periodoRelatorio" onchange="atualizarRelatorio()">
                <option value="mes">Este mês</option>
                <option value="ano">Este ano</option>
            </select>
            <div id="relatorioContent">Carregando...</div>
        </div>
    `;
    atualizarRelatorio();
}

async function atualizarRelatorio() {
    const periodo = document.getElementById('periodoRelatorio').value;
    const caixa = await listar('caixa');
    const hoje = new Date();
    let dataInicio = periodo === 'mes' ? new Date(hoje.getFullYear(), hoje.getMonth(), 1) : new Date(hoje.getFullYear(), 0, 1);
    const caixaPeriodo = caixa.filter(c => c.data && new Date(c.data) >= dataInicio);
    const entradas = caixaPeriodo.filter(c => c.tipo === 'entrada').reduce((t,c)=>t+(c.valor||0),0);
    const saidas = caixaPeriodo.filter(c => c.tipo === 'saida').reduce((t,c)=>t+(c.valor||0),0);
    const saldo = entradas - saidas;
    document.getElementById('relatorioContent').innerHTML = `<div class="card-relatorio"><div>ENTRADAS</div><div class="valor-grande" style="color:#4ade80;">R$ ${entradas.toFixed(2)}</div></div><div class="card-relatorio"><div>SAÍDAS</div><div class="valor-grande" style="color:#ef4444;">R$ ${saidas.toFixed(2)}</div></div><div class="card-relatorio"><div>SALDO</div><div class="valor-grande" style="color:${saldo>=0?'#4ade80':'#ef4444'};">R$ ${saldo.toFixed(2)}</div></div><button class="primary" onclick="exportarRelatorioPDF()">📄 EXPORTAR PDF</button>`;
}

async function exportarRelatorioPDF() {
    const { jsPDF } = window.jspdf; if (!jsPDF) return;
    const doc = new jsPDF();
    const caixa = await listar('caixa');
    const hoje = new Date(); const hojeStr = hoje.toISOString().split('T')[0];
    const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    const caixaDia = caixa.filter(c => c.data && c.data.startsWith(hojeStr));
    const caixaMes = caixa.filter(c => c.data && new Date(c.data) >= inicioMes);
    const entradasDia = caixaDia.filter(c=>c.tipo==='entrada').reduce((t,c)=>t+(c.valor||0),0);
    const saidasDia = caixaDia.filter(c=>c.tipo==='saida').reduce((t,c)=>t+(c.valor||0),0);
    const entradasMes = caixaMes.filter(c=>c.tipo==='entrada').reduce((t,c)=>t+(c.valor||0),0);
    const saidasMes = caixaMes.filter(c=>c.tipo==='saida').reduce((t,c)=>t+(c.valor||0),0);
    
    doc.setFillColor(21,22,36); doc.rect(0,0,210,35,'F');
    doc.setTextColor(255,255,255); doc.setFontSize(18); doc.text(getNomeOficina(), 105, 18, {align:'center'});
    doc.setFontSize(10); doc.text('Relatório Financeiro', 105, 26, {align:'center'});
    doc.setTextColor(0,0,0);
    doc.setFillColor(240,245,255); doc.roundedRect(20,50,80,25,4,4,'F'); doc.roundedRect(110,50,80,25,4,4,'F');
    doc.setFontSize(9); doc.setTextColor(100,100,100); doc.text('Saldo do Dia',25,60); doc.text('Saldo do Mês',115,60);
    doc.setFontSize(13); doc.setFont('helvetica','bold'); doc.setTextColor(21,22,36);
    doc.text(`R$ ${(entradasDia-saidasDia).toFixed(2)}`,25,71); doc.text(`R$ ${(entradasMes-saidasMes).toFixed(2)}`,115,71);
    
    let y=95;
    doc.setFontSize(12); doc.text('Movimentações de Hoje',20,y); y+=10;
    doc.setFillColor(21,22,36); doc.rect(20,y,170,8,'F'); doc.setTextColor(255,255,255); doc.setFontSize(9);
    doc.text('Descrição',25,y+6); doc.text('Tipo',120,y+6); doc.text('Valor',170,y+6,{align:'right'});
    y+=12; doc.setTextColor(0,0,0); doc.setFont('helvetica','normal');
    
    caixaDia.sort((a,b)=>b.id-a.id).forEach((c,i)=>{
        if(i%2===0){ doc.setFillColor(245,245,245); doc.rect(20,y-5,170,8,'F'); }
        doc.text(c.descricao.substring(0,25),25,y);
        doc.text(c.tipo==='entrada'?'ENTRADA':'SAÍDA',120,y);
        doc.setTextColor(c.tipo==='entrada'?74:239,c.tipo==='entrada'?222:68,c.tipo==='entrada'?128:68);
        doc.text(`R$ ${c.valor.toFixed(2)}`,170,y,{align:'right'});
        doc.setTextColor(0,0,0);
        y+=10;
    });
    
    doc.setFontSize(10); doc.setTextColor(150,150,150);
    doc.text(getNomeOficina(),105,285,{align:'center'});
    doc.text(`Gerado em ${hoje.toLocaleDateString('pt-BR')}`,105,290,{align:'center'});
    window.open(URL.createObjectURL(doc.output('blob')),'_blank');
}

async function gerarPDFOS(id) {
    const { jsPDF } = window.jspdf; if (!jsPDF) return;
    const doc = new jsPDF();
    const s = await buscarPorId('servicos', id);
    const c = await buscarPorId('clientes', s.clienteId);
    const v = (await listar('veiculos')).find(x=>x.id===s.veiculoId)||{tipo:'',modelo:'',placa:'',ano:'',cor:''};
    doc.setFillColor(21,22,36); doc.rect(0,0,210,40,'F');
    doc.setTextColor(255,255,255); doc.setFontSize(18); doc.text(getNomeOficina(),105,16,{align:'center'});
    doc.setFontSize(10); doc.text('Ordem de Serviço',105,26,{align:'center'});
    doc.setTextColor(0,0,0); doc.setFontSize(13); doc.text(`OS Nº ${id}`,20,52);
    doc.setFontSize(10); doc.text(`Cliente: ${c.nome}`,20,65); doc.text(`Telefone: ${c.telefone||'N/I'}`,20,73);
    doc.text(`Veículo: ${v.tipo} - ${v.modelo} | Placa: ${v.placa}`,20,83);
    doc.text(`Data: ${new Date(s.dataCriacao).toLocaleDateString('pt-BR')}`,20,93);
    doc.text(`Descrição: ${s.descricao}`,20,105,{maxWidth:170});
    doc.setFillColor(240,245,255); doc.roundedRect(20,140,170,25,4,4,'F');
    doc.setFontSize(11); doc.setTextColor(100,100,100); doc.text('VALOR TOTAL',25,152);
    doc.setFontSize(16); doc.setFont('helvetica','bold'); doc.setTextColor(34,197,94);
    doc.text(`R$ ${(s.valor||0).toFixed(2)}`,170,154,{align:'right'});
    doc.setTextColor(150,150,150); doc.setFontSize(8); doc.setFont('helvetica','normal');
    doc.text(getNomeOficina(),105,285,{align:'center'});
    window.open(URL.createObjectURL(doc.output('blob')),'_blank');
}

async function gerarPDFOrcamento(id) {
    const { jsPDF } = window.jspdf; if (!jsPDF) return;
    const doc = new jsPDF();
    const s = await buscarPorId('servicos', id);
    const c = await buscarPorId('clientes', s.clienteId);
    const v = (await listar('veiculos')).find(x=>x.id===s.veiculoId)||{tipo:'',modelo:'',placa:'',ano:'',cor:''};
    doc.setFillColor(21,22,36); doc.rect(0,0,210,40,'F');
    doc.setTextColor(255,255,255); doc.setFontSize(18); doc.text(getNomeOficina(),105,16,{align:'center'});
    doc.setFontSize(10); doc.text('Orçamento',105,26,{align:'center'});
    doc.setTextColor(0,0,0); doc.setFontSize(13); doc.text(`Orçamento Nº ${id}`,20,52);
    doc.setFontSize(10); doc.text(`Cliente: ${c.nome}`,20,65); doc.text(`Telefone: ${c.telefone||'N/I'}`,20,73);
    doc.text(`Veículo: ${v.tipo} - ${v.modelo} | Placa: ${v.placa}`,20,83);
    doc.text(`Data: ${new Date(s.dataCriacao).toLocaleDateString('pt-BR')}`,20,93);
    doc.text(`Descrição: ${s.descricao}`,20,105,{maxWidth:170});
    doc.setFillColor(240,245,255); doc.roundedRect(20,140,170,25,4,4,'F');
    doc.setFontSize(11); doc.setTextColor(100,100,100); doc.text('VALOR DO ORÇAMENTO',25,152);
    doc.setFontSize(16); doc.setFont('helvetica','bold'); doc.setTextColor(34,197,94);
    doc.text(`R$ ${(s.valor||0).toFixed(2)}`,170,154,{align:'right'});
    doc.setFontSize(9); doc.setTextColor(100,100,100);
    doc.text('Este orçamento tem validade de 7 dias.',20,180);
    doc.setTextColor(150,150,150); doc.setFontSize(8);
    doc.text(getNomeOficina(),105,285,{align:'center'});
    window.open(URL.createObjectURL(doc.output('blob')),'_blank');
}
