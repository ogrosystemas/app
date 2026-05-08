import { db } from '../database/db.js';

// =========================
// EXPORTAR BACKUP
// =========================

export async function exportarBackup() {

  try {

    const backupData = {

      materiais:
        await db.materiais.toArray(),

      composicoes:
        await db.composicoes.toArray(),

      clientes:
        await db.clientes.toArray(),

      financeiro:
        await db.financeiro.toArray(),

      producao:
        await db.producao.toArray(),

      pedidos:
        await db.pedidos.toArray(),

      estoque:
        await db.estoque.toArray(),

      equipamentos:
        await db.equipamentos.toArray(),

      settings:
        await db.settings.toArray(),

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

    const link =
      document.createElement(
        'a'
      );

    const date =
      new Date()
        .toISOString()
        .split('T')[0];

    link.href = url;

    link.download =
      `cutelaria-backup-${date}.json`;

    document.body.appendChild(
      link
    );

    link.click();

    document.body.removeChild(
      link
    );

    URL.revokeObjectURL(
      url
    );

    return true;

  } catch (error) {

    console.error(
      'Erro ao exportar backup:',
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

export async function importarBackup(
  file
) {

  try {

    const text =
      await file.text();

    const data =
      JSON.parse(text);

    // LIMPA TABELAS

    await db.materiais.clear();

    await db.composicoes.clear();

    await db.clientes.clear();

    await db.financeiro.clear();

    await db.producao.clear();

    await db.pedidos.clear();

    await db.estoque.clear();

    await db.equipamentos.clear();

    await db.settings.clear();

    // RESTAURA

    if (data.materiais?.length) {

      await db.materiais.bulkAdd(
        data.materiais
      );

    }

    if (data.composicoes?.length) {

      await db.composicoes.bulkAdd(
        data.composicoes
      );

    }

    if (data.clientes?.length) {

      await db.clientes.bulkAdd(
        data.clientes
      );

    }

    if (data.financeiro?.length) {

      await db.financeiro.bulkAdd(
        data.financeiro
      );

    }

    if (data.producao?.length) {

      await db.producao.bulkAdd(
        data.producao
      );

    }

    if (data.pedidos?.length) {

      await db.pedidos.bulkAdd(
        data.pedidos
      );

    }

    if (data.estoque?.length) {

      await db.estoque.bulkAdd(
        data.estoque
      );

    }

    if (data.equipamentos?.length) {

      await db.equipamentos.bulkAdd(
        data.equipamentos
      );

    }

    if (data.settings?.length) {

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
      'Erro ao importar backup:',
      error
    );

    alert(
      'Erro ao importar backup.'
    );

    return false;

  }

}