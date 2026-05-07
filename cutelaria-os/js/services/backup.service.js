import { db } from '../database/db.js';

export async function exportarBackup() {

  const backup = {

    materiais:
      await db.materiais.toArray(),

    equipamentos:
      await db.equipamentos.toArray(),

    composicoes:
      await db.composicoes.toArray(),

    composicaoItens:
      await db.composicaoItens.toArray(),

    etapas:
      await db.etapas.toArray()

  };

  const blob = new Blob(

    [
      JSON.stringify(
        backup,
        null,
        2
      )
    ],

    {
      type: 'application/json'
    }

  );

  const url =
    URL.createObjectURL(blob);

  const link =
    document.createElement('a');

  link.href = url;

  link.download =
    `cutelaria-os-backup.json`;

  link.click();

  URL.revokeObjectURL(url);
}

export async function importarBackup(file) {

  const texto =
    await file.text();

  const backup =
    JSON.parse(texto);

  // LIMPAR TABELAS

  await db.materiais.clear();

  await db.equipamentos.clear();

  await db.composicoes.clear();

  await db.composicaoItens.clear();

  await db.etapas.clear();

  // RESTAURAR

  if (backup.materiais?.length) {

    await db.materiais.bulkAdd(
      backup.materiais
    );

  }

  if (backup.equipamentos?.length) {

    await db.equipamentos.bulkAdd(
      backup.equipamentos
    );

  }

  if (backup.composicoes?.length) {

    await db.composicoes.bulkAdd(
      backup.composicoes
    );

  }

  if (backup.composicaoItens?.length) {

    await db.composicaoItens.bulkAdd(
      backup.composicaoItens
    );

  }

  if (backup.etapas?.length) {

    await db.etapas.bulkAdd(
      backup.etapas
    );

  }

  location.reload();
}