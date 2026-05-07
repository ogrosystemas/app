import { db } from '../database/db.js';

export async function exportarBackup() {

  const backup = {

    versao: 1,

    data:
      new Date().toISOString(),

    materiais:
      await db.materiais.toArray(),

    equipamentos:
      await db.equipamentos.toArray(),

    composicoes:
      await db.composicoes.toArray(),

    composicaoItens:
      await db.composicaoItens.toArray(),

    etapas:
      await db.etapas.toArray(),

    fotos:
      await db.fotos.toArray()

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
      type:
        'application/json'
    }
  );

  const url =
    URL.createObjectURL(blob);

  const a =
    document.createElement('a');

  a.href = url;

  a.download =
    `cutelaria-os-backup-${Date.now()}.json`;

  a.click();

  URL.revokeObjectURL(url);

}

export async function importarBackup(file) {

  const texto =
    await file.text();

  const backup =
    JSON.parse(texto);

  if (!backup.versao) {

    throw new Error(
      'Backup inválido'
    );

  }

  // LIMPA BANCO

  await db.materiais.clear();

  await db.equipamentos.clear();

  await db.composicoes.clear();

  await db.composicaoItens.clear();

  await db.etapas.clear();

  await db.fotos.clear();

  // RESTAURA

  if (backup.materiais?.length) {

    await db.materiais.bulkAdd(
      backup.materiais
    );

  }

  if (
    backup.equipamentos?.length
  ) {

    await db.equipamentos.bulkAdd(
      backup.equipamentos
    );

  }

  if (
    backup.composicoes?.length
  ) {

    await db.composicoes.bulkAdd(
      backup.composicoes
    );

  }

  if (
    backup.composicaoItens?.length
  ) {

    await db.composicaoItens.bulkAdd(
      backup.composicaoItens
    );

  }

  if (backup.etapas?.length) {

    await db.etapas.bulkAdd(
      backup.etapas
    );

  }

  if (backup.fotos?.length) {

    await db.fotos.bulkAdd(
      backup.fotos
    );

  }

}