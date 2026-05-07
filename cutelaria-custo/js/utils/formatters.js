// ============================================
// CUTELARIA CUSTO - FORMATTERS.JS
// Formatação de moeda, data, número
// ============================================

const Formatters = {
    // Estado da moeda atual
    _config: null,

    setConfig(config) {
        this._config = config;
    },

    getConfig() {
        return this._config || DEFAULT_CONFIG;
    },

    // Moeda
    currency(value, currencyCode = null) {
        const config = this.getConfig();
        const code = currencyCode || config.moeda || 'BRL';
        const moeda = MOEDAS[code] || MOEDAS.BRL;

        return new Intl.NumberFormat(moeda.locale, {
            style: 'currency',
            currency: code
        }).format(value || 0);
    },

    // Número simples
    number(value, decimals = 2) {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value || 0);
    },

    // Porcentagem
    percent(value, decimals = 0) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'percent',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format((value || 0) / 100);
    },

    // Data
    date(dateStr, options = {}) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';

        const opts = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            ...options
        };
        return d.toLocaleDateString('pt-BR', opts);
    },

    // Data e hora
    datetime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleString('pt-BR');
    },

    // Data relativa (hoje, ontem, etc)
    relativeDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / (1000 * 60 * 60 * 24));

        if (diff === 0) return 'Hoje';
        if (diff === 1) return 'Ontem';
        if (diff < 7) return `${diff} dias atrás`;
        if (diff < 30) return `${Math.floor(diff / 7)} semanas atrás`;
        return this.date(dateStr);
    },

    // Peso (kg/g)
    weight(value, unit = 'kg') {
        if (unit === 'g') {
            return `${this.number(value, 1)} g`;
        }
        return `${this.number(value, 3)} kg`;
    },

    // Comprimento
    length(value, unit = 'cm') {
        return `${this.number(value, 1)} ${unit}`;
    },

    // Hora
    hours(value) {
        const h = Math.floor(value);
        const m = Math.round((value - h) * 60);
        if (m === 0) return `${h}h`;
        return `${h}h ${m}min`;
    },

    // Telefone
    phone(value) {
        const cleaned = (value || '').replace(/\D/g, '');
        if (cleaned.length === 11) {
            return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        return value;
    },

    // Nome do tipo de insumo
    tipoInsumo(tipo) {
        return TIPOS_INSUMO[tipo]?.label || tipo;
    },

    // Badge class do tipo
    badgeClass(tipo) {
        return TIPOS_INSUMO[tipo]?.badge || 'badge-outro';
    },

    // Icon do tipo
    tipoIcon(tipo) {
        return TIPOS_INSUMO[tipo]?.icon || '📦';
    },

    // Nome do tipo de equipamento
    tipoEquipamento(tipo) {
        return TIPOS_EQUIPAMENTO[tipo]?.label || tipo;
    },

    // Icon do equipamento
    equipIcon(tipo) {
        return TIPOS_EQUIPAMENTO[tipo]?.icon || '🔧';
    }
};
