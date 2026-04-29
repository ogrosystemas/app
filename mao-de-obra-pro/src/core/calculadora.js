/**
 * Calcula o valor total de um serviço específico
 * @param {number} tempoMinutos - Tempo estimado para o serviço
 * @param {number} valorMinuto - Valor base do minuto do profissional
 * @param {number} dificuldade - Multiplicador (1.0, 1.3, 1.6)
 * @param {number} margemReserva - Percentual de reserva (ex: 5)
 */
export const calcularPrecoServico = (tempoMinutos, valorMinuto, dificuldade = 1.0, margemReserva = 5) => {
  const custoBase = tempoMinutos * valorMinuto * dificuldade;
  const valorComReserva = custoBase * (1 + margemReserva / 100);
  
  return parseFloat(valorComReserva.toFixed(2));
};

/**
 * Decompõe o valor ganho para o Fluxo de Caixa
 */
export const decomporValorRecebido = (valorTotal, metricasFinanceiras) => {
  const { totalDespesas, valorHora } = metricasFinanceiras;
  
  // Proporção de quanto do valor vai para cada "gaveta"
  // Aqui você pode expandir para separar exatamente o que é do ajudante
  return {
    custosOperacionais: valorTotal * 0.4, // Exemplo: 40% para despesas
    lucroReserva: valorTotal * 0.05,      // 5% fixo para ferramentas
    salarioLimpo: valorTotal * 0.55      // 55% para o bolso do profissional
  };
};