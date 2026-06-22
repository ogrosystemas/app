# Mutantes Moto Clube — App de Conferência de Mensalidades

PWA offline-first para controle de mensalidades fixas do clube: cadastro de membros,
conferência rápida de quem está em dia/pendente, baixa de pagamentos e negociação de
inadimplência acumulada.

> **Deploy:** este projeto é publicado em `app.ogrosystemas.com.br/mensalidades/` via
> GitHub Pages (repositório `app`, subpasta `mensalidades/`). Veja `../COMO-PUBLICAR.md`
> para o passo a passo de publicação. O caminho `/mensalidades/` já está configurado em
> `vite.config.ts` (constante `BASE_PATH`) — mude ali se a subpasta de destino mudar.

## Stack

- React + TypeScript (strict, sem `any`) + Vite
- Tailwind CSS (tema dark "moto clube": grafite/preto + laranja/vermelho)
- Dexie.js (IndexedDB) — persistência 100% local/offline
- vite-plugin-pwa — Service Worker com cache do app shell
- lucide-react — ícones

## Como rodar

```bash
npm install
npm run dev       # ambiente de desenvolvimento, http://localhost:5173
```

```bash
npm run build     # build de produção em /dist (inclui sw.js e manifest, já com base /mensalidades/)
npm run preview   # serve o build de produção localmente para testar o PWA offline
```

```bash
npm run lint       # ESLint (zero warnings configurado como meta)
```

## Dados de teste

Na primeira execução, se o banco estiver vazio, o app popula automaticamente 4 membros
fictícios com cenários distintos de pagamento (em dia, ingresso no meio do ano, inadimplência
no ciclo atual, e inadimplência multi-ano) — ver `src/db/seed.ts`. Para resetar, basta limpar
o IndexedDB do navegador (Application > IndexedDB > mutantes-mc-db) ou desinstalar o PWA.

## Estrutura

```
src/
├── components/   # UI (ui/, dashboard/, members/, layout/)
├── db/           # Dexie: schema + seed
├── hooks/        # lógica de dados (CRUD, cálculo de inadimplência, resumo)
├── types/        # Membro, Pagamento, ConfigClube
├── utils/        # datas/competências, moeda, cálculo de status
└── constants/
```

## Regras de negócio implementadas

- Mensalidade de valor único, configurável globalmente (`ConfigClube.valorMensalidade`).
- **Ciclo de cobrança anual (Janeiro-Dezembro do ano corrente)**: todo membro ativo é cobrado
  desde Janeiro até o mês atual, independentemente de há quanto tempo está no clube. A data de
  ingresso só desloca o início da cobrança dentro do próprio ano de ingresso (quem entrou em
  Abril não deve Jan-Mar daquele ano). Ver `src/utils/status.utils.ts`.
- **Histórico multi-ano**: dívidas de anos anteriores continuam visíveis separadamente — virar
  o ano não "zera" nem mistura competências de anos diferentes.
- Status de cada competência é **derivado**, não armazenado: um mês é "pendente" se está
  dentro do ciclo do ano de referência e não há `Pagamento` registrado para ele.
- Lista mostra inadimplência acumulada ("Pendente (N meses)") quando há mais de 1 mês em aberto.
- Botão **Dar Baixa**: baixa rápida de 1 clique, sempre na competência selecionada no topo.
- Botão **Negociar** (aparece quando há 2+ meses pendentes): abre modal para selecionar
  quais competências estão sendo quitadas agora, soma os valores e baixa todas em lote.
