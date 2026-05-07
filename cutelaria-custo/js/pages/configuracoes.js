// ============================================
// CUTELARIA CUSTO - CONFIGURACOES.JS
// Configurações e backup
// ============================================

const ConfiguracoesPage = {
    data: {
        config: null
    },

    async render() {
        this.data.config = await Database.getConfiguracoes();

        const moedaOptions = Object.entries(MOEDAS).map(([code, m]) => 
            `<option value="${code}" ${this.data.config.moeda === code ? 'selected' : ''}>${m.symbol} ${m.label}</option>`
        ).join('');

        return `
            <div class="config-section">
                <h3>⚙️ Configurações Gerais</h3>

                <div class="card">
                    ${UI.formGroup('Valor Hora de Trabalho', `<input type="number" id="cfg-hora" step="0.01" min="0" value="${this.data.config.horaTrabalho || 50}">`)}
                    ${UI.formGroup('Preço kWh (R$)', `<input type="number" id="cfg-kwh" step="0.01" min="0" value="${this.data.config.precoKwh || 0.75}">`)}
                    ${UI.formGroup('Moeda', `<select id="cfg-moeda">${moedaOptions}</select>`)}
                </div>
            </div>

            <div class="config-section">
                <h3>📊 Padrões</h3>

                <div class="card">
                    ${UI.formGroup('% Perda Padrão', `<input type="number" id="cfg-perda" step="1" min="0" max="100" value="${this.data.config.perdaPadrao || 10}">`)}
                    ${UI.formGroup('% Margem Padrão', `<input type="number" id="cfg-margem" step="1" min="0" value="${this.data.config.margemPadrao || 50}">`)}
                </div>
            </div>

            <div class="config-section">
                <h3>💾 Backup</h3>

                <div class="card">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button class="btn btn-secondary" onclick="ConfiguracoesPage.exportar()">
                            📤 Exportar Backup (JSON)
                        </button>
                        <button class="btn btn-secondary" onclick="ConfiguracoesPage.compartilhar()">
                            📲 Compartilhar Backup
                        </button>
                        <div style="position: relative;">
                            <input type="file" id="cfg-import-file" accept=".json" hidden onchange="ConfiguracoesPage.importar(this)">
                            <button class="btn btn-secondary" onclick="document.getElementById('cfg-import-file').click()">
                                📥 Importar Backup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="config-section zona-perigo">
                <h3>🗑️ Zona de Perigo</h3>
                <button class="btn btn-danger" style="width: 100%;" onclick="ConfiguracoesPage.limparTudo()">
                    ⚠️ Apagar Todos os Dados
                </button>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary btn-lg" onclick="ConfiguracoesPage.salvar()">
                    💾 Salvar Configurações
                </button>
            </div>
        `;
    },

    init() {
        // Nada especial
    },

    async salvar() {
        const config = {
            horaTrabalho: parseFloat(document.getElementById('cfg-hora').value) || 50,
            precoKwh: parseFloat(document.getElementById('cfg-kwh').value) || 0.75,
            moeda: document.getElementById('cfg-moeda').value || 'BRL',
            perdaPadrao: parseFloat(document.getElementById('cfg-perda').value) || 10,
            margemPadrao: parseFloat(document.getElementById('cfg-margem').value) || 50
        };

        try {
            await Database.updateConfiguracoes(config);
            Formatters.setConfig(config);
            Toast.success('Configurações salvas!');
        } catch (err) {
            Toast.error('Erro ao salvar: ' + err.message);
        }
    },

    async exportar() {
        try {
            await BackupService.exportar();
        } catch (err) {
            Toast.error('Erro ao exportar: ' + err.message);
        }
    },

    async compartilhar() {
        try {
            const ok = await BackupService.compartilhar();
            if (!ok) {
                Toast.warning('Compartilhamento não suportado neste dispositivo');
                await this.exportar();
            }
        } catch (err) {
            Toast.error('Erro: ' + err.message);
        }
    },

    async importar(input) {
        const file = input.files[0];
        if (!file) return;

        try {
            await BackupService.importar(file);
            Toast.success('Dados importados!');
            Router.navigate('dashboard');
        } catch (err) {
            Toast.error('Erro ao importar: ' + err.message);
        }

        input.value = '';
    },

    limparTudo() {
        Modal.confirm(
            '⚠️ ATENÇÃO! Isso apagará TODOS os dados permanentemente. Esta ação não pode ser desfeita.',
            async () => {
                await Database.clearAll();
                Toast.success('Todos os dados foram apagados');
                Router.navigate('dashboard');
            }
        );
    }
};
