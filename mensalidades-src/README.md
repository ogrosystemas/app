# Mutantes Moto Clube — App de Conferência de Mensalidades

PWA offline-first, com dados sincronizados na nuvem, para controle de mensalidades fixas
do clube: cadastro de membros, conferência rápida de quem está em dia/pendente, baixa de
pagamentos e negociação de inadimplência acumulada. Acesso restrito a contas Google
autorizadas — ver seção "Autenticação e autorização" abaixo.

> **Deploy:** este projeto é publicado em `app.ogrosystemas.com.br/mensalidades/` via
> GitHub Pages (repositório `app`, subpasta `mensalidades/`). Veja `../COMO-PUBLICAR.md`
> para o passo a passo de publicação. O caminho `/mensalidades/` já está configurado em
> `vite.config.ts` (constante `BASE_PATH`) — mude ali se a subpasta de destino mudar.

## Stack

- React + TypeScript (strict, sem `any`) + Vite
- Tailwind CSS (tema dark "moto clube": grafite/preto + laranja/vermelho)
- **Firebase** — Authentication (login Google) + Firestore (banco de dados na nuvem, com
  cache local persistente via IndexedDB nativo do SDK — funciona offline e sincroniza
  automaticamente quando a conexão volta)
- vite-plugin-pwa — Service Worker com cache do app shell
- pdf-lib + @pdf-lib/fontkit — geração de relatórios em PDF no navegador
- qrcode.react — geração de QR Code Pix (payload BR Code montado localmente, sem API externa)
- lucide-react — ícones

## Como rodar

```bash
cp .env.example .env   # preencha com as chaves do projeto Firebase (ver README do Firebase)
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

## Autenticação e autorização

O app exige login com Google antes de mostrar qualquer dado. Existem DOIS níveis de
acesso, bem diferentes entre si — login (qualquer conta Google) e autorização (o que
aquela conta pode ver) são coisas separadas:

### Administrador

Lista fixa de e-mails mantida na função `emailAutorizado()` em `firestore.rules` — vê e
edita tudo (membros, pagamentos, configurações, relatórios, backup). Para adicionar ou
remover um administrador: edite essa função e publique as regras de novo no Firebase
Console (Firestore Database → Regras → colar o conteúdo do arquivo → Publicar).

### Integrante comum (consulta restrita)

Qualquer membro cadastrado pode, opcionalmente, ter um e-mail vinculado (campo "E-mail
de acesso" no formulário de cadastro/edição, em `MemberFormModal.tsx`). Quem logar com
esse e-mail vê uma tela própria (`MemberSelfView.tsx`), somente leitura:

- Status atual (Em Dia/Pendente, sem valores em R$), sempre baseado no mês real de hoje.
- Histórico completo (mês a mês, pago/pendente), também sem valores.
- Botão "Vou pagar [competência]" em meses pendentes — envia um aviso informal, visível
  só para o administrador (ícone de sino na lista principal), sem alterar nenhum status
  real. O aviso é removido automaticamente quando a baixa real é registrada.

Esse vínculo é gerenciado inteiramente dentro do app — não exige editar `firestore.rules`
para cada membro novo. Por baixo dos panos, o app espelha o vínculo num documento em
`acessos/{email}` (coleção na raiz do banco, fora do caminho do clube), que as regras de
segurança consultam para restringir a leitura desse e-mail a apenas aquele membro
específico — nunca à lista inteira nem aos dados de outros membros.

Quem loga mas não está em nenhuma das duas listas vê a tela "Acesso não autorizado"
(`AccessDeniedScreen.tsx`).

Todos os dados (membros, pagamentos, configuração, avisos) vivem num único documento fixo
do clube no Firestore (`clubes/mutantes-mc`) — não há suporte a múltiplos clubes.

### Dois bugs reais já corrigidos aqui (não repetir)

1. **Funções de regra precisam estar DENTRO do bloco `service`/`match`.** As funções
   `temAcessoDeIntegranteQualquer()` e `ehOProprioMembroVinculado()` usam a variável
   `$(database)`, que só existe dentro de `match /databases/{database}/documents { ... }`.
   Funções herdam o escopo de onde são *definidas*, não de onde são chamadas — definir
   essas funções no nível mais externo do arquivo (fora do `service cloud.firestore`) fazia
   qualquer `get()`/`exists()` dentro delas falhar silenciosamente. Foi exatamente esse bug
   que causou "Acesso não autorizado" para integrantes vinculados corretamente. As funções
   já estão no lugar certo no `firestore.rules` atual — não as mova para fora de novo.

2. **`verificarAcessoAdmin()` (via `getDocs` em LISTA), não `getDoc` de documento único, é
   o teste correto de "é administrador?".** Em `App.tsx`, o teste de admin não pode usar
   `initDatabase()`/`getDoc(refClube())` como critério: a regra de leitura da config também
   libera integrantes comuns (eles precisam ler o valor da mensalidade para calcular o
   próprio status). Usar essa leitura como teste de admin fazia qualquer integrante vinculado
   cair erroneamente no `MainApp` (visão administrativa completa, travada em loading
   infinito por falta de outras permissões). O teste correto explora uma propriedade da
   regra de `list`: ela só passa se *todo* documento do resultado satisfizer a condição —
   um integrante, vinculado a só um `membroId`, nunca passa esse teste para a coleção
   inteira de membros, mas um administrador sempre passa.

## Patentes

A hierarquia de patentes/cargos do clube é definida em `src/constants/patentes.constants.ts`
(`PATENTES_EM_ORDEM`), em ordem do cargo mais alto para o mais comum. Essa lista alimenta o
seletor do formulário de cadastro e o critério de desempate na ordenação da lista de membros
(ver "Regras de negócio" abaixo).

## Estrutura

```
src/
├── assets/
│   └── fonts/      # Roboto-Regular.ttf (licença SIL OFL) — usada na geração de PDF
├── components/
│   ├── auth/       # tela de login, tela de acesso negado
│   ├── self/       # área restrita do integrante comum (somente leitura + aviso de pagamento)
│   ├── ui/         # Badge, Button, Modal, EmptyState, ConfirmDialog
│   ├── dashboard/  # cards de resumo do mês
│   ├── members/    # lista, item, cadastro/edição, histórico, negociação, ações, edição de pagamento
│   ├── settings/   # configurações do clube, relatórios, backup/restauração
│   ├── pwa/        # botão de instalar, banner de atualização
│   └── layout/     # header, seletor de mês
├── firebase/       # inicialização do Firebase (app, auth, firestore com cache offline)
├── db/             # referências do Firestore (refs.ts), inicialização (db.ts), backup/restauração
├── hooks/          # lógica de dados (CRUD, autenticação, acesso de integrante, avisos, inadimplência, relatório, backup)
├── types/          # Membro, Pagamento, ConfigClube, AvisoPagamento
├── utils/          # datas/competências, moeda, cálculo de status, relatório, geração de PDF
└── constants/      # patentes, tema/valores padrão
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
- **Patente**: cada membro tem uma patente fixa (lista em `PATENTES_EM_ORDEM`), exibida ao
  lado do apelido na lista (ex: "FOICE · Presidente"). A ordenação da lista prioriza sempre
  pendência (quem deve mais aparece primeiro — nunca esconde inadimplência), com patente
  mais alta como critério de desempate, e apelido (A-Z) como desempate final.
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
- **Avisos informais de pagamento**: um integrante com acesso vinculado (ver
  "Autenticação e autorização") pode marcar "Vou pagar [competência]" na própria área de
  consulta. Isso não altera nenhum cálculo de status — é só um lembrete visível para o
  administrador (ícone de sino ao lado do badge, na lista principal). O aviso é removido
  automaticamente quando a baixa real daquela competência é registrada. Ver
  `useAvisos.ts`.

