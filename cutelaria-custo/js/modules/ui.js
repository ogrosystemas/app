// ============================================
// CUTELARIA CUSTO - UI.JS
// Renderização DOM e componentes reutilizáveis
// ============================================

const UI = {
    // Renderizar lista de cards vazia
    emptyState(message = 'Nenhum item encontrado', icon = '📭') {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">${icon}</div>
                <p>${message}</p>
            </div>
        `;
    },

    // Card de item (material, insumo, equipamento)
    itemCard(item, options = {}) {
        const { onEdit, onDelete, extraInfo = [], badges = [] } = options;

        const badgeHtml = badges.map(b => 
            `<span class="badge ${b.class}">${b.text}</span>`
        ).join('');

        const infoHtml = extraInfo.map(info => 
            `<span>${info.icon || ''} ${info.text}</span>`
        ).join('');

        return `
            <div class="item-card" data-id="${item.id}">
                <div class="item-card-header">
                    <div>
                        <div class="card-title">${item.nome}</div>
                        <div class="card-subtitle">
                            ${badgeHtml}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 16px; font-weight: 700; color: var(--success);">
                            ${Formatters.currency(item.preco)}
                        </div>
                    </div>
                </div>
                <div class="item-card-info">
                    ${infoHtml}
                </div>
                ${(onEdit || onDelete) ? `
                <div class="item-card-actions">
                    ${onEdit ? `<button class="btn btn-secondary btn-sm" onclick="${onEdit}(${item.id})">✏️ Editar</button>` : ''}
                    ${onDelete ? `<button class="btn btn-danger btn-sm" onclick="${onDelete}(${item.id})">🗑️ Excluir</button>` : ''}
                </div>
                ` : ''}
            </div>
        `;
    },

    // Card de faca (histórico/dashboard)
    facaCard(faca) {
        return `
            <div class="faca-card" onclick="Router.navigate('historico', {view: ${faca.id}})">
                <div class="faca-card-header">
                    <div>
                        <div class="faca-card-title">${faca.nome}</div>
                        <div class="faca-card-date">${Formatters.relativeDate(faca.data)}</div>
                    </div>
                </div>
                ${faca.cliente ? `<div class="faca-card-cliente">👤 ${faca.cliente}</div>` : ''}
                <div class="faca-card-footer">
                    <span class="faca-card-custo">Custo: ${Formatters.currency(faca.custoTotal)}</span>
                    <span class="faca-card-venda">${Formatters.currency(faca.precoVenda)}</span>
                </div>
            </div>
        `;
    },

    // Stat card
    statCard(icon, value, label) {
        return `
            <div class="stat-card">
                <div class="stat-icon">${icon}</div>
                <span class="stat-value">${value}</span>
                <span class="stat-label">${label}</span>
            </div>
        `;
    },

    // Form group
    formGroup(label, inputHtml) {
        return `
            <div class="form-group">
                <label>${label}</label>
                ${inputHtml}
            </div>
        `;
    },

    // Select de tipos de insumo
    selectTipoInsumo(id, selected = '') {
        const options = Object.entries(TIPOS_INSUMO).map(([key, val]) => 
            `<option value="${key}" ${key === selected ? 'selected' : ''}>${val.icon} ${val.label}</option>`
        ).join('');

        return `<select id="${id}">${options}</select>`;
    },

    // Select de unidades
    selectUnidade(id, selected = 'kg') {
        const options = UNIDADES.map(u => 
            `<option value="${u.value}" ${u.value === selected ? 'selected' : ''}>${u.label}</option>`
        ).join('');

        return `<select id="${id}">${options}</select>`;
    },

    // Select de equipamentos
    selectEquipamento(id, equipamentos, selected = '') {
        if (equipamentos.length === 0) {
            return `<select id="${id}"><option value="">Nenhum equipamento</option></select>`;
        }

        const options = equipamentos.map(eq => {
            const custoHora = Calculations.custoHoraEquipamento(eq.preco, eq.vidaUtil);
            return `<option value="${eq.id}" ${eq.id === selected ? 'selected' : ''}>${eq.nome} (${Formatters.currency(custoHora)}/h)</option>`;
        }).join('');

        return `<select id="${id}"><option value="">Selecione...</option>${options}</select>`;
    },

    // Select de insumos
    selectInsumo(id, insumos, selected = '') {
        if (insumos.length === 0) {
            return `<select id="${id}"><option value="">Nenhum insumo</option></select>`;
        }

        const options = insumos.map(ins => {
            const unitario = Calculations.custoUnitarioInsumo(ins.preco, ins.quantidade);
            return `<option value="${ins.id}" ${ins.id === selected ? 'selected' : ''}>${ins.nome} (${Formatters.currency(unitario)}/${ins.unidade})</option>`;
        }).join('');

        return `<select id="${id}"><option value="">Selecione...</option>${options}</select>`;
    },

    // Item dinâmico (para formulários com múltiplos itens)
    itemDinamico(id, title, content, onRemove) {
        return `
            <div class="item-dinamico" data-id="${id}">
                <div class="item-dinamico-header">
                    <strong>${title}</strong>
                    <button type="button" class="btn-remover" onclick="${onRemove}('${id}')">&times;</button>
                </div>
                ${content}
            </div>
        `;
    },

    // Resumo de cálculo
    resumoCalculo(calculos) {
        const {
            custoInsumos = 0,
            custoEquipamentos = 0,
            custoEnergia = 0,
            custoGas = 0,
            custoMaoObra = 0,
            custoPerda = 0,
            custoTotal = 0,
            precoVenda = 0
        } = calculos;

        return `
            <div class="resumo-calc">
                <div class="resumo-linha">
                    <span>Custo Insumos:</span>
                    <span>${Formatters.currency(custoInsumos)}</span>
                </div>
                <div class="resumo-linha">
                    <span>Custo Equipamentos:</span>
                    <span>${Formatters.currency(custoEquipamentos)}</span>
                </div>
                <div class="resumo-linha">
                    <span>Custo Energia:</span>
                    <span>${Formatters.currency(custoEnergia)}</span>
                </div>
                <div class="resumo-linha">
                    <span>Custo Gás/Carvão:</span>
                    <span>${Formatters.currency(custoGas)}</span>
                </div>
                <div class="resumo-linha">
                    <span>Mão de Obra:</span>
                    <span>${Formatters.currency(custoMaoObra)}</span>
                </div>
                <div class="resumo-linha">
                    <span>Perda/Refugo:</span>
                    <span>${Formatters.currency(custoPerda)}</span>
                </div>
                <div class="resumo-linha destaque">
                    <span><strong>CUSTO TOTAL:</strong></span>
                    <span><strong>${Formatters.currency(custoTotal)}</strong></span>
                </div>
                <div class="resumo-linha destaque lucro">
                    <span><strong>PREÇO DE VENDA:</strong></span>
                    <span class="valor"><strong>${Formatters.currency(precoVenda)}</strong></span>
                </div>
            </div>
        `;
    }
};
