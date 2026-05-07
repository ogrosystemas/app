export function calcularEtapas(etapas = []) {

  return etapas.map(etapa => {

    const maoObra =
      etapa.horas * etapa.valorHora;

    const custoTotal =
      maoObra +
      etapa.custoEnergia +
      etapa.custoAbrasivos;

    return {
      ...etapa,
      custoTotal
    };

  });

}