## Cobrança via Pix

Tanto o administrador (ícone de QR Code na lista principal) quanto o próprio integrante
(botão de QR Code ao lado de cada competência pendente, na área de consulta restrita)
podem gerar um Pix dinâmico já com valor, chave e identificação preenchidos — quem for
pagar só precisa escanear o QR Code ou copiar o código no app do banco, sem digitar nada.

Pontos importantes:

- **Gerado 100% no navegador**, sem nenhuma API externa de pagamento: o "Pix Copia e
  Cola" é apenas um texto estruturado no padrão BR Code (EMV QRCPS) do Banco Central — ver
  `src/utils/pix.utils.ts`. A implementação do CRC16 (checksum final do payload) foi
  validada contra o valor de referência oficial do CRC16-CCITT-FALSE (a string
  `"123456789"` deve produzir `0xE5CC`) e contra um ciclo completo de geração → QR Code →
  decodificação, confirmando que o conteúdo lido de volta é idêntico ao original.
- **Não há confirmação automática de pagamento.** Isso é um Pix estático/dinâmico simples
  (sem integração com a API do Banco Central), então o app nunca sabe se o Pix foi pago —
  a baixa no sistema continua sendo manual, feita pelo administrador depois de confirmar o
  recebimento na própria conta bancária (ver `EditPaymentModal`/`usePagamentos.darBaixa`).
- **Valor**: o integrante só gera Pix do valor de 1 mensalidade (a competência pendente
  específica que ele está vendo); o administrador pode gerar Pix de qualquer valor (útil
  para cobranças negociadas de múltiplos meses de uma vez).
- A chave Pix, nome do recebedor e cidade são fixos em
  `src/constants/theme.constants.ts` (`PIX_CHAVE`, `PIX_NOME_RECEBEDOR`, `PIX_CIDADE`) —
  diferente do nome do clube e valor da mensalidade, não são editáveis pelo app, já que
  envolvem dados bancários reais.

## Backup e restauração

Em Configurações → Backup e restauração:

- **Exportar backup**: gera um arquivo `.json` com todos os membros, pagamentos e a
  configuração do clube, e dispara o download no navegador.
- **Importar backup**: lê um arquivo `.json` exportado anteriormente (do mesmo dispositivo
  ou de outro celular) e **mescla** com os dados já existentes — nunca substitui ou apaga
  nada. Deduplicação: um membro é considerado "já existente" se já houver um membro com o
  mesmo par (nome, apelido); um pagamento é considerado "já existente" via o mesmo esquema
  de ID determinístico (`membroId_ano_mes`) usado por `usePagamentos`. Membros novos do
  arquivo recebem um novo ID de documento gerado pelo Firestore — nunca reaproveita o ID
  do arquivo, que pode ter vindo de outro dispositivo/sessão. Ver `src/db/backup.ts`.

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
