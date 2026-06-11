export function calcularComposicao({
  itens,
  etapas,
  margemLucro
}) {

  const custoMateriais =
    itens.reduce((total, item) => {
      return total + item.subtotal;
    }, 0);

  const custoEtapas =
    etapas.reduce((total, etapa) => {
      return total + etapa.custoTotal;
    }, 0);

  const custoTotal =
    custoMateriais + custoEtapas;

  const lucro =
    custoTotal * (margemLucro / 100);

  const valorFinal =
    custoTotal + lucro;

  return {
    custoMateriais,
    custoEtapas,
    custoTotal,
    valorFinal
  };
}