// ============================================
// CUTELARIA CUSTO - FACA.JS
// Criação e cálculo de faca
// ============================================

const FacaPage = {
    data: {
        config: null,
        materiais: [],
        insumos: [],
        equipamentos: [],
        modelos: [],
        facaInsumos: [],
        facaEquipamentos: [],
        calculo: null
    },

    async render() {
        this.data.config = await Database.getConfiguracoes();
        this.data.materiais = await Database.getMateriais();
        this.data.insumos = await Database.getInsumos();
        this.data.equipamentos = await Database.getEquipamentos();
        this.data.modelos = await Database.getModelos();

        return `
            <form id="form-faca" onsubmit="FacaPage.salvar(event)">
                <div class="form-section">
                    <div class="form-section-title">📋 Informações</div>
                    ${UI.formGroup('Nome da Faca', `<input type="text" id="faca-nome" placeholder="Ex: Faca Chef 8"" required>`)}
                    ${UI.formGroup('Cliente', `<input type="text" id="faca-cliente" placeholder="Nome do cliente (opcional)">`)}
                    ${UI.formGroup('Modelo Base', this.renderSelectModelo())}
                </div>

                <div class="form-section">
                    <div class="form-section-title">📦 Materiais</div>
                    <div id="faca-materiais" class="itens-dinamicos">
                        <p class="hint">Adicione os materiais usados nesta faca</p>
                    </div>
                    <button type="button" class="btn-adicionar" onclick="FacaPage.addMaterial()">
                        ➕ Adicionar Material
                    </button>
                </div>

                <div class="form-section">
                    <div class="form-section-title">🧪 Insumos</div>
                    <div id="faca-insumos" class="itens-dinamicos">
                        <p class="hint">Adicione os insumos usados nesta faca</p>
                    </div>
                    <button type="button" class="btn-adicionar" onclick="FacaPage.addInsumo()">
                        ➕ Adicionar Insumo
                    </button>
                </div>

                <div class="form-section">
                    <div class="form-section-title">🔧 Equipamentos</div>
                    <div id="faca-equipamentos" class="itens-dinamicos">
                        <p class="hint">Adicione o tempo de uso dos equipamentos</p>
                    </div>
                    <button type="button" class="btn-adicionar" onclick="FacaPage.addEquipamento()">
                        ➕ Adicionar Equipamento
                    </button>
                </div>

                <div class="form-section">
                    <div class="form-section-title">⚡ Custos Extras</div>
                    <div class="form-row">
                        ${UI.formGroup('Horas de Trabalho', `<input type="number" id="faca-horas" step="0.5" min="0" placeholder="0" onchange="FacaPage.calcular()">`)}
                        ${UI.formGroup('kWh Consumidos', `<input type="number" id="faca-kwh" step="0.1" min="0" placeholder="0" onchange="FacaPage.calcular()">`)}
                    </div>
                    <div class="form-row">
                        ${UI.formGroup('Gás/Carvão (R$)', `<input type="number" id="faca-gas" step="0.01" min="0" placeholder="0,00" onchange="FacaPage.calcular()">`)}
                        ${UI.formGroup('% Perda/Refugo', `<input type="number" id="faca-perda" step="1" min="0" max="100" value="${this.data.config.perdaPadrao || 10}" onchange="FacaPage.calcular()">`)}
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">💰 Margem de Lucro</div>
                    ${UI.formGroup('Margem (%)', `<input type="number" id="faca-margem" step="1" min="0" value="${this.data.config.margemPadrao || 50}" onchange="FacaPage.calcular()">`)}
                </div>

                <div id="resumo-calculo">
                    ${UI.resumoCalculo({})}
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Salvar Faca</button>
                    <button type="button" class="btn btn-secondary btn-lg" onclick="FacaPage.calcular()">
                        🧮 Recalcular
                    </button>
                </div>
            </form>
        `;
    },

    renderSelectModelo() {
        if (this.data.modelos.length === 0) {
            return `<select id="faca-modelo"><option value="">Nenhum modelo cadastrado</option></select>`;
        }
        const options = this.data.modelos.map(m => `<option value="${m.id}">${m.nome}</option>`).join('');
        return `<select id="faca-modelo" onchange="FacaPage.aplicarModelo()"><option value="">-- Sem modelo --</option>${options}</select>`;
    },

    init() {
        this.data.facaInsumos = [];
        this.data.facaEquipamentos = [];
        this.data.facaMateriais = [];
    },

    addMaterial() {
        if (this.data.materiais.length === 0) {
            Toast.warning('Cadastre materiais primeiro!');
            return;
        }

        const id = generateId();
        this.data.facaMateriais.push(id);

        const container = document.getElementById('faca-materiais');
        if (this.data.facaMateriais.length === 1) container.innerHTML = '';

        container.insertAdjacentHTML('beforeend', `
            <div class="item-dinamico" data-id="${id}">
                <div class="item-dinamico-header">
                    <strong>📦 Material</strong>
                    <button type="button" class="btn-remover" onclick="FacaPage.removerMaterial('${id}')">&times;</button>
                </div>
                <div class="item-dinamimo-row">
                    ${UI.formGroup('', UI.selectMaterial('mat-sel-' + id, this.data.materiais))}
                    ${UI.formGroup('', `<input type="number" id="mat-qtd-${id}" step="0.01" min="0" placeholder="Qtd" onchange="FacaPage.calcular()">`)}
                </div>
            </div>
        `);
    },

    addInsumo() {
        if (this.data.insumos.length === 0) {
            Toast.warning('Cadastre insumos primeiro!');
            return;
        }

        const id = generateId();
        this.data.facaInsumos.push(id);

        const container = document.getElementById('faca-insumos');
        if (this.data.facaInsumos.length === 1) container.innerHTML = '';

        container.insertAdjacentHTML('beforeend', `
            <div class="item-dinamico" data-id="${id}">
                <div class="item-dinamico-header">
                    <strong>🧪 Insumo</strong>
                    <button type="button" class="btn-remover" onclick="FacaPage.removerInsumo('${id}')">&times;</button>
                </div>
                <div class="item-dinamimo-row">
                    ${UI.formGroup('', UI.selectInsumo('ins-sel-' + id, this.data.insumos))}
                    ${UI.formGroup('', `<input type="number" id="ins-qtd-${id}" step="0.01" min="0" placeholder="Qtd" onchange="FacaPage.calcular()">`)}
                </div>
            </div>
        `);
    },

    addEquipamento() {
        if (this.data.equipamentos.length === 0) {
            Toast.warning('Cadastre equipamentos primeiro!');
            return;
        }

        const id = generateId();
        this.data.facaEquipamentos.push(id);

        const container = document.getElementById('faca-equipamentos');
        if (this.data.facaEquipamentos.length === 1) container.innerHTML = '';

        container.insertAdjacentHTML('beforeend', `
            <div class="item-dinamico" data-id="${id}">
                <div class="item-dinamico-header">
                    <strong>🔧 Equipamento</strong>
                    <button type="button" class="btn-remover" onclick="FacaPage.removerEquipamento('${id}')">&times;</button>
                </div>
                <div class="item-dinamimo-row">
                    ${UI.formGroup('', UI.selectEquipamento('eq-sel-' + id, this.data.equipamentos))}
                    ${UI.formGroup('', `<input type="number" id="eq-horas-${id}" step="0.5" min="0" placeholder="Horas" onchange="FacaPage.calcular()">`)}
                </div>
            </div>
        `);
    },

    removerMaterial(id) {
        const el = document.querySelector(`[data-id="${id}"]`);
        if (el) el.remove();
        this.data.facaMateriais = this.data.facaMateriais.filter(x => x !== id);
        if (this.data.facaMateriais.length === 0) {
            document.getElementById('faca-materiais').innerHTML = '<p class="hint">Adicione os materiais usados nesta faca</p>';
        }
        this.calcular();
    },

    removerInsumo(id) {
        const el = document.querySelector(`[data-id="${id}"]`);
        if (el) el.remove();
        this.data.facaInsumos = this.data.facaInsumos.filter(x => x !== id);
        if (this.data.facaInsumos.length === 0) {
            document.getElementById('faca-insumos').innerHTML = '<p class="hint">Adicione os insumos usados nesta faca</p>';
        }
        this.calcular();
    },

    removerEquipamento(id) {
        const el = document.querySelector(`[data-id="${id}"]`);
        if (el) el.remove();
        this.data.facaEquipamentos = this.data.facaEquipamentos.filter(x => x !== id);
        if (this.data.facaEquipamentos.length === 0) {
            document.getElementById('faca-equipamentos').innerHTML = '<p class="hint">Adicione o tempo de uso dos equipamentos</p>';
        }
        this.calcular();
    },

    calcular() {
        const materiais = [];
        this.data.facaMateriais.forEach(id => {
            const matId = parseInt(document.getElementById('mat-sel-' + id)?.value);
            const qtd = parseFloat(document.getElementById('mat-qtd-' + id)?.value) || 0;
            const mat = this.data.materiais.find(m => m.id === matId);
            if (mat && qtd > 0) {
                materiais.push({
                    id: matId,
                    nome: mat.nome,
                    quantidade: qtd,
                    unidade: mat.unidade,
                    custo: Calculations.custoInsumoUsado(mat.preco, mat.quantidade, qtd)
                });
            }
        });

        const insumos = [];
        this.data.facaInsumos.forEach(id => {
            const insId = parseInt(document.getElementById('ins-sel-' + id)?.value);
            const qtd = parseFloat(document.getElementById('ins-qtd-' + id)?.value) || 0;
            const ins = this.data.insumos.find(i => i.id === insId);
            if (ins && qtd > 0) {
                insumos.push({
                    id: insId,
                    nome: ins.nome,
                    quantidade: qtd,
                    unidade: ins.unidade,
                    custo: Calculations.custoInsumoUsado(ins.preco, ins.quantidade, qtd)
                });
            }
        });

        const equipamentos = [];
        this.data.facaEquipamentos.forEach(id => {
            const eqId = parseInt(document.getElementById('eq-sel-' + id)?.value);
            const horas = parseFloat(document.getElementById('eq-horas-' + id)?.value) || 0;
            const eq = this.data.equipamentos.find(e => e.id === eqId);
            if (eq && horas > 0) {
                equipamentos.push({
                    id: eqId,
                    nome: eq.nome,
                    horas: horas,
                    custo: Calculations.custoUsoEquipamento(eq.preco, eq.vidaUtil, horas)
                });
            }
        });

        const dados = {
            insumos: [...materiais, ...insumos],
            equipamentos,
            kwh: parseFloat(document.getElementById('faca-kwh')?.value) || 0,
            gas: parseFloat(document.getElementById('faca-gas')?.value) || 0,
            horasTrabalho: parseFloat(document.getElementById('faca-horas')?.value) || 0,
            perda: parseFloat(document.getElementById('faca-perda')?.value) || 0,
            margem: parseFloat(document.getElementById('faca-margem')?.value) || 0
        };

        this.data.calculo = Calculations.calcularFaca(dados, this.data.config);
        document.getElementById('resumo-calculo').innerHTML = UI.resumoCalculo(this.data.calculo);

        return this.data.calculo;
    },

    async aplicarModelo() {
        const modeloId = parseInt(document.getElementById('faca-modelo')?.value);
        if (!modeloId) return;

        const modelo = this.data.modelos.find(m => m.id === modeloId);
        if (!modelo || !modelo.materiais) return;

        // Limpar e aplicar
        this.data.facaMateriais = [];
        document.getElementById('faca-materiais').innerHTML = '';

        modelo.materiais.forEach(mi => this.addMaterial());
        // Preencher valores do modelo... (simplificado)

        Toast.success('Modelo aplicado!');
        this.calcular();
    },

    async salvar(e) {
        e.preventDefault();

        const nome = document.getElementById('faca-nome').value;
        if (!nome) {
            Toast.error('Informe o nome da faca');
            return;
        }

        const calculo = this.calcular();
        if (!calculo || calculo.custoTotal <= 0) {
            Toast.warning('Adicione pelo menos um material ou insumo');
            return;
        }

        const faca = {
            nome,
            cliente: document.getElementById('faca-cliente').value || null,
            modeloId: parseInt(document.getElementById('faca-modelo')?.value) || null,
            data: new Date().toISOString().split('T')[0],
            materiais: this.data.facaMateriais.map(id => {
                const matId = parseInt(document.getElementById('mat-sel-' + id)?.value);
                const qtd = parseFloat(document.getElementById('mat-qtd-' + id)?.value) || 0;
                const mat = this.data.materiais.find(m => m.id === matId);
                return mat ? { id: matId, nome: mat.nome, quantidade: qtd, unidade: mat.unidade, custo: Calculations.custoInsumoUsado(mat.preco, mat.quantidade, qtd) } : null;
            }).filter(Boolean),
            insumos: this.data.facaInsumos.map(id => {
                const insId = parseInt(document.getElementById('ins-sel-' + id)?.value);
                const qtd = parseFloat(document.getElementById('ins-qtd-' + id)?.value) || 0;
                const ins = this.data.insumos.find(i => i.id === insId);
                return ins ? { id: insId, nome: ins.nome, quantidade: qtd, unidade: ins.unidade, custo: Calculations.custoInsumoUsado(ins.preco, ins.quantidade, qtd) } : null;
            }).filter(Boolean),
            equipamentos: this.data.facaEquipamentos.map(id => {
                const eqId = parseInt(document.getElementById('eq-sel-' + id)?.value);
                const horas = parseFloat(document.getElementById('eq-horas-' + id)?.value) || 0;
                const eq = this.data.equipamentos.find(e => e.id === eqId);
                return eq ? { id: eqId, nome: eq.nome, horas, custo: Calculations.custoUsoEquipamento(eq.preco, eq.vidaUtil, horas) } : null;
            }).filter(Boolean),
            horasTrabalho: parseFloat(document.getElementById('faca-horas')?.value) || 0,
            kwh: parseFloat(document.getElementById('faca-kwh')?.value) || 0,
            gas: parseFloat(document.getElementById('faca-gas')?.value) || 0,
            perda: parseFloat(document.getElementById('faca-perda')?.value) || 0,
            margem: parseFloat(document.getElementById('faca-margem')?.value) || 0,
            ...calculo
        };

        try {
            await Database.addFaca(faca);
            Toast.success(`Faca salva! Preço: ${Formatters.currency(calculo.precoVenda)}`);
            Router.navigate('dashboard');
        } catch (err) {
            Toast.error('Erro ao salvar: ' + err.message);
        }
    }
};

// Helper para select de materiais
UI.selectMaterial = function(id, materiais, selected = '') {
    if (materiais.length === 0) {
        return `<select id="${id}"><option value="">Nenhum material</option></select>`;
    }
    const options = materiais.map(m => {
        const unitario = Calculations.custoUnitarioInsumo(m.preco, m.quantidade);
        return `<option value="${m.id}" ${m.id === selected ? 'selected' : ''}>${m.nome} (${Formatters.currency(unitario)}/${m.unidade})</option>`;
    }).join('');
    return `<select id="${id}"><option value="">Selecione...</option>${options}</select>`;
};
