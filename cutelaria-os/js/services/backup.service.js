import { db } from '../database/db.js';
import { showToast } from '../components/toast.js';

const TABELAS = ['materiais', 'financeiro', 'producao', 'pedidos', 'settings'];

// ============================================
// EXPORTAR
// ============================================

export async function exportarBackup() {
  try {
    const backup = {
      version:    3,
      app:        'cutelaria-os',
      exportedAt: new Date().toISOString(),
    };

    for (const tabela of TABELAS) {
      backup[tabela] = db[tabela] ? await db[tabela].toArray() : [];
    }

    const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href     = url;
    link.download = `cutelaria-backup-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showToast({ message: 'Backup exportado!' });
    return true;
  } catch (err) {
    console.error('Erro exportando backup:', err);
    showToast({ type: 'error', message: 'Erro ao exportar backup.' });
    return false;
  }
}

// ============================================
// IMPORTAR
// ============================================

export async function importarBackup(file) {
  try {
    // 1. Ler e parsear
    const text = await file.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      showToast({ type: 'error', message: 'Arquivo inválido — não é um JSON válido.' });
      return false;
    }

    // 2. Validar que é um backup do Cutelaria OS
    if (data.app !== 'cutelaria-os') {
      showToast({ type: 'error', message: 'Arquivo não reconhecido. Use um backup gerado pelo Cutelaria OS.' });
      return false;
    }

    // 3. Contar registros para mostrar no confirm
    const totais = TABELAS.map(t => {
      const n = data[t]?.length || 0;
      return n > 0 ? `${n} ${t}` : null;
    }).filter(Boolean).join(', ');

    const dataExport = data.exportedAt
      ? new Date(data.exportedAt).toLocaleString('pt-BR')
      : 'data desconhecida';

    // 4. Confirmar antes de apagar tudo
    const ok = confirm(
      `Restaurar backup de ${dataExport}?\n\n` +
      `Isso vai substituir todos os dados atuais por:\n${totais || 'nenhum registro'}\n\n` +
      `Esta ação não pode ser desfeita.`
    );

    if (!ok) return false;

    // 5. Restaurar tabela por tabela
    for (const tabela of TABELAS) {
      if (!db[tabela]) continue;
      await db[tabela].clear();
      if (data[tabela]?.length) {
        // Remove o campo id para deixar o Dexie gerar novos ids
        // e evitar conflitos caso o banco já tenha registros
        const registros = data[tabela].map(r => {
          const { id, ...resto } = r;
          return resto;
        });
        await db[tabela].bulkAdd(registros);
      }
    }

    showToast({ message: 'Backup restaurado! Recarregando...' });
    setTimeout(() => window.location.reload(), 800);
    return true;

  } catch (err) {
    console.error('Erro importando backup:', err);
    showToast({ type: 'error', message: 'Falha ao restaurar. Arquivo pode estar corrompido.' });
    return false;
  }
}
