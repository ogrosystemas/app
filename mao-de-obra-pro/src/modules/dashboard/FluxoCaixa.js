export const decomporFaturamento = (valorTotal, config) => {
  // Baseado na proporção de custos do perfil
  const totalCustos = config.custosFixos + config.custoAjudante;
  const faturamentoMeta = config.salarioDesejado + totalCustos;
  
  const percentualCusto = totalCustos / faturamentoMeta;
  const percentualSalario = config.salarioDesejado / faturamentoMeta;

  return {
    custos: valorTotal * percentualCusto,
    proLabore: valorTotal * percentualSalario,
    reserva: valorTotal * 0.05 // 5% fixo de reserva técnica
  };
};