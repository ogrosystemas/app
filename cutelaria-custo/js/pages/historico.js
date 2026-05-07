// ============================================
// CUTELARIA CUSTO - HISTORICO.JS
// Histórico de produção
// ============================================

const HistoricoPage = {
    data: {
        facas: [],
        filtro: ''
    },

    async render() {
        this.data.facas = await Database.getFacas();

        return `
            <div class="page-actions">
                <button class="btn btn-secondary" onclick="HistoricoPage.filtrar()">
                    🔍 Filtrar
                </button>
            </div>
            <div class="lista-cards" id="lista-historico">
                ${this.renderLista()}
            </div>
        `;
    },

    renderLista() {
        let facas = [...this.data.facas].reverse();

        if (this.data.filtro) {
            const f = this.data.filtro.toLowerCase();
            facas = facas.filter(faca => 
                faca.nome.toLowerCase().includes(f) ||
                (faca.cliente && faca.cliente.toLowerCase().includes(f))
            );
        }

        if (facas.length === 0) {
            return UI.emptyState('Nenhuma faca no histórico', '📜');
        }

        return facas.map(f => UI.facaCard(f)).join('');
    },

    init() {
        const viewId = Router.getParam('view');
        if (viewId) {
            this.verDetalhes(parseInt(viewId));
        }
    },

    filtrar() {
        const termo = prompt('Filtrar por nome ou cliente:');
        if (termo !== null) {
            this.data.filtro = termo;
            document.getElementById('lista-historico').innerHTML = this.renderLista();
        }
    },

    async verDetalhes(id) {
        const faca = await Database.getFacaById(id);
        if (!faca) return;

        const content = `
            <div class="detalhe-grupo">
                <h4>📋 Informações</h4>
                <div class="detalhe-item"><span>Nome:</span><span>${faca.nome}</span></div>
                ${faca.cliente ? `<div class="detalhe-item"><span>Cliente:</span><span>${faca.cliente}</span></div>` : ''}
                <div class="detalhe-item"><span>Data:</span><span>${Formatters.date(faca.data)}</span></div>
                <div class="detalhe-item"><span>Margem:</span><span>${faca.margem}%</span></div>
            </div>

            ${faca.materiais?.length > 0 ? `
            <div class="detalhe-grupo">
                <h4>📦 Materiais</h4>
                ${faca.materiais.map(m => `
                    <div class="detalhe-item">
                        <span>${m.nome} (${m.quantidade} ${m.unidade})</span>
                        <span>${Formatters.currency(m.custo)}</span>
                    </div>
                `).join('')}
            </div>
            ` : ''}

            ${faca.insumos?.length > 0 ? `
            <div class="detalhe-grupo">
                <h4>🧪 Insumos</h4>
                ${faca.insumos.map(i => `
                    <div class="detalhe-item">
                        <span>${i.nome} (${i.quantidade} ${i.unidade})</span>
                        <span>${Formatters.currency(i.custo)}</span>
                    </div>
                `).join('')}
            </div>
            ` : ''}

            ${faca.equipamentos?.length > 0 ? `
            <div class="detalhe-grupo">
                <h4>🔧 Equipamentos</h4>
                ${faca.equipamentos.map(e => `
                    <div class="detalhe-item">
                        <span>${e.nome} (${e.horas}h)</span>
                        <span>${Formatters.currency(e.custo)}</span>
                    </div>
                `).join('')}
            </div>
            ` : ''}

            <div class="detalhe-grupo">
                <h4>⚡ Custos</h4>
                <div class="detalhe-item"><span>Energia (${faca.kwh} kWh):</span><span>${Formatters.currency(faca.custoEnergia)}</span></div>
                <div class="detalhe-item"><span>Gás/Carvão:</span><span>${Formatters.currency(faca.custoGas)}</span></div>
                <div class="detalhe-item"><span>Mão de Obra (${faca.horasTrabalho}h):</span><span>${Formatters.currency(faca.custoMaoObra)}</span></div>
                <div class="detalhe-item"><span>Perda (${faca.perda}%):</span><span>${Formatters.currency(faca.custoPerda)}</span></div>
            </div>

            <div class="detalhe-total">
                <div class="detalhe-total-row"><span>Custo Total:</span><span>${Formatters.currency(faca.custoTotal)}</span></div>
                <div class="detalhe-total-row grande"><span>Preço de Venda:</span><span>${Formatters.currency(faca.precoVenda)}</span></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="BackupService.exportarFaca(${faca.id})">📤 Exportar</button>
                <button class="btn btn-danger" onclick="HistoricoPage.excluir(${faca.id})">🗑️ Excluir</button>
                <button class="btn btn-secondary" onclick="Modal.close()">Fechar</button>
            </div>
        `;

        Modal.open(content, { title: '🔪 Detalhes da Faca', large: true });
    },

    async excluir(id) {
        Modal.confirm('Excluir esta faca do histórico?', async () => {
            await Database.deleteFaca(id);
            Toast.success('Faca removida');
            Modal.close();
            this.data.facas = await Database.getFacas();
            document.getElementById('lista-historico').innerHTML = this.renderLista();
        });
    }
};
