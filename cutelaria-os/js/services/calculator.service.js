export function calcularComposicao({
  itens,
  horasTrabalho,
  valorHora,
  margemLucro,
  custoEnergia = 0
}) {

  const custoMateriais =
    itens.reduce((total, item) => {
      return total + item.subtotal;
    }, 0);

  const custoMaoObra =
    horasTrabalho * valorHora;

  const custoTotal =
    custoMateriais +
    custoMaoObra +
    custoEnergia;

  const lucro =
    custoTotal * (margemLucro / 100);

  const valorFinal =
    custoTotal + lucro;

  return {
    custoMateriais,
    custoMaoObra,
    custoTotal,
    valorFinal
  };
}