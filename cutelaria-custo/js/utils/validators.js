// ============================================
// CUTELARIA CUSTO - VALIDATORS.JS
// Validação de formulários
// ============================================

const Validators = {
    // Validação de campo obrigatório
    required(value, fieldName = 'Campo') {
        if (isEmpty(value)) {
            return { valid: false, error: `${fieldName} é obrigatório` };
        }
        return { valid: true };
    },

    // Validação de número positivo
    positiveNumber(value, fieldName = 'Campo') {
        const num = parseFloat(value);
        if (isNaN(num) || num <= 0) {
            return { valid: false, error: `${fieldName} deve ser um número positivo` };
        }
        return { valid: true, value: num };
    },

    // Validação de número não negativo
    nonNegative(value, fieldName = 'Campo') {
        const num = parseFloat(value);
        if (isNaN(num) || num < 0) {
            return { valid: false, error: `${fieldName} não pode ser negativo` };
        }
        return { valid: true, value: num };
    },

    // Validação de porcentagem
    percentage(value, fieldName = 'Campo') {
        const num = parseFloat(value);
        if (isNaN(num) || num < 0 || num > 100) {
            return { valid: false, error: `${fieldName} deve estar entre 0 e 100` };
        }
        return { valid: true, value: num };
    },

    // Validação de email
    email(value) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regex.test(value)) {
            return { valid: false, error: 'Email inválido' };
        }
        return { valid: true };
    },

    // Validação de data
    date(value, fieldName = 'Data') {
        if (!value) {
            return { valid: false, error: `${fieldName} é obrigatória` };
        }
        const d = new Date(value);
        if (isNaN(d.getTime())) {
            return { valid: false, error: `${fieldName} inválida` };
        }
        return { valid: true };
    },

    // Validação de formulário completo
    validateForm(fields) {
        const errors = [];
        const values = {};

        for (const [key, config] of Object.entries(fields)) {
            const { value, rules, label } = config;

            for (const rule of rules) {
                const result = rule(value, label);
                if (!result.valid) {
                    errors.push({ field: key, error: result.error });
                    break;
                }
                if (result.value !== undefined) {
                    values[key] = result.value;
                }
            }
        }

        return {
            valid: errors.length === 0,
            errors,
            values
        };
    },

    // Validação customizada
    custom(value, validatorFn, errorMsg) {
        const result = validatorFn(value);
        if (!result) {
            return { valid: false, error: errorMsg };
        }
        return { valid: true };
    }
};

// Helper para criar regras de validação
const Rules = {
    required(label) {
        return (value) => Validators.required(value, label);
    },
    positiveNumber(label) {
        return (value) => Validators.positiveNumber(value, label);
    },
    nonNegative(label) {
        return (value) => Validators.nonNegative(value, label);
    },
    percentage(label) {
        return (value) => Validators.percentage(value, label);
    }
};
