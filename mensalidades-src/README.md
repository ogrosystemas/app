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

## Multi-sede e autenticação/autorização

O Mutantes MC tem MÚLTIPLAS SEDES independentes (matriz + subsedes) — cada uma com seus
próprios membros, pagamentos, configuração e chave Pix, totalmente isoladas das demais.
O app exige login com Google antes de mostrar qualquer dado. Existem TRÊS níveis de
acesso, bem diferentes entre si — login (qualquer conta Google) e autorização (o que
aquela conta pode ver) são coisas separadas:

### Super Admin

Administra TODAS as sedes. Há um único e-mail fixo no código (`ehSuperAdminInicial()` em
`firestore.rules`) que serve como ponto de partida do sistema — a partir dele, mais super
admins (ou tesoureiros de sede) podem ser promovidos editando a coleção
`administradores/{email}` no Firestore (campo `clubeId`: `"*"` para super admin, ou o ID
de uma sede específica para tesoureiro). Depois do login, o Super Admin vê uma tela de
escolha de sede (`SedeSelectionScreen.tsx`), com a opção de criar uma sede nova
(`NewSedeModal.tsx`) — nome, valor inicial da mensalidade, e o e-mail do tesoureiro
responsável, tudo numa única ação.

A sede escolhida é lembrada entre sessões (`localStorage`, ver
`utils/sede-preferencia.utils.ts`): da segunda vez em diante, o app abre direto na última
sede escolhida, sem mostrar a tela de seleção de novo — útil para quem administra sempre
a mesma sede no dia a dia, mesmo sendo Super Admin. Um botão **"Trocar sede"** dentro de
Configurações volta para a tela de escolha quando necessário (ex: para dar suporte a
outra sede pontualmente), sem deslogar da conta Google. Se a sede lembrada for removida
ou o `localStorage` estiver vazio/bloqueado, a tela de escolha aparece normalmente.

### Admin de sede (tesoureiro)

Administra SOMENTE a própria sede — vínculo em `administradores/{email}` com `clubeId`
igual ao ID daquela sede. Entra direto no `MainApp` da própria sede, sem nenhuma tela de
escolha. Vê e edita tudo dentro dela (membros, pagamentos, configurações, relatórios,
backup, Pix) — nunca dados de outra sede.

### Integrante comum (consulta restrita)

