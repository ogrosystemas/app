// ============================================
// CUTELARIA CUSTO - DB.JS
// IndexedDB service via Dexie.js
// ============================================

const Database = {
    db: null,

    // Inicializar banco de dados
    async init() {
        this.db = new Dexie(APP_CONFIG.dbName);

        this.db.version(APP_CONFIG.dbVersion).stores(DB_SCHEMA);

        // Verificar se é primeiro uso
        const configs = await this.db.configuracoes.toArray();
        if (configs.length === 0) {
            await this.db.configuracoes.add({ ...DEFAULT_CONFIG });
        }

        return this.db;
    },

    // === MATERIAIS ===
    async getMateriais() {
        return await this.db.materiais.toArray();
    },

    async addMaterial(material) {
        return await this.db.materiais.add(material);
    },

    async updateMaterial(id, material) {
        return await this.db.materiais.update(id, material);
    },

    async deleteMaterial(id) {
        return await this.db.materiais.delete(id);
    },

    async getMaterialById(id) {
        return await this.db.materiais.get(id);
    },

    // === INSUMOS ===
    async getInsumos() {
        return await this.db.insumos.toArray();
    },

    async addInsumo(insumo) {
        return await this.db.insumos.add(insumo);
    },

    async updateInsumo(id, insumo) {
        return await this.db.insumos.update(id, insumo);
    },

    async deleteInsumo(id) {
        return await this.db.insumos.delete(id);
    },

    async getInsumoById(id) {
        return await this.db.insumos.get(id);
    },

    // === EQUIPAMENTOS ===
    async getEquipamentos() {
        return await this.db.equipamentos.toArray();
    },

    async addEquipamento(equip) {
        return await this.db.equipamentos.add(equip);
    },

    async updateEquipamento(id, equip) {
        return await this.db.equipamentos.update(id, equip);
    },

    async deleteEquipamento(id) {
        return await this.db.equipamentos.delete(id);
    },

    async getEquipamentoById(id) {
        return await this.db.equipamentos.get(id);
    },

    // === MODELOS ===
    async getModelos() {
        return await this.db.modelos.toArray();
    },

    async addModelo(modelo) {
        return await this.db.modelos.add(modelo);
    },

    async updateModelo(id, modelo) {
        return await this.db.modelos.update(id, modelo);
    },

    async deleteModelo(id) {
        return await this.db.modelos.delete(id);
    },

    async getModeloById(id) {
        return await this.db.modelos.get(id);
    },

    // === FACAS ===
    async getFacas() {
        return await this.db.facas.toArray();
    },

    async addFaca(faca) {
        return await this.db.facas.add(faca);
    },

    async updateFaca(id, faca) {
        return await this.db.facas.update(id, faca);
    },

    async deleteFaca(id) {
        return await this.db.facas.delete(id);
    },

    async getFacaById(id) {
        return await this.db.facas.get(id);
    },

    // === CONFIGURAÇÕES ===
    async getConfiguracoes() {
        const configs = await this.db.configuracoes.toArray();
        return configs[0] || DEFAULT_CONFIG;
    },

    async updateConfiguracoes(config) {
        const configs = await this.db.configuracoes.toArray();
        if (configs.length > 0) {
            return await this.db.configuracoes.update(configs[0].id, config);
        } else {
            return await this.db.configuracoes.add(config);
        }
    },

    // === UTILIDADES ===
    async clearAll() {
        await this.db.materiais.clear();
        await this.db.insumos.clear();
        await this.db.equipamentos.clear();
        await this.db.modelos.clear();
        await this.db.facas.clear();
        await this.db.configuracoes.clear();
    },

    async exportAll() {
        return {
            materiais: await this.db.materiais.toArray(),
            insumos: await this.db.insumos.toArray(),
            equipamentos: await this.db.equipamentos.toArray(),
            modelos: await this.db.modelos.toArray(),
            facas: await this.db.facas.toArray(),
            configuracoes: await this.db.configuracoes.toArray(),
            exportadoEm: new Date().toISOString(),
            versao: APP_CONFIG.version
        };
    },

    async importAll(data) {
        await this.clearAll();

        if (data.materiais?.length) await this.db.materiais.bulkAdd(data.materiais);
        if (data.insumos?.length) await this.db.insumos.bulkAdd(data.insumos);
        if (data.equipamentos?.length) await this.db.equipamentos.bulkAdd(data.equipamentos);
        if (data.modelos?.length) await this.db.modelos.bulkAdd(data.modelos);
        if (data.facas?.length) await this.db.facas.bulkAdd(data.facas);
        if (data.configuracoes?.length) await this.db.configuracoes.bulkAdd(data.configuracoes);
    }
};
