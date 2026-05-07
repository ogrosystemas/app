// ============================================
// CUTELARIA CUSTO - EXPORT-IMPORT.JS
// Backup/restore JSON
// ============================================

const BackupService = {
    // Exportar dados para arquivo JSON
    async exportar() {
        const dados = await Database.exportAll();
        const blob = new Blob([JSON.stringify(dados, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `cutelaria-backup-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        return true;
    },

    // Importar dados de arquivo JSON
    async importar(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = async (e) => {
                try {
                    const dados = JSON.parse(e.target.result);

                    // Validar estrutura
                    if (!dados.versao) {
                        throw new Error('Arquivo de backup inválido');
                    }

                    await Database.importAll(dados);
                    resolve(true);
                } catch (err) {
                    reject(err);
                }
            };

            reader.onerror = () => reject(new Error('Erro ao ler arquivo'));
            reader.readAsText(file);
        });
    },

    // Compartilhar via Web Share API (mobile)
    async compartilhar() {
        const dados = await Database.exportAll();
        const blob = new Blob([JSON.stringify(dados, null, 2)], { type: 'application/json' });
        const file = new File([blob], `cutelaria-backup-${new Date().toISOString().split('T')[0]}.json`, { type: 'application/json' });

        if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                title: 'Backup Cutelaria Custo',
                files: [file]
            });
            return true;
        }
        return false;
    },

    // Exportar faca específica como PDF/JSON
    async exportarFaca(facaId) {
        const faca = await Database.getFacaById(facaId);
        if (!faca) return false;

        const blob = new Blob([JSON.stringify(faca, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `faca-${faca.nome.replace(/\s+/g, '_').toLowerCase()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        return true;
    }
};
