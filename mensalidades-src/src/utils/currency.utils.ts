const formatadorBRL = new Intl.NumberFormat("pt-BR", {
  style: "currency",
  currency: "BRL",
});

/** Formata um número como moeda brasileira (ex: 1234.5 -> "R$ 1.234,50"). */
export function formatarMoeda(valor: number): string {
  return formatadorBRL.format(valor);
}

/**
 * Converte uma string de input de moeda (ex: "50,00" ou "50.00" ou "50") em number.
 * Retorna null se a string não puder ser convertida em um número válido.
 */
export function parseMoeda(valor: string): number | null {
  const normalizado = valor.trim().replace(/\./g, "").replace(",", ".");
  if (normalizado === "") return null;
  const numero = Number(normalizado);
  return Number.isFinite(numero) ? numero : null;
}
