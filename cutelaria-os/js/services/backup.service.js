import { db } from '../database/db.js';
import { showToast } from '../components/toast.js';

// ============================================
// EXPORTAR
// ============================================

export async function exportarBackup() {
  try {
    const backup = {
      version:    2,
      exportedAt: new Date().toISOString(),
      materiais:  db.materiais  ? await db.materiais.toArray()  : [],
      financeiro: db.financeiro ? await db.financeiro.toArray() : [],
      producao:   db.producao   ? await db.producao.toArray()   : [],
      pedidos:    db.pedidos    ? await db.pedidos.toArray()     : [],
      settings:   db.settings   ? await db.settings.toArray()   : [],
    };

    const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href     = url;
    link.download = `cutelaria-backup-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    showToast({ message: 'Backup exportado com sucesso!' });
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
    const text = await file.text();
    const data = JSON.parse(text);

    const tabelas = ['materiais', 'financeiro', 'producao', 'pedidos', 'settings'];

    for (const tabela of tabelas) {
      if (db[tabela] && data[tabela]?.length) {
        await db[tabela].clear();
        await db[tabela].bulkAdd(data[tabela]);
      }
    }

    showToast({ message: 'Backup restaurado! Recarregando...' });
    setTimeout(() => window.location.reload(), 800);
    return true;
  } catch (err) {
    console.error('Erro importando backup:', err);
    showToast({ type: 'error', message: 'Arquivo inválido ou corrompido.' });
    return false;
  }
}
