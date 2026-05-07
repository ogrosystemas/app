// ============================================
// CUTELARIA CUSTO - HELPERS.JS
// Funções utilitárias gerais
// ============================================

const generateId = () => {
    return Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
};

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const throttle = (fn, limit = 100) => {
    let inThrottle;
    return (...args) => {
        if (!inThrottle) {
            fn(...args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

const deepClone = (obj) => JSON.parse(JSON.stringify(obj));

const groupBy = (array, key) => {
    return array.reduce((result, item) => {
        const group = item[key];
        result[group] = result[group] || [];
        result[group].push(item);
        return result;
    }, {});
};

const sortBy = (array, key, order = 'asc') => {
    return [...array].sort((a, b) => {
        const aVal = a[key];
        const bVal = b[key];
        if (order === 'desc') return bVal > aVal ? 1 : -1;
        return aVal > bVal ? 1 : -1;
    });
};

const filterBy = (array, key, value) => {
    return array.filter(item => item[key] === value);
};

const sumBy = (array, key) => {
    return array.reduce((sum, item) => sum + (parseFloat(item[key]) || 0), 0);
};

const averageBy = (array, key) => {
    if (array.length === 0) return 0;
    return sumBy(array, key) / array.length;
};

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const isEmpty = (value) => {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim() === '';
    if (Array.isArray(value)) return value.length === 0;
    if (typeof value === 'object') return Object.keys(value).length === 0;
    return false;
};

const pick = (obj, keys) => {
    return keys.reduce((result, key) => {
        if (key in obj) result[key] = obj[key];
        return result;
    }, {});
};

const omit = (obj, keys) => {
    const result = { ...obj };
    keys.forEach(key => delete result[key]);
    return result;
};

// LocalStorage helpers
const storage = {
    get: (key, defaultValue = null) => {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch {
            return defaultValue;
        }
    },
    set: (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch {
            return false;
        }
    },
    remove: (key) => localStorage.removeItem(key),
    clear: () => localStorage.clear()
};
