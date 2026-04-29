import { useState, useEffect } from 'react';
import db from '../database/db';
import { calcularValorMinuto } from '../core/calculadora';

export function useFinanceiro() {
  const [config, setConfig] = useState({
    metaSalarial: 5000,
    horasTrabalhadas: 160,
    margemReserva: 0.2,
    taxaDeslocamento: 50,
    valorMinuto: 0
  });
  const [loading, setLoading] = useState(true);

  // Load config from database
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

  const updateConfig = async (chave, valor) => {
    try {
      await db.config.where('chave').equals(chave).modify({ valor });
      await loadConfig(); // Reload config
      return true;
    } catch (error) {
      console.error('Error updating config:', error);
      return false;
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
      console.error('Error updating all config:', error);
      return false;
    }
  };

  return {
    config,
    loading,
    updateConfig,
    updateAllConfig,
    refresh: loadConfig
  };
}