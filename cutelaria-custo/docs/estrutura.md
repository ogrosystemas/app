# 🏗️ Arquitetura - Cutelaria Custo

## Visão Geral

Aplicação PWA (Progressive Web App) para cálculo de custos de cutelaria artesanal. Arquitetura modular, offline-first, sem backend.

## Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| **Frontend** | Vanilla JavaScript (ES6+) |
| **CSS** | CSS3 com variáveis, mobile-first |
| **Banco de Dados** | IndexedDB via Dexie.js |
| **Gráficos** | Chart.js |
| **PWA** | Service Workers, Manifest |
| **Hospedagem** | GitHub Pages |

## Estrutura de Pastas

```
cutelaria-custo/
├── index.html              # SPA principal
├── manifest.json           # Configuração PWA
├── sw.js                   # Service Worker
│
├── assets/
│   ├── fonts/              # Fontes customizadas
│   ├── icons/              # Ícones PWA (72x72 a 512x512)
│   └── images/             # Imagens do app
│
├── css/
│   ├── main.css            # Variáveis, reset, base
│   ├── layout.css          # Grid, flexbox, responsivo
│   ├── components.css      # Botões, cards, inputs, modais
│   ├── pages.css           # Estilos por tela
│   └── animations.css      # Transições, loaders
│
├── js/
│   ├── utils/
│   │   ├── constants.js    # Enums, configurações
│   │   ├── helpers.js      # Funções utilitárias
│   │   ├── formatters.js   # Formatação moeda/data
│   │   ├── validators.js   # Validação formulários
│   │   └── calculations.js # Fórmulas matemáticas
│   │
│   ├── services/
│   │   ├── db.js           # IndexedDB service
│   │   ├── storage.js      # localStorage wrapper
│   │   ├── export-import.js # Backup/restore JSON
│   │   └── cache.js        # Cache API
│   │
│   ├── modules/
│   │   ├── app.js          # Inicialização
│   │   ├── router.js       # Hash router SPA
│   │   ├── ui.js           # Componentes reutilizáveis
│   │   ├── modal.js        # Sistema de modais
│   │   ├── toast.js        # Notificações
│   │   └── navbar.js       # Menu inferior/side
│   │
│   └── pages/
│       ├── dashboard.js    # Dashboard com gráficos
│       ├── materiais.js   # Cadastro de materiais
│       ├── insumos.js     # Cadastro de insumos
│       ├── equipamentos.js # Ferramentas
│       ├── faca.js        # Cálculo de faca
│       ├── historico.js   # Histórico
│       └── configuracoes.js # Configurações
│
├── data/
│   └── default-data.json  # Dados demo
│
└── docs/
    ├── estrutura.md       # Esta documentação
    ├── funcionalidades.md # Features
    └── api-indexeddb.md   # Schema do banco
```

## Padrões de Arquitetura

### 1. Module Pattern
Cada arquivo JS é um módulo independente:
```javascript
const NomeModulo = {
    data: {},
    render() {},
    init() {},
    // métodos privados
};
```

### 2. Service Layer
Serviços isolam acesso a dados:
- `Database` → IndexedDB
- `AppStorage` → localStorage
- `BackupService` → Import/Export
- `CacheService` → Cache API

### 3. Componentes Reutilizáveis
`UI.js` fornece funções puras para renderização:
- `UI.itemCard()` → Card genérico
- `UI.facaCard()` → Card de faca
- `UI.statCard()` → Card de estatística
- `UI.formGroup()` → Grupo de formulário

### 4. Hash Router
Navegação SPA via `window.location.hash`:
```
#/dashboard
#/faca
#/historico?view=123
```

## Fluxo de Dados

```
User Input → Page Module → Database Service → IndexedDB
                ↓
         UI Module (render)
                ↓
         DOM Update
```

## Ciclo de Vida da Página

1. **Router.navigate('page')** → Muda hash
2. **Router.handleRoute()** → Detecta mudança
3. **Page.render()** → Retorna HTML string
4. **DOM injection** → Insere no #main-content
5. **Page.init()** → Ativa listeners/eventos

## Offline Strategy

- **Cache First** para assets estáticos
- **IndexedDB** para dados do usuário
- **Service Worker** intercepta fetch
- Nenhuma chamada de API externa obrigatória

## Segurança

- Todos os dados ficam no dispositivo do usuário
- Backup via exportação manual de JSON
- Sem autenticação, sem servidor, sem rastreamento
