export const calcularValorMinuto = (config) => {
  const { 
    salarioDesejado, 
    custosFixos, 
    custoAjudante, 
    diasTrabalhados, 
    horasPorDia 
  } = config;

  const totalCustoMensal = Number(salarioDesejado) + Number(custosFixos) + Number(custoAjudante);
  const totalHorasMes = diasTrabalhados * horasPorDia;
  const valorHora = totalCustoMensal / totalHorasMes;
  
  return valorHora / 60; // Retorna valor por minuto
};

export const calcularOrcamentoFinal = (itens, valorMinuto, taxaDeslocamento, markupReserva = 1.05) => {
  const subtotalMaoDeObra = itens.reduce((acc, item) => {
    // tempo * minuto * dificuldade
    return acc + (item.tempo * valorMinuto * item.dificuldade);
  }, 0);

  // Aplica o markup de reserva de ferramentas (ex: 5%) e soma deslocamento
  return (subtotalMaoDeObra * markupReserva) + Number(taxaDeslocamento);
};