Qualquer membro cadastrado pode, opcionalmente, ter um e-mail vinculado (campo "E-mail
de acesso" no formulário de cadastro/edição, em `MemberFormModal.tsx`). Quem logar com
esse e-mail vê uma tela própria (`MemberSelfView.tsx`), somente leitura, restrita à sede
daquele membro:

- Status atual (Em Dia/Pendente, sem valores em R$), sempre baseado no mês real de hoje.
- Histórico completo (mês a mês, pago/pendente), também sem valores.
- Botão "Vou pagar [competência]" em meses pendentes — envia um aviso informal, visível
  só para o administrador daquela sede (ícone de sino na lista principal), sem alterar
  nenhum status real. O aviso é removido automaticamente quando a baixa real é registrada.
- Botão de QR Code Pix por competência pendente — gera a cobrança com a chave Pix
  **daquela sede específica** (nunca de outra).

Esse vínculo é gerenciado inteiramente dentro do app — não exige editar `firestore.rules`
para cada membro novo. Por baixo dos panos, o app espelha o vínculo num documento em
`acessos/{email}` (coleção na raiz do banco, contendo `clubeId` + `membroId`), que as
regras de segurança consultam para restringir a leitura desse e-mail a apenas aquele
membro específico, dentro daquela sede específica — nunca à lista inteira, nunca a outra
sede.

Quem loga mas não está vinculado a nenhuma sede de nenhuma das três formas vê a tela
"Acesso não autorizado" (`AccessDeniedScreen.tsx`).

### Estrutura de dados no Firestore

```
sedes/{clubeId}                       (metadados: nome, tipo "matriz"|"subsede", data de
                                        criação — usado pela tela de Super Admin para
                                        listar/criar sedes, e pelo badge no header do app)
clubes/{clubeId}                      (config da sede: nome, valor da mensalidade, Pix)
clubes/{clubeId}/membros/{id}
clubes/{clubeId}/pagamentos/{id}
clubes/{clubeId}/avisos/{id}
administradores/{email}               -> { clubeId }   ("*" para super admin)
acessos/{email}                       -> { clubeId, membroId }
```

A leitura de `sedes/{clubeId}` (documento único, nunca a coleção inteira em lista) é
permitida para qualquer um com acesso àquela sede específica — administrador dela ou
integrante vinculado a ela — não só super admin, já que o header do app (badge
Matriz/Subsede) precisa disso em qualquer tela. A criação/edição/exclusão de sedes
continua exclusiva de super admin. Sedes criadas antes da introdução do campo `tipo`
(ex: a sede original de Itajaí, migrada do clube fixo anterior) não têm esse campo —
o badge simplesmente não aparece até ele ser adicionado manualmente no Firestore
Console (`sedes/{clubeId}` → adicionar campo `tipo`, tipo string, valor `"matriz"` ou
`"subsede"`).

### Bugs reais já corrigidos aqui (não repetir)

1. **Funções de regra precisam estar DENTRO do bloco `service`/`match`.** Várias funções
   usam a variável `$(database)`, que só existe dentro de
   `match /databases/{database}/documents { ... }`. Funções herdam o escopo de onde são
   *definidas*, não de onde são chamadas — definir essas funções no nível mais externo do
   arquivo (fora do `service cloud.firestore`) fazia qualquer `get()`/`exists()` dentro
   delas falhar silenciosamente. Foi exatamente esse bug que causou "Acesso não
   autorizado" para integrantes vinculados corretamente, numa versão anterior. As funções
   já estão no lugar certo no `firestore.rules` atual — não as mova para fora de novo.

2. **`verificarAcessoAdmin()` lê `administradores/{email}` diretamente — não usa mais
   `getDocs` em lista como teste indireto.** Numa versão anterior (antes do modelo
   multi-sede), o teste de "é admin?" explorava uma propriedade de `list` (só passa se
   *todo* documento do resultado satisfizer a regra). Isso parou de ser necessário assim
   que existe um documento explícito de vínculo (`administradores/{email}`) — ler esse
   vínculo diretamente é mais simples e explícito, e já diz de cara se é super admin ou
   admin de qual sede específica.

3. **`useAcessoMembro` nunca pode retornar "não vinculado" sem antes ter feito uma
   tentativa real de leitura.** Causou "Acesso não autorizado" de forma INTERMITENTE
   (funcionava na maioria das vezes, falhava ocasionalmente, principalmente logo depois
   de reabrir o app) para um integrante com tudo corretamente cadastrado — confirmado com
   3 testes diretos no Simulador de Regras (administradores, acessos, membros, todos
   "Permitido"), o que provou que a causa não estava nas regras nem nos dados.

   A causa real: o hook recebia `email: string | null` do `App.tsx`, e quando `email`
   era `null` (o chamador ainda não tinha decidido tentar essa verificação), retornava
   IMEDIATAMENTE `{ status: "nao-vinculado" }` — um resultado FINAL, sem nenhuma
   tentativa de leitura ter ocorrido. Isso colide com uma particularidade documentada do
   próprio SDK do Firebase Auth
   ([firebase-js-sdk#7049](https://github.com/firebase/firebase-js-sdk/issues/7049)):
   `onAuthStateChanged` pode disparar mais de uma vez em sequência rápida ao reabrir o
   app. Entre um disparo e outro, havia uma janela onde o `App.tsx` já tinha o e-mail
   disponível e mudava para `estado.tipo === "tentando-integrante"`, mas o resultado do
   `useAcessoMembro` ainda refletia o "não vinculado" instantâneo de um render anterior
   (quando `email` era `null`) — fazendo `AccessDeniedScreen` aparecer por engano, antes
   da verificação real ter qualquer chance de rodar.

   A correção (`useAcessoMembro.ts`): quando `email` é `null`, o hook agora retorna
   `"verificando"` — nunca um resultado definitivo sem checagem real. Além disso, um
   `ref` rastreia qual foi a verificação mais RECENTEMENTE disparada (não só a mais
   recente a terminar), descartando resultados obsoletos de chamadas antigas que
   terminam depois de uma chamada mais nova já ter sido disparada — mais robusto que uma
   simples flag `cancelado` por execução do efeito.

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
- Botão **Negociar** (aparece quando há 2+ meses pendentes): abre modal com uma grade dos
  12 meses do ano corrente, cada um com status visual (pendente = vermelho, já pago = verde
  e bloqueado, fora do período do membro = cinza e bloqueado, futuro/ainda não vencido =
  laranja "adiantado") — o tesoureiro pode combinar livremente dívida real (meses pendentes)
  com pagamento adiantado (meses futuros do mesmo ano) na mesma negociação. Pré-seleciona
  automaticamente todos os meses pendentes; meses futuros começam desmarcados (adiantar é
  uma escolha extra, não o caso padrão). Soma os valores e baixa todas as competências
  selecionadas em lote. Inclui um botão **"Gerar Pix deste total"**, que abre o QR Code de
  cobrança (ver "Cobrança via Pix" abaixo) com o valor somado de tudo que foi selecionado —
  útil para mandar uma cobrança consolidada em vez do membro pagar mês a mês. Ver
  `NegotiationModal.tsx` e `utils/status.utils.ts` (`gerarMesesDoAnoParaNegociacao`).
- Botão **Adiantar** (aparece quando o membro está Em Dia, sem nenhuma pendência): abre o
  mesmo modal de Negociação — como não há mês pendente, a grade naturalmente só oferece os
  meses futuros disponíveis (o título do modal também se ajusta automaticamente para
  "Adiantar" em vez de "Negociar" quando não há nenhuma dívida real envolvida).
- **Adiantamento pelo próprio integrante** (área de autoconsulta, `MemberSelfView`): um
  botão "Adiantar Mensalidades" abre uma grade só com os meses futuros do ano corrente,
  permitindo ao membro gerar o próprio Pix com o valor somado de quantos meses quiser
  adiantar — mas SEM nenhuma escrita no banco: o integrante nunca registra a própria baixa,
  apenas gera o código para pagar. A baixa de fato continua sendo feita pelo tesoureiro,
  depois de confirmar o recebimento — mesmo modelo de confiança usado no resto do app (o
  integrante nunca consegue se autodeclarar "pago"). Ver `AdvancePaymentModal.tsx`.
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

✅ **Status: testado e confirmado com pagamento real** (escaneado e aceito no C6, depois
da correção do CRC16 — ver "Bugs reais já corrigidos" abaixo para o histórico completo da
investigação).

Tanto o administrador (ícone de QR Code na lista principal) quanto o próprio integrante
(botão de QR Code ao lado de cada competência pendente, na área de consulta restrita)
podem gerar um Pix dinâmico já com valor, chave e identificação preenchidos — quem for
pagar só precisa escanear o QR Code ou copiar o código no app do banco, sem digitar nada.

Pontos importantes:

- **Gerado 100% no navegador**, sem nenhuma API externa de pagamento: o "Pix Copia e
  Cola" é apenas um texto estruturado no padrão BR Code (EMV QRCPS) do Banco Central — ver
  `src/utils/pix.utils.ts`. A implementação do CRC16 foi validada byte-a-byte contra dois
  payloads reais gerados por PSPs diferentes (app da Caixa e site do BB) — ver o histórico
  de bugs abaixo antes de tocar nesse arquivo de novo.
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

### Bugs reais já corrigidos aqui (não repetir)

**1. Chave de telefone exige o formato internacional `+55DDDNNNNNNNNN` dentro do QR Code.**
Mesmo que você só digite o DDD+número ao *cadastrar* a chave no app do banco, o Manual
Operacional do DICT (Banco Central) exige que, dentro do *payload* do BR Code, uma chave
de telefone venha com `+55` na frente. Usar o número "puro" (sem o `+55`) gerava um QR Code
estruturalmente válido (passava por toda a validação de CRC e decodificação TLV), mas o
app do banco rejeitava a chave silenciosamente — confirmado na prática com o C6, que
retornava "não foi possível completar a solicitação" antes mesmo de mostrar os dados da
cobrança. `PIX_CHAVE` já está no formato correto (`+5547996018551`) — não remova o `+55`
se for trocar a chave do clube no futuro.

**2. O GUI do campo Merchant Account Information (subcampo `00` dentro do campo `26`) deve
ser `br.gov.bcb.pix` em MINÚSCULAS, exatamente como no exemplo oficial do Manual de Padrões
para Iniciação do Pix.** Usar `BR.GOV.BCB.PIX` em maiúsculas também gera um payload
estruturalmente válido — vários bancos aceitam por fazerem comparação case-insensitive —
mas pelo menos o Banco do Brasil rejeitou com "Parâmetros inválidos" antes de mostrar
qualquer dado da cobrança, confirmado em teste real. Ver `gerarPayloadPix` em
`pix.utils.ts`, que já usa o valor correto.

**3. ⚠️ CAUSA RAIZ REAL — o algoritmo de CRC16 estava matematicamente incorreto, por
causa de uma referência de teste errada.** Esta foi a causa raiz de fato por trás de
TODAS as rejeições ("Parâmetros inválidos" no BB, "Ocorreu um erro" no C6 e Bradesco) —
as correções 1 e 2 acima eram válidas, mas insuficientes; uma 3ª tentativa (já revertida)
chegou a trocar espaço por underscore no nome do recebedor, baseada num único payload
copiado do site do BB, e essa generalização estava errada.

O algoritmo de CRC16 usado pelo Pix é o **CRC-16/IBM-3740** (polinômio `0x1021`, init
`0xFFFF`, sem reflexão, sem XOR final) — mas esse algoritmo é frequentemente confundido
de nome com "CRC-CCITT" ou "CRC-CCITT-FALSE" na documentação de mercado, levando a
implementações que processam 2 bytes extras de valor zero ("augment") ao final do
cálculo. Essas implementações erradas produzem `0xE5CC` para a string de teste
`"123456789"` — um valor que circula amplamente na internet como se fosse a referência
"correta". **Não é.** O catálogo oficial de algoritmos CRC
([reveng.sourceforge.io/crc-catalogue](https://reveng.sourceforge.io/crc-catalogue/16.htm))
confirma que o valor de teste correto para essa string é `0x29B1` — o mesmo valor que a
implementação errada (com augment) rejeitava como "claramente equivocado".

Essa implementação errada gerava QR Codes **estruturalmente válidos** (autoconsistentes:
o CRC errado batia com o resto do payload errado, então passava até por validadores de
terceiros que só checam autoconsistência, como a Kobana) — mas o valor não correspondia
ao que qualquer app de banco esperava, causando rejeição sem nenhuma pista sobre qual
campo estava errado, já que estruturalmente nada estava.

**Como foi descoberto, de fato**: comparando o payload do nosso app contra um Pix real
gerado pelo APP OFICIAL DA CAIXA (o PSP onde a chave está de fato registrada) usando os
mesmos dados (mesma chave, nome, valor). Os dois payloads ficaram byte-a-byte idênticos
em todos os campos *exceto* o CRC final. Recalcular esse CRC com a implementação "errada"
(augment) dava um valor diferente do real; recalcular sem o augment dava exatamente o
valor do payload real. Essa comparação direta contra um payload de PSP confiável foi o
que permitiu confirmar a causa — nenhuma quantidade de releitura da especificação ou
validação por ferramentas de terceiros teria pego isso, porque o bug era autoconsistente.

`calcularCRC16` em `pix.utils.ts` já usa a implementação correta (validada contra dois
payloads reais e funcionais, gerados por dois sistemas bancários diferentes). **Se algum
dia precisar tocar nesse código de novo: não confie em valores de teste de CRC16
encontrados pela internet sem confirmar contra o catálogo oficial reveng.sourceforge.io —
e, melhor ainda, valide sempre contra um payload real gerado pelo PSP onde a chave está
registrada, não contra a especificação isolada.**

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

### Ícones (logo do clube)

Gerados a partir de `icone-mensalidade-app.svg` (logo oficial: emblema lobo/caveira do
clube + cofrinho representando "mensalidades"), em `public/icons/`:

- `icon-192.png` / `icon-512.png` — ícones padrão do manifest (`purpose: "any"`).
- `icon-maskable-512.png` — versão com fundo sólido `#0a0a0a` (mesma cor do tema do app)
  e margem de 20% em cada lado ao redor do logo. Ícones `maskable` podem ser recortados
  pelo sistema (Android) em formas arbitrárias (círculo, squircle); sem essa margem, o
  cofrinho e o texto "mensalidades" — que ficam perto da borda no logo original — corriam
  risco real de serem cortados. A margem de 20% foi validada simulando um recorte
  circular completo antes de finalizar; uma margem menor (10%) deixava esses elementos
  colados na borda.
- `apple-touch-icon-{120,152,167,180}.png` e `favicon-{16,32}.png` — tamanhos dedicados
  por dispositivo/contexto (ver `index.html`), em vez de reaproveitar o `icon-192.png`
  genérico para tudo — evita reamostragem extra pelo navegador/SO em cada tamanho.

Para gerar novos tamanhos a partir do SVG fonte (ex: se o logo for atualizado), foi usado
`sharp` (Node) com `density: 384` na rasterização (mantém nitidez ao reduzir de 512px para
tamanhos menores) — não há script permanente no repositório para isso, é uma tarefa pontual.
