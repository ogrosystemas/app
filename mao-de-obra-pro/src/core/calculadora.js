// Dificuldade levels
export const DIFICULDADE = {
  NORMAL: { fator: 1.0, label: 'Normal', cor: 'text-green-600' },
  MEDIO: { fator: 1.3, label: 'Médio', cor: 'text-yellow-600' },
  ALTO: { fator: 1.6, label: 'Alto', cor: 'text-red-600' }
};

/**
 * Calcula o preço baseado na fórmula:
 * Preço = ((T * Vm) * D) / (1 - Mr) + Td
 *
 * @param {number} tempo - Tempo do serviço em minutos
 * @param {number} valorMinuto - Valor do minuto de trabalho
 * @param {number} dificuldade - Fator de dificuldade (1.0, 1.3, 1.6)
 * @param {number} margemReserva - Margem de lucro (ex: 0.2 para 20%)
 * @returns {number} Preço calculado
 */
export function calcularPrecoServico(tempo, valorMinuto, dificuldade, margemReserva) {
  if (tempo <= 0) return 0;
  if (valorMinuto <= 0) return 0;

  const custoBase = tempo * valorMinuto;
  const custoAjustado = custoBase * dificuldade;
  const preco = custoAjustado / (1 - margemReserva);

  return Math.round(preco * 100) / 100;
}

/**
 * Calcula o valor do minuto baseado na meta salarial e horas trabalhadas
 *
 * @param {number} metaSalarial - Meta salarial mensal desejada
 * @param {number} horasTrabalhadas - Horas trabalhadas por mês
 * @returns {number} Valor do minuto
 */
export function calcularValorMinuto(metaSalarial, horasTrabalhadas) {
  if (horasTrabalhadas <= 0) return 0;
  const valorHora = metaSalarial / horasTrabalhadas;
  return valorHora / 60;
}

/**
 * Calcula o total do orçamento incluindo deslocamento
 *
 * @param {Array} itens - Lista de serviços com seus preços
 * @param {number} taxaDeslocamento - Taxa fixa de deslocamento
 * @returns {Object} Totalizadores do orçamento
 */
export function calcularTotalOrcamento(itens, taxaDeslocamento) {
  if (!itens || itens.length === 0) {
    return {
      subtotal: 0,
      taxaDeslocamento: taxaDeslocamento || 0,
      total: taxaDeslocamento || 0,
      totalServicos: 0
    };
  }

  const totalServicos = itens.reduce((sum, item) => sum + (item.preco || 0), 0);
  const transporte = taxaDeslocamento || 0;

  return {
    subtotal: totalServicos,
    taxaDeslocamento: transporte,
    total: totalServicos + transporte,
    totalServicos: totalServicos
  };
}

/**
 * Formata valor para moeda brasileira
 *
 * @param {number} valor - Valor a ser formatado
 * @returns {string} Valor formatado (R$ X.XXX,XX)
 */
export function formatarMoeda(valor) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(valor);
}

/**
 * Formata tempo em minutos para horas e minutos
 *
 * @param {number} minutos - Tempo em minutos
 * @returns {string} Tempo formatado (ex: "2h 30min")
 */
export function formatarTempo(minutos) {
  const horas = Math.floor(minutos / 60);
  const mins = minutos % 60;

  if (horas === 0) return `${mins}min`;
  if (mins === 0) return `${horas}h`;
  return `${horas}h ${mins}min`;
}