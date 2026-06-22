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
- pdf-lib + @pdf-lib/fontkit — geração de relatórios em PDF no navegador
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

Na primeira execução, se o banco estiver vazio, o app popula automaticamente 5 membros
fictícios com cenários distintos (em dia, ingresso no meio do ano, inadimplência no ciclo
atual, inadimplência multi-ano, e um membro afastado) — ver `src/db/seed.ts`. Para resetar,
basta limpar o IndexedDB do navegador (Application > IndexedDB > mutantes-mc-db) ou
desinstalar o PWA.

## Estrutura

```
src/
├── assets/
│   └── fonts/      # Roboto-Regular.ttf (licença SIL OFL) — usada na geração de PDF
├── components/
│   ├── ui/         # Badge, Button, Modal, EmptyState, ConfirmDialog
│   ├── dashboard/  # cards de resumo do mês
│   ├── members/    # lista, item, cadastro/edição, histórico, negociação, ações, edição de pagamento
│   ├── settings/   # configurações do clube, relatórios, backup/restauração
│   ├── pwa/        # botão de instalar, banner de atualização
│   └── layout/     # header, seletor de mês
├── db/             # Dexie: schema, seed, backup/restauração
├── hooks/          # lógica de dados (CRUD, cálculo de inadimplência, resumo, relatório, backup)
├── types/          # Membro, Pagamento, ConfigClube
├── utils/          # datas/competências, moeda, cálculo de status, relatório, geração de PDF
└── constants/
```

## Regras de negócio implementadas

- Mensalidade de valor único, configurável em Configurações (`ConfigClube.valorMensalidade`).
  Alterar o valor não retroage em pagamentos já registrados — cada `Pagamento` guarda seu
  próprio `valorPago`, congelado no momento da baixa.
- **Ciclo de cobrança anual (Janeiro-Dezembro do ano corrente)**: todo membro ativo é cobrado
  desde Janeiro até o mês atual, independentemente de há quanto tempo está no clube. A data de
  ingresso só desloca o início da cobrança dentro do próprio ano de ingresso (quem entrou em
  Abril não deve Jan-Mar daquele ano). Ver `src/utils/status.utils.ts`.
- **Histórico multi-ano**: dívidas de anos anteriores continuam visíveis separadamente — virar
  o ano não "zera" nem mistura competências de anos diferentes.
- **Afastamento** (`Membro.status === "afastado"`): a partir da competência registrada em
  `Membro.competenciaAfastamento`, o membro para de gerar pendências novas, mas qualquer
  dívida anterior ao afastamento é mantida (não é perdoada). O membro continua visível na
  lista — se ainda houver dívida residual, o badge mostra "Afastado · Deve N mês(es)" e os
  botões de regularização (Dar Baixa/Negociar) continuam disponíveis para essa dívida
  específica. Reativar o membro volta a contar normalmente a partir do mês da reativação —
  o período afastado nunca retroage como dívida.
- Status de cada competência é **derivado**, não armazenado: um mês é "pendente" se está
  dentro do ciclo do ano de referência (e antes de um eventual afastamento) e não há
  `Pagamento` registrado para ele.
- Lista mostra inadimplência acumulada ("Pendente (N meses)") quando há mais de 1 mês em aberto.
- Botão **Dar Baixa**: baixa rápida de 1 clique, sempre na competência PENDENTE real do
  membro (não na competência selecionada no seletor do topo) — importante para membros
  afastados, cuja única dívida pode ser de um mês diferente do mês em exibição.
- Botão **Negociar** (aparece quando há 2+ meses pendentes): abre modal para selecionar
  quais competências estão sendo quitadas agora, soma os valores e baixa todas em lote.
- **"Arrecadado" é uma métrica de caixa, não de competência**: soma o valor de TODOS os
  pagamentos cuja `dataPagamento` cai no mês/ano selecionado no topo, independentemente de
  qual competência (mês cobrado) cada pagamento se refere. Uma negociação que quita 2 meses
  de uma vez soma o valor total (ex: R$100) inteiro no caixa do mês em que foi feita — nunca
  "divide" parte do valor para o mês da competência antiga. Ver `useDashboardResumo.ts`.
