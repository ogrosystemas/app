// ============================================================
// calculadora.js — lógica pura, sem side effects
// ============================================================

export const DIFICULDADE = {
  NORMAL: { fator: 1.0, label: 'Normal',  cor: 'success' },
  MEDIO:  { fator: 1.3, label: 'Médio',   cor: 'warning' },
  ALTO:   { fator: 1.6, label: 'Alto',    cor: 'danger'  },
};

/**
 * Valor por minuto ajustado ao risco da profissão
 */
export function calcularValorMinuto(metaSalarial, horasTrabalhadas, riscoBase = 1.0) {
  if (!horasTrabalhadas || horasTrabalhadas <= 0) return 0;
  const valorHora = metaSalarial / horasTrabalhadas;
  return (valorHora / 60) * riscoBase;
}

/**
 * Preço de um serviço baseado em tempo
 * Fórmula: (tempo × vm × dificuldade) / (1 - margem)
 */
export function calcularPrecoServico(tempo, valorMinuto, fatorDificuldade, margemReserva) {
  if (tempo <= 0 || valorMinuto <= 0) return 0;
  const custo = tempo * valorMinuto * fatorDificuldade;
  return Math.round((custo / (1 - margemReserva)) * 100) / 100;
}

/**
 * Total do orçamento com desconto
 */
export function calcularTotalOrcamento(itens, taxaDeslocamento, desconto) {
  const subtotal = itens.reduce((s, i) => s + (i.precoTotal || 0), 0);
  const comDeslocamento = subtotal + (taxaDeslocamento || 0);
  let total = comDeslocamento;

  if (desconto && desconto.valor > 0) {
    if (desconto.tipo === 'percentual') {
      total = comDeslocamento * (1 - desconto.valor / 100);
    } else {
      total = comDeslocamento - desconto.valor;
    }
  }

  return {
    subtotal,
    taxaDeslocamento: taxaDeslocamento || 0,
    desconto: desconto || { tipo: 'valor', valor: 0 },
    total: Math.max(0, total),
  };
}

// ── formatters ───────────────────────────────────────────────

export function moeda(valor) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
}

export function tempo(minutos) {
  if (!minutos) return '—';
  const h = Math.floor(minutos / 60);
  const m = minutos % 60;
  if (h === 0) return `${m}min`;
  if (m === 0) return `${h}h`;
  return `${h}h ${m}min`;
}

export function dataLocal(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('pt-BR');
}

export function dataVencimento(dias) {
  const d = new Date();
  d.setDate(d.getDate() + dias);
  return d.toISOString();
}
