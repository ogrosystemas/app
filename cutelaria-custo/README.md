# 🔪 Cutelaria Custo

**Calculadora completa de custos para cutelaria artesanal**

Uma PWA (Progressive Web App) profissional para cuteleiros calcularem o custo real de suas facas artesanais. Com amortização de equipamentos, cálculo de energia, mão de obra, perdas e margem de lucro.

## ✨ Funcionalidades

| Módulo | O que faz |
|--------|-----------|
| 📦 **Materiais** | Cadastro de aços, cabos, madeiras com custo unitário |
| 🧪 **Insumos** | Lixas, carvão, gás, colas, óleos, discos, juta, pinos |
| 🔧 **Equipamentos** | Ferramentas com cálculo de depreciação por hora |
| ➕ **Nova Faca** | Calculadora completa com todos os custos em tempo real |
| 📊 **Dashboard** | Estatísticas, gráficos e resumo do negócio |
| 📜 **Histórico** | Todas as facas com detalhes completos |
| ⚙️ **Configurações** | Backup/restore, moedas, valores padrão |

## 🧮 Fórmulas de Cálculo

```
Custo Insumo = Preço ÷ Quantidade Comprada × Quantidade Usada
Custo Equipamento = Preço Aquisição ÷ Vida Útil (h) × Horas Usadas
Custo Energia = kWh × Preço kWh
Custo Mão de Obra = Horas × Valor/Hora
Custo Perda = Subtotal × % Perda
Custo Total = Subtotal + Perda
Preço Venda = Custo Total × (1 + Margem%)
```

## 🚀 Como usar no GitHub Pages

### 1. Criar repositório
```bash
git init
git add .
git commit -m "Primeira versão"
git remote add origin https://github.com/SEU-USUARIO/cutelaria-custo.git
git push -u origin main
```

### 2. Ativar GitHub Pages
- Acesse **Settings → Pages** no repositório
- Source: `Deploy from a branch`
- Branch: `main` / folder: `/ (root)`
- Seu app estará em: `https://SEU-USUARIO.github.io/cutelaria-custo/`

### 3. Instalar no celular

**Android (Chrome):**
1. Acesse o link no Chrome
2. Menu (⋮) → "Adicionar à tela inicial"

**iPhone (Safari):**
1. Acesse o link no Safari
2. Compartilhar → "Adicionar à Tela de Início"

## 📱 Screenshots

| Dashboard | Nova Faca | Histórico |
|-----------|-----------|-----------|
| 📊 Stats | ➕ Cálculo | 📜 Lista |

## 🗂️ Estrutura do Projeto

```
cutelaria-custo/
├── index.html              # SPA principal
├── manifest.json           # PWA manifest
├── sw.js                   # Service Worker
├── css/                    # 5 arquivos CSS modulares
├── js/
│   ├── utils/              # Constants, helpers, formatters, validators, calculations
│   ├── services/           # DB, storage, export-import, cache
│   ├── modules/            # App, router, UI, modal, toast, navbar
│   └── pages/              # 7 páginas
├── data/
│   └── default-data.json   # Dados demo
└── docs/                   # Documentação completa
```

## 🔒 Privacidade

- **100% offline** — funciona sem internet
- **Dados locais** — IndexedDB no seu celular
- **Sem servidor** — nada vai para nuvem
- **Backup manual** — você controla seus dados via JSON

## 🛠️ Tecnologias

- Vanilla JavaScript (ES6+ modules)
- Dexie.js (IndexedDB wrapper)
- Chart.js (gráficos)
- CSS3 custom properties
- Service Workers
- GitHub Pages

## 📄 Documentação

- [🏗️ Arquitetura](docs/estrutura.md)
- [✨ Funcionalidades](docs/funcionalidades.md)
- [🗄️ Schema do Banco](docs/api-indexeddb.md)

## 📝 Licença

MIT — use, modifique e distribua livremente.

---

**Feito com 🔪 para cuteleiros.**
