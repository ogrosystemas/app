# 🗄️ Schema IndexedDB - Cutelaria Custo

## Banco de Dados

- **Nome:** `CutelariaCustoDB`
- **Versão:** `1`
- **Engine:** Dexie.js (wrapper IndexedDB)

## Stores (Tabelas)

### 1. `materiais`
Materiais principais: aços, cabos, madeiras.

```javascript
{
    id: 1,                    // auto-increment
    nome: "Aço 1070 Carbono",
    tipo: "aco-carbono",      // enum TIPOS_INSUMO
    quantidade: 5,            // número
    unidade: "kg",            // enum UNIDADES
    preco: 150.00,           // preço total pago
    dataCompra: "2026-01-15", // ISO date
    fornecedor: "Aços Ltda"   // opcional
}
```

**Índices:** `++id, nome, tipo, dataCompra`

### 2. `insumos`
Insumos de consumo: lixas, carvão, gás, colas.

```javascript
{
    id: 1,
    nome: "Lixa 80",
    tipo: "lixa",
    quantidade: 50,
    unidade: "un",
    preco: 75.00,
    dataCompra: "2026-01-05",
    fornecedor: "Abrasivos Sul"
}
```

**Índices:** `++id, nome, tipo, dataCompra`

### 3. `equipamentos`
Ferramentas e máquinas com depreciação.

```javascript
{
    id: 1,
    nome: "Esmerilhadeira Angular",
    tipo: "esmerilhadeira",   // enum TIPOS_EQUIPAMENTO
    preco: 350.00,           // preço de aquisição
    vidaUtil: 2000,          // horas estimadas
    dataAquisicao: "2025-06-15",
    notas: "Bosch GWS 7-115" // opcional
}
```

**Índices:** `++id, nome, tipo, dataAquisicao`

### 4. `modelos`
Templates de facas para reutilização.

```javascript
{
    id: 1,
    nome: "Faca Chef 8"",
    descricao: "Faca de chef tradicional",
    materiais: [
        { materialId: 1, quantidade: 0.3 }
    ],
    insumos: [
        { insumoId: 1, quantidade: 5 }
    ]
}
```

**Índices:** `++id, nome`

### 5. `facas`
Registro de cada faca produzida.

```javascript
{
    id: 1,
    nome: "Faca Chef para João",
    cliente: "João Silva",
    modeloId: 1,              // referência opcional
    data: "2026-05-07",

    // Materiais usados
    materiais: [
        {
            id: 1,
            nome: "Aço 1070",
            quantidade: 0.3,
            unidade: "kg",
            custo: 9.00
        }
    ],

    // Insumos usados
    insumos: [
        {
            id: 1,
            nome: "Lixa 80",
            quantidade: 3,
            unidade: "un",
            custo: 4.50
        }
    ],

    // Equipamentos usados
    equipamentos: [
        {
            id: 1,
            nome: "Esmerilhadeira",
            horas: 2,
            custo: 0.35
        }
    ],

    // Custos extras
    horasTrabalho: 8,
    kwh: 3.5,
    gas: 15.00,
    perda: 12,
    margem: 55,

    // Resultados do cálculo
    custoInsumos: 45.00,
    custoEquipamentos: 2.50,
    custoEnergia: 2.98,
    custoGas: 15.00,
    custoMaoObra: 480.00,
    custoPerda: 65.10,
    subtotal: 542.50,
    custoTotal: 607.60,
    precoVenda: 941.78,
    lucro: 334.18,
    margemReal: 55.00
}
```

**Índices:** `++id, nome, data, cliente`

### 6. `configuracoes`
Configurações do usuário (apenas 1 registro).

```javascript
{
    id: 1,
    horaTrabalho: 60.00,
    precoKwh: 0.85,
    moeda: "BRL",
    perdaPadrao: 12,
    margemPadrao: 55
}
```

**Índices:** `++id`

## API Dexie

### Operações CRUD
```javascript
// Create
await db.materiais.add({ nome: "Aço", ... });

// Read
const materiais = await db.materiais.toArray();
const material = await db.materiais.get(1);

// Update
await db.materiais.update(1, { preco: 200 });

// Delete
await db.materiais.delete(1);

// Query
const acos = await db.materiais.where('tipo').equals('aco').toArray();
const recentes = await db.materiais.orderBy('dataCompra').reverse().limit(5).toArray();
```

## Backup/Restore

### Exportar
```javascript
const dados = {
    materiais: await db.materiais.toArray(),
    insumos: await db.insumos.toArray(),
    equipamentos: await db.equipamentos.toArray(),
    modelos: await db.modelos.toArray(),
    facas: await db.facas.toArray(),
    configuracoes: await db.configuracoes.toArray()
};
```

### Importar
```javascript
await db.materiais.bulkAdd(dados.materiais);
```
