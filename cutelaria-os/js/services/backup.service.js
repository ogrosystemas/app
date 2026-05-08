import { db } from '../database/db.js';

// =========================
// EXPORTAR BACKUP
// =========================

async function exportarBackupInterno() {

  try {

    const backupData = {

      materiais:
        db.materiais
          ? await db.materiais.toArray()
          : [],

      composicoes:
        db.composicoes
          ? await db.composicoes.toArray()
          : [],

      clientes:
        db.clientes
          ? await db.clientes.toArray()
          : [],

      financeiro:
        db.financeiro
          ? await db.financeiro.toArray()
          : [],

      producao:
        db.producao
          ? await db.producao.toArray()
          : [],

      pedidos:
        db.pedidos
          ? await db.pedidos.toArray()
          : [],

      estoque:
        db.estoque
          ? await db.estoque.toArray()
          : [],

      equipamentos:
        db.equipamentos
          ? await db.equipamentos.toArray()
          : [],

      settings:
        db.settings
          ? await db.settings.toArray()
          : [],

      exportedAt:
        new Date().toISOString()

    };

    const json =
      JSON.stringify(
        backupData,
        null,
        2
      );

    const blob =
      new Blob(
        [json],
        {
          type:
            'application/json'
        }
      );

    const url =
      URL.createObjectURL(
        blob
      );

    const a =
      document.createElement(
        'a'
      );

    const date =
      new Date()
        .toISOString()
        .split('T')[0];

    a.href = url;

    a.download =
      `cutelaria-backup-${date}.json`;

    document.body.appendChild(
      a
    );

    a.click();

    document.body.removeChild(
      a
    );

    URL.revokeObjectURL(
      url
    );

    return true;

  } catch (error) {

    console.error(
      'Erro exportando backup:',
      error
    );

    alert(
      'Erro ao exportar backup.'
    );

    return false;

  }

}

// =========================
// IMPORTAR BACKUP
// =========================

async function importarBackupInterno(
  file
) {

  try {

    const text =
      await file.text();

    const data =
      JSON.parse(text);

    // LIMPA

    if (db.materiais)
      await db.materiais.clear();

    if (db.composicoes)
      await db.composicoes.clear();

    if (db.clientes)
      await db.clientes.clear();

    if (db.financeiro)
      await db.financeiro.clear();

    if (db.producao)
      await db.producao.clear();

    if (db.pedidos)
      await db.pedidos.clear();

    if (db.estoque)
      await db.estoque.clear();

    if (db.equipamentos)
      await db.equipamentos.clear();

    if (db.settings)
      await db.settings.clear();

    // RESTAURA

    if (
      db.materiais &&
      data.materiais?.length
    ) {

      await db.materiais.bulkAdd(
        data.materiais
      );

    }

    if (
      db.composicoes &&
      data.composicoes?.length
    ) {

      await db.composicoes.bulkAdd(
        data.composicoes
      );

    }

    if (
      db.clientes &&
      data.clientes?.length
    ) {

      await db.clientes.bulkAdd(
        data.clientes
      );

    }

    if (
      db.financeiro &&
      data.financeiro?.length
    ) {

      await db.financeiro.bulkAdd(
        data.financeiro
      );

    }

    if (
      db.producao &&
      data.producao?.length
    ) {

      await db.producao.bulkAdd(
        data.producao
      );

    }

    if (
      db.pedidos &&
      data.pedidos?.length
    ) {

      await db.pedidos.bulkAdd(
        data.pedidos
      );

    }

    if (
      db.estoque &&
      data.estoque?.length
    ) {

      await db.estoque.bulkAdd(
        data.estoque
      );

    }

    if (
      db.equipamentos &&
      data.equipamentos?.length
    ) {

      await db.equipamentos.bulkAdd(
        data.equipamentos
      );

    }

    if (
      db.settings &&
      data.settings?.length
    ) {

      await db.settings.bulkAdd(
        data.settings
      );

    }

    alert(
      'Backup restaurado com sucesso.'
    );

    window.location.reload();

    return true;

  } catch (error) {

    console.error(
      'Erro importando backup:',
      error
    );

    alert(
      'Erro ao importar backup.'
    );

    return false;

  }

}

// =========================
// EXPORTS
// =========================

export {

  exportarBackupInterno as exportarBackup,

  importarBackupInterno as importarBackup

};