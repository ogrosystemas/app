import { useLiveQuery } from "dexie-react-hooks";
import db from "../database/db";

export const useFinanceiro = () => {
  const perfil = useLiveQuery(() => db.configuracoes.get("perfil"));

  const dados = perfil || {
    salarioDesejado: 0,
    custosFixos: 0,
    custoAjudante: 0,
    diasTrabalhados: 22,
    horasPorDia: 8,
    margemReserva: 5,
    nomeEmpresa: ''
  };

  const calcularMetricas = () => {
    const totalDespesas = Number(dados.salarioDesejado) + Number(dados.custosFixos) + Number(dados.custoAjudante);
    const totalHorasMes = (Number(dados.diasTrabalhados) || 1) * (Number(dados.horasPorDia) || 1);

    const valorHoraBase = totalDespesas / totalHorasMes;
    const valorMinutoBase = valorHoraBase / 60;

    return {
      valorHora: valorHoraBase || 0,
      valorMinuto: valorMinutoBase || 0,
      totalDespesas,
      totalHorasMes
    };
  };

  const metricas = calcularMetricas();

  const salvarPerfil = async (novosDados) => {
    await db.configuracoes.put({ id: "perfil", ...novosDados });
  };

  return { dados, metricas, salvarPerfil, carregando: !perfil };
};