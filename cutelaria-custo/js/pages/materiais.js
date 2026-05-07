// ============================================
// CUTELARIA CUSTO - MATERIAIS.JS
// Cadastro de materiais (aços, cabos, etc)
// ============================================

const MateriaisPage = {
    data: {
        materiais: [],
        editando: null
    },

    async render() {
        this.data.materiais = await Database.getMateriais();

        return `
            <div class="page-actions">
                <button class="btn btn-primary" onclick="MateriaisPage.abrirModal()">
                    ➕ Novo Material
                </button>
            </div>
            <div class="lista-cards" id="lista-materiais">
                ${this.renderLista()}
            </div>
        `;
    },

    renderLista() {
        if (this.data.materiais.length === 0) {
            return UI.emptyState('Nenhum material cadastrado. Adicione aços, cabos, lixas...', '📦');
        }

        return this.data.materiais.map(m => UI.itemCard(m, {
            badges: [{ class: Formatters.badgeClass(m.tipo), text: Formatters.tipoInsumo(m.tipo) }],
            extraInfo: [
                { text: `${m.quantidade} ${m.unidade}` },
                { text: Formatters.date(m.dataCompra) },
                m.fornecedor ? { text: m.fornecedor } : null
            ].filter(Boolean),
            onEdit: 'MateriaisPage.editar',
            onDelete: 'MateriaisPage.excluir'
        })).join('');
    },

    init() {
        // Nada especial para inicializar
    },

    abrirModal(material = null) {
        this.data.editando = material ? material.id : null;
        const isEdit = !!material;

        const content = `
            <form id="form-material" onsubmit="MateriaisPage.salvar(event)">
                ${UI.formGroup('Nome', `<input type="text" id="mat-nome" placeholder="Ex: Aço 1070" value="${isEdit ? material.nome : ''}" required>`)}

                <div class="form-row">
                    ${UI.formGroup('Tipo', UI.selectTipoInsumo('mat-tipo', isEdit ? material.tipo : ''))}
                    ${UI.formGroup('Unidade', UI.selectUnidade('mat-unidade', isEdit ? material.unidade : 'kg'))}
                </div>

                <div class="form-row">
                    ${UI.formGroup('Quantidade', `<input type="number" id="mat-quantidade" step="0.01" min="0" placeholder="0" value="${isEdit ? material.quantidade : ''}" required>`)}
                    ${UI.formGroup('Preço Total (R$)', `<input type="number" id="mat-preco" step="0.01" min="0" placeholder="0,00" value="${isEdit ? material.preco : ''}" required>`)}
                </div>

                <div class="form-row">
                    ${UI.formGroup('Data Compra', `<input type="date" id="mat-data" value="${isEdit ? material.dataCompra : new Date().toISOString().split('T')[0]}" required>`)}
                    ${UI.formGroup('Fornecedor', `<input type="text" id="mat-fornecedor" placeholder="Nome do fornecedor" value="${isEdit ? (material.fornecedor || '') : ''}">`)}
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">💾 ${isEdit ? 'Atualizar' : 'Salvar'}</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
                </div>
            </form>
        `;

        Modal.open(content, { 
            title: isEdit ? '✏️ Editar Material' : '📦 Novo Material',
            onClose: () => { this.data.editando = null; }
        });
    },

    async salvar(e) {
        e.preventDefault();

        const material = {
            nome: document.getElementById('mat-nome').value,
            tipo: document.getElementById('mat-tipo').value,
            quantidade: parseFloat(document.getElementById('mat-quantidade').value),
            unidade: document.getElementById('mat-unidade').value,
            preco: parseFloat(document.getElementById('mat-preco').value),
            dataCompra: document.getElementById('mat-data').value,
            fornecedor: document.getElementById('mat-fornecedor').value || null
        };

        try {
            if (this.data.editando) {
                await Database.updateMaterial(this.data.editando, material);
                Toast.success('Material atualizado!');
            } else {
                await Database.addMaterial(material);
                Toast.success('Material cadastrado!');
            }

            Modal.close();
            this.data.editando = null;
            await this.refresh();
        } catch (err) {
            Toast.error('Erro ao salvar: ' + err.message);
        }
    },

    async editar(id) {
        const material = await Database.getMaterialById(id);
        if (material) this.abrirModal(material);
    },

    async excluir(id) {
        Modal.confirm('Tem certeza que deseja excluir este material?', async () => {
            await Database.deleteMaterial(id);
            Toast.success('Material excluído');
            await this.refresh();
        });
    },

    async refresh() {
        this.data.materiais = await Database.getMateriais();
        document.getElementById('lista-materiais').innerHTML = this.renderLista();
    }
};
