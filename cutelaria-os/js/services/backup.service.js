import { db } from '../database/db.js';

// ========================================
// EXPORTAR BACKUP
// ========================================

export async function exportarBackup() {

  try {

    const backup = {

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
        backup,
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
      'Erro exportando backup:',
      error
    );

    alert(
      'Erro ao exportar backup.'
    );

    return false;

  }

}

// ========================================
// IMPORTAR BACKUP
// ========================================

export async function importarBackup(
  file
) {

  try {

    const text =
      await file.text();

    const data =
      JSON.parse(text);

    if (db.materiais) {

      await db.materiais.clear();

      if (data.materiais?.length) {

        await db.materiais.bulkAdd(
          data.materiais
        );

      }

    }

    if (db.composicoes) {

      await db.composicoes.clear();

      if (data.composicoes?.length) {

        await db.composicoes.bulkAdd(
          data.composicoes
        );

      }

    }

    if (db.clientes) {

      await db.clientes.clear();

      if (data.clientes?.length) {

        await db.clientes.bulkAdd(
          data.clientes
        );

      }

    }

    if (db.financeiro) {

      await db.financeiro.clear();

      if (data.financeiro?.length) {

        await db.financeiro.bulkAdd(
          data.financeiro
        );

      }

    }

    if (db.producao) {

      await db.producao.clear();

      if (data.producao?.length) {

        await db.producao.bulkAdd(
          data.producao
        );

      }

    }

    if (db.pedidos) {

      await db.pedidos.clear();

      if (data.pedidos?.length) {

        await db.pedidos.bulkAdd(
          data.pedidos
        );

      }

    }

    if (db.estoque) {

      await db.estoque.clear();

      if (data.estoque?.length) {

        await db.estoque.bulkAdd(
          data.estoque
        );

      }

    }

    if (db.equipamentos) {

      await db.equipamentos.clear();

      if (data.equipamentos?.length) {

        await db.equipamentos.bulkAdd(
          data.equipamentos
        );

      }

    }

    if (db.settings) {

      await db.settings.clear();

      if (data.settings?.length) {

        await db.settings.bulkAdd(
          data.settings
        );

      }

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