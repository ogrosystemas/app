import React, { useState, useEffect } from 'react';
import { Zap, Wrench, Wind, Paintbrush, Hammer, Check } from 'lucide-react';
import db from '../database/db';

const ProfissaoSelector = ({ onSelect, selectedSlug }) => {
  const [profissoes, setProfissoes] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadProfissoes();
  }, []);

  const loadProfissoes = async () => {
    const all = await db.profissoes.where('ativo').equals(true).toArray();
    setProfissoes(all);
    setLoading(false);
  };

  const getIcon = (iconName) => {
    const icons = {
      Zap: Zap,
      Wrench: Wrench,
      Wind: Wind,
      Paintbrush: Paintbrush,
      Hammer: Hammer
    };
    const IconComponent = icons[iconName] || Wrench;
    return IconComponent;
  };

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  };

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
      {profissoes.map((prof) => {
        const Icon = getIcon(prof.icone);
        const isSelected = selectedSlug === prof.slug;
        return (
          <button
            key={prof.id}
            onClick={() => onSelect(prof)}
            className={`
              relative p-4 rounded-xl border-2 transition-all text-left
              ${isSelected 
                ? 'border-blue-500 bg-blue-50 shadow-md' 
                : 'border-slate-200 hover:border-blue-300 hover:shadow-sm'}
            `}
          >
            {isSelected && (
              <div className="absolute top-2 right-2">
                <Check size={20} className="text-blue-600" />
              </div>
            )}
            
            <div className="flex items-start gap-3">
              <div className={`
                p-3 rounded-lg
                ${isSelected ? 'bg-blue-100' : 'bg-slate-100'}
              `}>
                <Icon size={24} className={isSelected ? 'text-blue-600' : 'text-slate-600'} />
              </div>
              
              <div className="flex-1">
                <h3 className="font-bold text-slate-900 text-lg">{prof.nome}</h3>
                <p className="text-sm text-slate-600 mt-1">{prof.descricao}</p>
                
                <div className="mt-3 space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Risco Base:</span>
                    <span className="font-semibold text-slate-700">
                      {prof.riscoBase === 1.0 ? 'Normal' : 
                       prof.riscoBase === 1.1 ? 'Médio (+10%)' :
                       prof.riscoBase === 1.2 ? 'Alto (+20%)' : 'Extra (+40%)'}
                    </span>
                  </div>
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Custo Ferramental:</span>
                    <span className="font-semibold text-slate-700">{formatCurrency(prof.custoFerramental)}/mês</span>
                  </div>
                </div>
              </div>
            </div>
          </button>
        );
      })}
    </div>
  );
};

export default ProfissaoSelector;