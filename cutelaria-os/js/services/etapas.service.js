export function calcularEtapas(
  etapas = [],
  equipamentos = []
) {

  return etapas.map(etapa => {

    // MÃO DE OBRA

    const maoObra =
      etapa.horas * etapa.valorHora;

    // DEPRECIAÇÃO EQUIPAMENTOS

    const custoEquipamentos =
      equipamentos.reduce((total, equipamento) => {

        return total +
          (equipamento.custoHora * etapa.horas);

      }, 0);

    // TOTAL

    const custoTotal =
      maoObra +
      etapa.custoEnergia +
      etapa.custoAbrasivos +
      custoEquipamentos;

    return {
      ...etapa,
      custoEquipamentos,
      custoTotal
    };

  });

}