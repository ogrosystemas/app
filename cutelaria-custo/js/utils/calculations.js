// ============================================
// CUTELARIA CUSTO - CALCULATIONS.JS
// Fórmulas matemáticas de custo
// ============================================

const Calculations = {
    // Cálculo de custo unitário de insumo
    custoUnitarioInsumo(precoTotal, quantidade) {
        if (!quantidade || quantidade <= 0) return 0;
        return precoTotal / quantidade;
    },

    // Cálculo de custo de insumo usado
    custoInsumoUsado(precoTotal, quantidadeComprada, quantidadeUsada) {
        const unitario = this.custoUnitarioInsumo(precoTotal, quantidadeComprada);
        return unitario * quantidadeUsada;
    },

    // Cálculo de amortização de equipamento (custo por hora)
    custoHoraEquipamento(precoAquisicao, vidaUtilHoras) {
        if (!vidaUtilHoras || vidaUtilHoras <= 0) return 0;
        return precoAquisicao / vidaUtilHoras;
    },

    // Cálculo de custo de uso de equipamento
    custoUsoEquipamento(precoAquisicao, vidaUtilHoras, horasUsadas) {
        const custoHora = this.custoHoraEquipamento(precoAquisicao, vidaUtilHoras);
        return custoHora * horasUsadas;
    },

    // Cálculo de custo de energia
    custoEnergia(kwhConsumidos, precoKwh) {
        return (kwhConsumidos || 0) * (precoKwh || 0);
    },

    // Cálculo de mão de obra
    custoMaoObra(horasTrabalho, valorHora) {
        return (horasTrabalho || 0) * (valorHora || 0);
    },

    // Cálculo de perda/refugo
    custoPerda(subtotal, percentualPerda) {
        return subtotal * ((percentualPerda || 0) / 100);
    },

    // Cálculo de custo total
    custoTotal(custos) {
        const {
            insumos = 0,
            equipamentos = 0,
            energia = 0,
            gas = 0,
            maoObra = 0,
            perda = 0
        } = custos;

        const subtotal = insumos + equipamentos + energia + gas + maoObra;
        return subtotal + perda;
    },

    // Cálculo de preço de venda com margem
    precoVenda(custoTotal, margemPercentual) {
        if (!margemPercentual || margemPercentual < 0) return custoTotal;
        return custoTotal * (1 + margemPercentual / 100);
    },

    // Cálculo de margem real (a partir de custo e preço)
    margemReal(custoTotal, precoVenda) {
        if (!custoTotal || custoTotal <= 0) return 0;
        return ((precoVenda - custoTotal) / custoTotal) * 100;
    },

    // Cálculo de lucro bruto
    lucroBruto(custoTotal, precoVenda) {
        return precoVenda - custoTotal;
    },

    // Cálculo completo de uma faca
    calcularFaca(dados, config) {
        const {
            insumos = [],
            equipamentos = [],
            kwh = 0,
            gas = 0,
            horasTrabalho = 0,
            perda = 0,
            margem = 0
        } = dados;

        // Custo dos insumos
        const custoInsumos = insumos.reduce((total, item) => {
            return total + (item.custo || 0);
        }, 0);

        // Custo dos equipamentos
        const custoEquipamentos = equipamentos.reduce((total, item) => {
            return total + (item.custo || 0);
        }, 0);

        // Custo de energia
        const custoEnergia = this.custoEnergia(kwh, config.precoKwh);

        // Custo de gás/carvão
        const custoGas = gas || 0;

        // Custo de mão de obra
        const custoMaoObra = this.custoMaoObra(horasTrabalho, config.horaTrabalho);

        // Subtotal
        const subtotal = custoInsumos + custoEquipamentos + custoEnergia + custoGas + custoMaoObra;

        // Perda
        const custoPerda = this.custoPerda(subtotal, perda);

        // Custo total
        const custoTotal = subtotal + custoPerda;

        // Preço de venda
        const precoVenda = this.precoVenda(custoTotal, margem);

        // Lucro
        const lucro = this.lucroBruto(custoTotal, precoVenda);

        return {
            custoInsumos,
            custoEquipamentos,
            custoEnergia,
            custoGas,
            custoMaoObra,
            custoPerda,
            subtotal,
            custoTotal,
            precoVenda,
            lucro,
            margemReal: this.margemReal(custoTotal, precoVenda)
        };
    },

    // Estatísticas do dashboard
    estatisticasDashboard(facas) {
        if (!facas || facas.length === 0) {
            return {
                totalFacas: 0,
                custoTotal: 0,
                vendaTotal: 0,
                lucroTotal: 0,
                custoMedio: 0,
                vendaMedia: 0,
                lucroMedio: 0,
                margemMedia: 0
            };
        }

        const total = facas.length;
        const custoTotal = sumBy(facas, 'custoTotal');
        const vendaTotal = sumBy(facas, 'precoVenda');
        const lucroTotal = vendaTotal - custoTotal;

        return {
            totalFacas: total,
            custoTotal,
            vendaTotal,
            lucroTotal,
            custoMedio: custoTotal / total,
            vendaMedia: vendaTotal / total,
            lucroMedio: lucroTotal / total,
            margemMedia: averageBy(facas, 'margem')
        };
    },

    // Resumo por tipo de insumo
    resumoPorTipo(insumos) {
        const grouped = groupBy(insumos, 'tipo');
        return Object.entries(grouped).map(([tipo, items]) => ({
            tipo,
            label: Formatters.tipoInsumo(tipo),
            quantidade: items.length,
            valorTotal: sumBy(items, 'preco')
        }));
    }
};
