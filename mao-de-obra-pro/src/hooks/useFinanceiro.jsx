import { useState, useEffect } from 'react';
import db from '../database/db';
import { calcularValorMinuto } from '../core/calculadora';

export function useFinanceiro() {
  const [config, setConfig] = useState({
    metaSalarial: 5000,
    horasTrabalhadas: 160,
    margemReserva: 0.2,
    taxaDeslocamento: 50,
    valorMinuto: 0,
    profissaoSelecionada: null,
    adicionalPericulosidade: 0.15,
    custoManutencaoFerramenta: 300,
    primeiroAcesso: true
  });
  const [loading, setLoading] = useState(true);
  const [profissao, setProfissao] = useState(null);

  useEffect(() => {
    loadConfig();
  }, []);

  const loadConfig = async () => {
    try {
      setLoading(true);
      const configs = await db.config.toArray();
      const configObj = {};
      configs.forEach(c => { configObj[c.chave] = c.valor; });

      const profissaoSlug = configObj.profissaoSelecionada || 'eletricista';
      const profissaoData = await db.profissoes.where('slug').equals(profissaoSlug).first();

      const valorMinutoBase = calcularValorMinuto(
        configObj.metaSalarial || 5000,
        configObj.horasTrabalhadas || 160
      );
      const riscoMultiplier = profissaoData ? profissaoData.riscoBase : 1.0;
      const valorMinutoAjustado = valorMinutoBase * riscoMultiplier;

      setProfissao(profissaoData);
      setConfig({
        metaSalarial: configObj.metaSalarial || 5000,
        horasTrabalhadas: configObj.horasTrabalhadas || 160,
        margemReserva: configObj.margemReserva || 0.2,
        taxaDeslocamento: configObj.taxaDeslocamento || 50,
        valorMinuto: valorMinutoAjustado,
        profissaoSelecionada: profissaoSlug,
        adicionalPericulosidade: configObj.adicionalPericulosidade || 0.15,
        custoManutencaoFerramenta: configObj.custoManutencaoFerramenta || 300,
        primeiroAcesso: configObj.primeiroAcesso || true
      });
    } catch (error) {
      console.error('Error loading config:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateConfig = async (chave, valor) => {
    try {
      const existing = await db.config.get(chave);
      if (existing) {
        await db.config.update(chave, { valor });
      } else {
        await db.config.add({ chave, valor });
      }
      await loadConfig();
      return true;
    } catch (error) {
      console.error('Error updating config:', error);
      return false;
    }
  };

  const updateAllConfig = async (newConfig) => {
    try {
      for (const [chave, valor] of Object.entries(newConfig)) {
        const existing = await db.config.get(chave);
        if (existing) {
          await db.config.update(chave, { valor });
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

  const selecionarProfissao = async (profissaoData) => {
    try {
      await updateConfig('profissaoSelecionada', profissaoData.slug);
      await updateConfig('custoManutencaoFerramenta', profissaoData.custoFerramental);

      let adicionalPericulosidade = 0.15;
      if (profissaoData.riscoBase >= 1.2) adicionalPericulosidade = 0.20;
      else if (profissaoData.riscoBase >= 1.1) adicionalPericulosidade = 0.15;
      else adicionalPericulosidade = 0.10;
      await updateConfig('adicionalPericulosidade', adicionalPericulosidade);
      await updateConfig('primeiroAcesso', 0);

      await loadConfig();
      return true;
    } catch (error) {
      console.error('Error selecting profession:', error);
      return false;
    }
  };

  return {
    config,
    profissao,
    loading,
    updateConfig,
    updateAllConfig,
    selecionarProfissao,
    refresh: loadConfig
  };
}