- Menu de **ações do membro** (ícone de 3 pontos na lista): editar nome/apelido, afastar ou
  reativar, e excluir definitivamente (cadastro + todo o histórico de pagamentos, com
  confirmação explícita antes de executar).
- **Edição e estorno de pagamento**: no histórico do membro, qualquer linha já paga é
  clicável e abre um modal para corrigir valor/data/forma de pagamento, ou estornar
  (excluir) o registro por completo — a competência volta a ficar pendente. Ver
  `EditPaymentModal.tsx` e `usePagamentos.editarPagamento`/`removerBaixa`.

## Backup e restauração

Em Configurações → Backup e restauração:

- **Exportar backup**: gera um arquivo `.json` com todos os membros, pagamentos e a
  configuração do clube, e dispara o download no navegador.
- **Importar backup**: lê um arquivo `.json` exportado anteriormente (do mesmo dispositivo
  ou de outro celular) e **mescla** com os dados já existentes — nunca substitui ou apaga
  nada. Deduplicação: um membro é considerado "já existente" se já houver um membro com o
  mesmo par (nome, apelido); um pagamento é considerado "já existente" se já houver um
  pagamento do mesmo membro para a mesma competência. Membros novos do arquivo recebem um
  ID gerado localmente — nunca reaproveita o ID do arquivo, que poderia colidir com IDs já
  usados localmente. Ver `src/db/backup.ts`.

## Relatórios em PDF

Em Configurações → Relatórios → Gerar relatório em PDF. Três tipos de filtro disponíveis:

- **Mês**: situação de um único mês (ex: Junho/2026).
- **Período**: intervalo customizado entre duas competências (ex: Março/2026 a Maio/2026).
- **Ano**: ano completo (Janeiro a Dezembro).

O relatório lista cada membro com seu status (Em dia / Pendente — com quantidade de meses
devidos / Afastado), e o valor total arrecadado no período (mesma métrica de "caixa" do
dashboard: soma pagamentos pela `dataPagamento`, não pela competência paga). Importante: o
cálculo de status nunca considera meses **futuros** como pendência — um relatório anual
gerado em Junho não acusa Julho-Dezembro como dívida, mesmo que o filtro vá até Dezembro.

A geração do PDF acontece inteiramente no navegador (`pdf-lib` + `@pdf-lib/fontkit`), sem
nenhum servidor envolvido. Usa uma fonte TrueType embutida (Roboto, licença SIL OFL — ver
`src/assets/fonts/OFL-Roboto.txt`) em vez das fontes padrão do pdf-lib, que não suportam
acentos do português. `pdf-lib` e `fontkit` são carregados via `import()` dinâmico — só
entram no bundle quando o usuário de fato pede um relatório, mantendo o carregamento normal
do app leve.

## Instalação e atualização do PWA

- **Botão "Instalar"** no header (`InstallAppButton.tsx`): captura o evento nativo
  `beforeinstallprompt` do navegador e expõe um botão sempre visível dentro do próprio app,
  em vez de depender só da heurística do Chrome para mostrar (ou não) seu prompt automático
  no menu. O botão desaparece sozinho depois que o app é instalado, ou se já estiver rodando
  em modo standalone.
- **Banner de atualização** (`UpdateBanner.tsx`): quando uma nova versão é publicada, mostra
  "Nova versão disponível" com uma barra de progresso de 10 segundos. O usuário pode clicar em
  "Atualizar" para aplicar na hora, ou não fazer nada — a atualização é aplicada
  automaticamente quando a barra zera, e uma confirmação rápida "App atualizado" aparece após
  o reload. Inclui verificação periódica (a cada hora) por novas versões enquanto o app está
  aberto, e um fallback de `window.location.reload()` como segurança caso o sinal interno do
  Service Worker não dispare o reload por conta própria.
- O Service Worker é registrado explicitamente via `useRegisterSW` (de
  `virtual:pwa-register/react`), com `registerType: "prompt"` no `vite.config.ts` — ou seja,
  uma versão nova fica esperando até a UI decidir aplicá-la, nunca substitui a versão em uso
  silenciosamente.
