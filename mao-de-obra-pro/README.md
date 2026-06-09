# Mão de Obra PRO

Sistema de orçamentos para profissionais autônomos. PWA instalável, funciona 100% offline.

## ✅ Tecnologias

- HTML + CSS + JavaScript puro (sem build, sem npm)
- Bootstrap 5.3 via CDN
- Bootstrap Icons via CDN
- IndexedDB nativo (sem Dexie)
- jsPDF via CDN (carregado só quando gera PDF)
- PWA com Service Worker

---

## 🚀 Deploy no GitHub Pages

### 1. Criar o repositório

1. Acesse [github.com](https://github.com) e faça login
2. Clique em **New repository**
3. Nome: `mao-de-obra-pro` (ou qualquer nome)
4. Marque **Public**
5. Clique **Create repository**

### 2. Subir os arquivos

**Opção A — via interface web (mais fácil):**

1. Na página do repositório, clique em **uploading an existing file**
2. Arraste todos os arquivos e pastas do ZIP
3. Clique **Commit changes**

**Opção B — via git:**

```bash
git init
git add .
git commit -m "primeiro commit"
git branch -M main
git remote add origin https://github.com/SEU_USUARIO/mao-de-obra-pro.git
git push -u origin main
```

### 3. Ativar o GitHub Pages

1. No repositório, vá em **Settings** → **Pages**
2. Em **Branch**, selecione `main` e a pasta `/ (root)`
3. Clique **Save**
4. Aguarde ~2 minutos e acesse a URL fornecida

A URL será algo como: `https://app.ogrosystemas.com.br/mao-de-obra-pro/`

---

## 📱 Instalar como app no celular

### Android (Chrome)
1. Acesse a URL no Chrome
2. Toque no menu (⋮) → **Adicionar à tela inicial**
3. Confirme → app instalado!

### iOS (Safari)
1. Acesse a URL no Safari
2. Toque no botão de compartilhar (□↑)
3. Role e toque **Adicionar à Tela de Início**
4. Confirme → app instalado!

---

## ⚠️ Atenção ao usar GitHub Pages

O GitHub Pages serve os arquivos de um subdiretório (`/mao-de-obra-pro/`), não da raiz.
Se a URL do seu app for `https://usuario.github.io/mao-de-obra-pro/`, você precisa ajustar
dois arquivos:

**`sw.js`** — troque os caminhos:
```js
// De:
const PRECACHE = ['/', '/index.html', ...]
// Para:
const PRECACHE = ['/mao-de-obra-pro/', '/mao-de-obra-pro/index.html', ...]
```

**`manifest.json`** — ajuste o `start_url`:
```json
"start_url": "/mao-de-obra-pro/"
```

**Alternativa mais simples:** Use um domínio customizado no GitHub Pages ou crie
um repositório com o nome exato `SEU_USUARIO.github.io` — aí fica na raiz.

---

## 🏗️ Estrutura

```
mao-de-obra-pro/
  index.html          ← shell do app
  manifest.json       ← configuração PWA
  sw.js               ← service worker (offline)
  css/
    app.css           ← estilos
  js/
    app.js            ← bootstrap e estado global
    db.js             ← IndexedDB (banco de dados local)
    calculadora.js    ← lógica de precificação
    router.js         ← navegação SPA
  pages/
    setup.js          ← configuração inicial
    dashboard.js      ← tela principal
    clientes.js       ← gestão de clientes
    catalogo.js       ← catálogo de serviços
    orcamento.js      ← criação de orçamentos
    visualizar.js     ← visualização + PDF + WhatsApp
    configuracoes.js  ← configurações financeiras
  icons/
    icon-192.png
    icon-512.png
```

---

## 💡 Funcionalidades

- ✅ Setup inicial com seleção de profissões
- ✅ Múltiplas profissões ativas (eletricista, encanador, etc)
- ✅ Catálogo de serviços por profissão
- ✅ Criação de orçamentos em 4 passos
- ✅ Cálculo automático por tempo + dificuldade
- ✅ Preços fixos para serviços tabelados
- ✅ Fotos do serviço no orçamento
- ✅ Desconto por valor ou percentual
- ✅ Envio por WhatsApp (texto formatado)
- ✅ Geração de PDF profissional
- ✅ Gestão de clientes com busca
- ✅ Dashboard com estatísticas
- ✅ Funciona 100% offline após primeira visita
- ✅ Dados armazenados no dispositivo (IndexedDB)
