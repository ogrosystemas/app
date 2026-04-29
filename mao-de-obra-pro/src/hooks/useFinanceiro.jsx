import { useState, useEffect } from 'react';
import db from '../database/db.js';

export function calcularValorMinuto(metaSalarial, horasTrabalhadas) {
  if (horasTrabalhadas <= 0) return 0;
  const valorHora = metaSalarial / horasTrabalhadas;
  return valorHora / 60;
}

export function calcularPrecoServico(tempo, valorMinuto, dificuldade, margemReserva) {
  if (tempo <= 0) return 0;
  if (valorMinuto <= 0) return 0;

  const custoBase = tempo * valorMinuto;
  const custoAjustado = custoBase * dificuldade;
  const preco = custoAjustado / (1 - margemReserva);

  return Math.round(preco * 100) / 100;
}

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

export function formatarMoeda(valor) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(valor);
}

export function formatarTempo(minutos) {
  const horas = Math.floor(minutos / 60);
  const mins = minutos % 60;

  if (horas === 0) return `${mins}min`;
  if (mins === 0) return `${horas}h`;
  return `${horas}h ${mins}min`;
}

export const DIFICULDADE = {
  NORMAL: { fator: 1.0, label: 'Normal' },
  MEDIO: { fator: 1.3, label: 'Médio' },
  ALTO: { fator: 1.6, label: 'Alto' }
};

export function useFinanceiro() {
  const [config, setConfig] = useState({
    metaSalarial: 5000,
    horasTrabalhadas: 160,
    margemReserva: 0.2,
    taxaDeslocamento: 50,
    valorMinuto: 0
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadConfig();
  }, []);

  const loadConfig = async () => {
    try {
      setLoading(true);
      const configs = await db.config.toArray();
      const configObj = {};
      configs.forEach(c => {
        configObj[c.chave] = c.valor;
      });

      const valorMinuto = calcularValorMinuto(
        configObj.metaSalarial || 5000,
        configObj.horasTrabalhadas || 160
      );

      setConfig({
        metaSalarial: configObj.metaSalarial || 5000,
        horasTrabalhadas: configObj.horasTrabalhadas || 160,
        margemReserva: configObj.margemReserva || 0.2,
        taxaDeslocamento: configObj.taxaDeslocamento || 50,
        valorMinuto: valorMinuto
      });
    } catch (error) {
      console.error('Error loading config:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateAllConfig = async (newConfig) => {
    try {
      for (const [chave, valor] of Object.entries(newConfig)) {
        const existing = await db.config.where('chave').equals(chave).first();
        if (existing) {
          await db.config.where('chave').equals(chave).modify({ valor });
        } else {
          await db.config.add({ chave, valor });
        }
      }
      await loadConfig();
      return true;
    } catch (error) {
      console.error('Error updating config:', error);
      return false;
    }
  };

  return {
    config,
    loading,
    updateAllConfig,
    refresh: loadConfig
  };
}