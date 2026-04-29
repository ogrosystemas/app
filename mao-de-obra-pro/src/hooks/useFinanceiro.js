import { useLiveQuery } from "dexie-react-hooks";
import db from "../database/db";

export const useFinanceiro = () => {
  // Busca as configurações do banco de dados
  const perfil = useLiveQuery(() => db.configuracoes.get("perfil"));

  // Valores padrão caso o usuário ainda não tenha configurado
  const dados = perfil || {
    salarioDesejado: 0,
    custosFixos: 0,
    custoAjudante: 0,
    diasTrabalhados: 22,
    horasPorDia: 8,
    margemReserva: 5, // 5% de reserva técnica
  };

  // CÁLCULOS TÉCNICOS
  const calcularMetricas = () => {
    const totalDespesas = Number(dados.salarioDesejado) + Number(dados.custosFixos) + Number(dados.custoAjudante);
    const totalHorasMes = Number(dados.diasTrabalhados) * Number(dados.horasPorDia);
    
    if (totalHorasMes === 0) return { valorHora: 0, valorMinuto: 0 };

    const valorHoraBase = totalDespesas / totalHorasMes;
    const valorMinutoBase = valorHoraBase / 60;

    return {
      valorHora: valorHoraBase,
      valorMinuto: valorMinutoBase,
      totalDespesas,
      totalHorasMes
    };
  };

  const metricas = calcularMetricas();

  // Função para salvar/atualizar os dados do perfil
  const salvarPerfil = async (novosDados) => {
    await db.configuracoes.put({ id: "perfil", ...novosDados });
  };

  return {
    dados,
    metricas,
    salvarPerfil,
    carregando: !perfil
  };
};