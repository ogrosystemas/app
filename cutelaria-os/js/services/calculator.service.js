export function calcularComposicao({
  custoMateriais,
  horasTrabalho,
  valorHora,
  margemLucro
}) {

  const custoMaoObra =
    horasTrabalho * valorHora;

  const custoTotal =
    custoMateriais + custoMaoObra;

  const lucro =
    custoTotal * (margemLucro / 100);

  const valorFinal =
    custoTotal + lucro;

  return {
    custoMaoObra,
    custoTotal,
    valorFinal
  };
}