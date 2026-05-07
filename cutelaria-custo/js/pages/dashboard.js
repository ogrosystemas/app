// ============================================
// CUTELARIA CUSTO - DASHBOARD.JS
// Dashboard com estatísticas e gráficos
// ============================================

const DashboardPage = {
    data: {
        facas: [],
        materiais: [],
        insumos: [],
        equipamentos: [],
        stats: null
    },

    async render() {
        this.data.facas = await Database.getFacas();
        this.data.materiais = await Database.getMateriais();
        this.data.insumos = await Database.getInsumos();
        this.data.equipamentos = await Database.getEquipamentos();
        this.data.stats = Calculations.estatisticasDashboard(this.data.facas);

        const { stats } = this.data;

        return `
            <div class="dashboard-stats">
                ${UI.statCard('🔪', stats.totalFacas, 'Facas Feitas')}
                ${UI.statCard('💰', Formatters.currency(stats.custoMedio), 'Custo Médio')}
                ${UI.statCard('📈', Formatters.currency(stats.vendaMedia), 'Preço Médio')}
            </div>

            <div class="dashboard-section">
                <h2>📊 Desempenho</h2>
                <div class="chart-container">
                    <canvas id="chart-desempenho"></canvas>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>🔪 Últimas Facas</h2>
                <div id="ultimas-facas">
                    ${this.renderUltimasFacas()}
                </div>
            </div>

            <div class="dashboard-section">
                <h2>📦 Estoque</h2>
                <div class="card">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: var(--accent);">${this.data.materiais.length}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">Materiais</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: var(--success);">${this.data.insumos.length}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">Insumos</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: var(--warning);">${this.data.equipamentos.length}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">Equipamentos</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: var(--info);">${Formatters.currency(sumBy(this.data.insumos, 'preco'))}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">Valor Estoque</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>📈 Lucro Total</h2>
                <div class="card" style="border-color: var(--success);">
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 36px; font-weight: 700; color: var(--success);">
                            ${Formatters.currency(stats.lucroTotal)}
                        </div>
                        <div style="font-size: 14px; color: var(--text-secondary); margin-top: 8px;">
                            ${stats.totalFacas} facas produzidas
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderUltimasFacas() {
        const ultimas = [...this.data.facas].slice(-5).reverse();

        if (ultimas.length === 0) {
            return UI.emptyState('Nenhuma faca calculada ainda. Crie sua primeira faca!', '🔪');
        }

        return ultimas.map(f => UI.facaCard(f)).join('');
    },

    init() {
        this.initCharts();
    },

    initCharts() {
        const ctx = document.getElementById('chart-desempenho');
        if (!ctx || this.data.facas.length === 0) return;

        const facas = [...this.data.facas].slice(-10);
        const labels = facas.map(f => f.nome.substring(0, 15));
        const custos = facas.map(f => f.custoTotal);
        const vendas = facas.map(f => f.precoVenda);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Custo',
                        data: custos,
                        backgroundColor: 'rgba(233, 69, 96, 0.6)',
                        borderColor: 'rgba(233, 69, 96, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Venda',
                        data: vendas,
                        backgroundColor: 'rgba(0, 217, 165, 0.6)',
                        borderColor: 'rgba(0, 217, 165, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#f0f0f5' }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: '#8b8b9e' },
                        grid: { color: '#2a2a40' }
                    },
                    x: {
                        ticks: { color: '#8b8b9e' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
};
