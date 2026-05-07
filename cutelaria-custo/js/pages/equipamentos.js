// ============================================
// CUTELARIA CUSTO - EQUIPAMENTOS.JS
// Ferramentas e depreciação
// ============================================

const EquipamentosPage = {
    data: {
        equipamentos: [],
        editando: null
    },

    async render() {
        this.data.equipamentos = await Database.getEquipamentos();

        return `
            <div class="page-actions">
                <button class="btn btn-primary" onclick="EquipamentosPage.abrirModal()">
                    ➕ Novo Equipamento
                </button>
            </div>
            <div class="lista-cards" id="lista-equipamentos">
                ${this.renderLista()}
            </div>
        `;
    },

    renderLista() {
        if (this.data.equipamentos.length === 0) {
            return UI.emptyState('Nenhum equipamento cadastrado. Adicione esmeril, lixadeira, forja...', '🔧');
        }

        return this.data.equipamentos.map(eq => {
            const custoHora = Calculations.custoHoraEquipamento(eq.preco, eq.vidaUtil);
            return UI.itemCard(eq, {
                badges: [{ class: 'badge-outro', text: Formatters.tipoEquipamento(eq.tipo) }],
                extraInfo: [
                    { text: `⏱️ ${eq.vidaUtil}h vida útil` },
                    { text: `💰 ${Formatters.currency(custoHora)}/h` },
                    { text: Formatters.date(eq.dataAquisicao) }
                ],
                onDelete: 'EquipamentosPage.excluir'
            });
        }).join('');
    },

    init() {
        // Nada especial
    },

    abrirModal(equip = null) {
        this.data.editando = equip ? equip.id : null;
        const isEdit = !!equip;

        const tipoOptions = Object.entries(TIPOS_EQUIPAMENTO).map(([key, val]) => 
            `<option value="${key}" ${isEdit && equip.tipo === key ? 'selected' : ''}>${val.icon} ${val.label}</option>`
        ).join('');

        const content = `
            <form id="form-equip" onsubmit="EquipamentosPage.salvar(event)">
                ${UI.formGroup('Nome', `<input type="text" id="eq-nome" placeholder="Ex: Lixadeira de Cinta" value="${isEdit ? equip.nome : ''}" required>`)}

                <div class="form-row">
                    ${UI.formGroup('Tipo', `<select id="eq-tipo">${tipoOptions}</select>`)}
                    ${UI.formGroup('Preço Aquisição (R$)', `<input type="number" id="eq-preco" step="0.01" min="0" placeholder="0,00" value="${isEdit ? equip.preco : ''}" required>`)}
                </div>

                <div class="form-row">
                    ${UI.formGroup('Vida Útil (horas)', `<input type="number" id="eq-vida" step="1" min="1" placeholder="500" value="${isEdit ? equip.vidaUtil : ''}" required>`)}
                    ${UI.formGroup('Data Aquisição', `<input type="date" id="eq-data" value="${isEdit ? equip.dataAquisicao : new Date().toISOString().split('T')[0]}" required>`)}
                </div>

                ${UI.formGroup('Notas', `<textarea id="eq-notas" rows="2" placeholder="Observações...">${isEdit ? (equip.notas || '') : ''}</textarea>`)}

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">💾 ${isEdit ? 'Atualizar' : 'Salvar'}</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
                </div>
            </form>
        `;

        Modal.open(content, { 
            title: isEdit ? '✏️ Editar Equipamento' : '🔧 Novo Equipamento',
            onClose: () => { this.data.editando = null; }
        });
    },

    async salvar(e) {
        e.preventDefault();

        const equip = {
            nome: document.getElementById('eq-nome').value,
            tipo: document.getElementById('eq-tipo').value,
            preco: parseFloat(document.getElementById('eq-preco').value),
            vidaUtil: parseFloat(document.getElementById('eq-vida').value),
            dataAquisicao: document.getElementById('eq-data').value,
            notas: document.getElementById('eq-notas').value || null
        };

        try {
            if (this.data.editando) {
                await Database.updateEquipamento(this.data.editando, equip);
                Toast.success('Equipamento atualizado!');
            } else {
                await Database.addEquipamento(equip);
                Toast.success('Equipamento cadastrado!');
            }

            Modal.close();
            this.data.editando = null;
            await this.refresh();
        } catch (err) {
            Toast.error('Erro ao salvar: ' + err.message);
        }
    },

    async excluir(id) {
        Modal.confirm('Tem certeza que deseja excluir este equipamento?', async () => {
            await Database.deleteEquipamento(id);
            Toast.success('Equipamento excluído');
            await this.refresh();
        });
    },

    async refresh() {
        this.data.equipamentos = await Database.getEquipamentos();
        document.getElementById('lista-equipamentos').innerHTML = this.renderLista();
    }
};
