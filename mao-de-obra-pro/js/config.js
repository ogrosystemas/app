export function calcularValorHora(meta, custos, dias) {
    // 8 horas por dia padrão
    const valor = (meta + custos) / (dias * 8);
    localStorage.setItem('mopro_valor_hora', valor);
    return valor;
}