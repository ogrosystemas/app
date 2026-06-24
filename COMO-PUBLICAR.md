# Deploy automático — Mutantes Moto Clube em app.ogrosystemas.com.br/mensalidades

## O que mudou

Antes você precisava rodar `npm run build` na sua máquina e copiar a pasta `dist/` manualmente
para dentro do repositório. **Agora isso é automático**: a partir desta entrega, o próprio
GitHub builda o app a cada push, exatamente como já acontece com seus outros PWAs — só que,
como este é um projeto React (precisa de build), o GitHub Actions faz esse passo de "build"
antes de publicar, em vez de servir o código-fonte direto.

## ⚠️ Esta entrega é uma migração GRANDE: o app passou a suportar múltiplas sedes

Antes, o app tinha um único clube fixo. Agora, cada sede (matriz + subsedes) é isolada,
com seu próprio tesoureiro, membros, pagamentos e chave Pix. Isso muda a estrutura do
banco de dados — **é necessário migrar os dados existentes antes de usar a versão nova**,
seguindo os passos abaixo, NESTA ORDEM:

### Passo 1 — Republicar `firestore.rules` (sempre obrigatório nesta entrega)

As regras mudaram para suportar três níveis (super admin / admin de sede / integrante).
Vá em Firebase Console → Firestore Database → Regras → cole o conteúdo de
`mensalidades-src/firestore.rules` → Publicar.

### Passo 2 — Publicar o código novo (mesmo fluxo de sempre)

Copie `mensalidades-src/` para dentro do seu repositório, commit, push, aguarde o deploy
automático (aba Actions) concluir.

### Passo 3 — Rodar a ferramenta de migração de dados (uma única vez)

1. Acesse `https://app.ogrosystemas.com.br/mensalidades/?migrar=1` (note o `?migrar=1` no
   final da URL — sem isso você cai no app normal).
2. Faça login com `tibabarcelos@gmail.com` (só esse e-mail consegue rodar a ferramenta).
3. Clique em **"Executar migração"**. Isso copia todos os dados do clube fixo antigo
   (`clubes/mutantes-mc`) para a nova sede "Itajaí" (`clubes/itajai`), cria os metadados
   da sede, e te promove a Super Admin (`administradores/tibabarcelos@gmail.com` com
   `clubeId: "*"`). **Os dados antigos não são apagados** — a migração só copia.
4. Acompanhe o log na tela até aparecer "🎉 MIGRAÇÃO CONCLUÍDA COM SUCESSO."

### Passo 4 — Confirmar que tudo está certo

1. Acesse o app normalmente (sem o `?migrar=1`), faça login com
   `tibabarcelos@gmail.com`.
2. Você deve ver a tela de **escolha de sede** (já que agora é Super Admin) — escolha
   "Itajaí" e confirme que todos os 19 membros, pagamentos e configurações (incluindo a
   chave Pix) estão lá, exatamente como antes.
3. Os integrantes com acesso vinculado (ex: Corega) também devem continuar funcionando
   normalmente — o vínculo deles foi atualizado automaticamente pela ferramenta.

### Passo 5 — Remover a ferramenta de migração (depois de confirmar tudo certo)

A ferramenta (`src/MigrationTool.tsx` e o trecho condicional em `src/main.tsx` que checa
`?migrar=1`) é de uso único — depois de confirmado que a migração funcionou, peça para eu
gerar uma versão sem ela, ou remova manualmente esses dois pontos e publique de novo. Não
é estritamente perigoso deixá-la (ela exige login + checagem do e-mail certo + as regras
de segurança continuam valendo), mas é uma boa prática não deixar ferramentas de uso único
em produção depois que não são mais necessárias.

### Para criar uma sede nova depois disso

Como Super Admin, na tela de escolha de sede, clique em **"Nova Sede"** — preencha nome,
ID (gerado automaticamente a partir do nome, editável), valor inicial da mensalidade, e o
e-mail do tesoureiro responsável. Tudo é criado numa única ação, sem precisar editar nada
no Firebase Console.

## O que tem neste zip

```
.github/
└── workflows/
    └── deploy-mensalidades.yml   ← o robô que builda e publica automaticamente

mensalidades-src/                  ← código-fonte React (TypeScript, Vite, Tailwind...)
└── ... (todo o projeto)

COMO-PUBLICAR.md                   ← este arquivo
```

## ⚠️ Passo obrigatório ANTES do primeiro push: configurar os Secrets do Firebase

Esta versão do app usa Firebase (login com Google + banco de dados na nuvem). As chaves de
configuração do seu projeto Firebase **não vêm dentro do código** (por segurança e boas
práticas) — elas precisam ser cadastradas como "GitHub Secrets" no repositório, uma única
vez, antes do workflow conseguir buildar o app corretamente.

1. No GitHub, vá em **Settings** do repositório `app` → **Secrets and variables** →
   **Actions** → **New repository secret**.
2. Crie estes 6 secrets, um por um (nome exato à esquerda, valor à direita — os valores
   você encontra no Firebase Console, em Configurações do projeto → Seus apps → app Web →
   Configuração do SDK):

   | Nome do secret | De onde vem |
   |---|---|
   | `VITE_FIREBASE_API_KEY` | `apiKey` |
   | `VITE_FIREBASE_AUTH_DOMAIN` | `authDomain` |
   | `VITE_FIREBASE_PROJECT_ID` | `projectId` |
   | `VITE_FIREBASE_STORAGE_BUCKET` | `storageBucket` |
   | `VITE_FIREBASE_MESSAGING_SENDER_ID` | `messagingSenderId` |
   | `VITE_FIREBASE_APP_ID` | `appId` |

3. Só depois desses 6 secrets criados, siga o passo a passo normal de publicação abaixo.

Sem esses secrets, o workflow ainda builda e publica (não falha visivelmente), mas o app
publicado vai dar erro de configuração do Firebase ao abrir — sempre confirme que os 6
secrets existem antes do primeiro push desta versão.

## Outro passo obrigatório: publicar as regras de segurança do Firestore

⚠️ **Esta entrega traz `firestore.rules` atualizado** (suporte a integrantes com acesso de
consulta restrita, além dos administradores) — mesmo que você já tenha publicado uma
versão anterior, é necessário **republicar** o conteúdo novo.

O arquivo `mensalidades-src/firestore.rules` define quem pode acessar os dados do clube:
administradores (lista de e-mails na função `emailAutorizado()`) veem e editam tudo;
integrantes com e-mail vinculado no próprio cadastro (campo "E-mail de acesso") veem
apenas o próprio status, em modo consulta. Esse arquivo **não é aplicado automaticamente**
pelo GitHub Actions — precisa ser publicado manualmente, sempre que mudar:

1. Abra o Firebase Console → seu projeto → **Firestore Database** → aba **Regras**.
2. Copie todo o conteúdo de `mensalidades-src/firestore.rules`.
3. Cole no editor de regras do Console, substituindo o que já estava lá.
4. Clique em **Publicar**.

Para vincular um integrante a uma área de consulta, não é preciso tocar nessas regras de
novo — basta editar o cadastro dele no app (Mais ações → Editar → campo "E-mail de
acesso"). Só a lista de *administradores* exige editar e republicar este arquivo.

## Passo a passo (uma única vez)

1. No seu repositório **`app`** (o mesmo que já hospeda os outros PWAs), copie o conteúdo
   deste zip para a **raiz do repositório**:
   - a pasta `.github/` (se você já tiver uma `.github/workflows/` com outros arquivos `.yml`,
     apenas adicione o arquivo `deploy-mensalidades.yml` dentro dela — não substitua a pasta toda)
   - a pasta `mensalidades-src/`, inteira, na raiz do repo (ao lado das pastas dos outros PWAs)
2. Comite e faça push para a branch `main`.
3. Vá na aba **Actions** do repositório no GitHub — você vai ver o workflow
   **"Build e publicar Mutantes MC em /mensalidades"** rodando automaticamente.
4. Espere ele terminar (1-2 minutos). Ele vai:
   - instalar as dependências do projeto
   - rodar o build (Vite + TypeScript + Tailwind)
   - copiar o resultado para dentro da pasta `mensalidades/` do mesmo repositório
   - fazer commit e push dessa pasta `mensalidades/` automaticamente
5. Assim que esse commit automático aparecer, o GitHub Pages publica como sempre —
   sem você precisar fazer mais nada.
6. Acesse `https://app.ogrosystemas.com.br/mensalidades/` (com a barra final).

## Daqui pra frente: como atualizar o app

Sempre que você (ou eu) alterar algo dentro de `mensalidades-src/src/`:

1. Comite e faça push dessa alteração para `main`.
2. O workflow detecta a mudança automaticamente e builda de novo.
3. Pronto — não precisa rodar nada na sua máquina, não precisa copiar pasta nenhuma.

Você pode acompanhar o progresso em **Actions** no GitHub a qualquer momento. Se quiser forçar
uma nova publicação sem alterar código (por exemplo, depois de mexer só no `vite.config.ts`),
vá em Actions → "Build e publicar Mutantes MC em /mensalidades" → **Run workflow**.

## Por que isso não quebra os outros PWAs

O workflow só dispara quando algo dentro de `mensalidades-src/` (ou o próprio arquivo do
workflow) muda — pushes relacionados aos outros PWAs não acionam este processo. E o passo
final do workflow só toca na pasta `mensalidades/`; nenhuma outra pasta do repositório é lida,
movida ou apagada.

## Por que o commit automático não entra em loop

O workflow termina fazendo um commit (com a pasta `mensalidades/` atualizada) e um push de
volta para `main`. Pra esse commit não disparar o workflow de novo (e ficar num loop infinito
de build→commit→build→commit...), a mensagem desse commit automático inclui a marcação
`[skip ci]` — um recurso nativo do GitHub que faz ele simplesmente não iniciar o workflow
quando vê essa marcação na mensagem do commit. Você não precisa fazer nada a respeito disso,
é só pra entender o que está acontecendo se notar essa marcação no histórico de commits.

## Se o workflow falhar

Abra a aba **Actions**, clique na execução que falhou e leia o log — ele mostra exatamente em
qual passo (instalar dependências, build, ou commit) e por quê. As causas mais comuns:
- Algum erro de TypeScript ou ESLint introduzido no código-fonte (o build já roda essas
  verificações antes de gerar os arquivos finais).
- Falta de permissão de escrita do Actions no repositório — verifique em Settings → Actions →
  General → Workflow permissions, que precisa estar em "Read and write permissions".
- Se o app publicar mas mostrar erro de configuração do Firebase ao abrir (não é um erro que
  trava o workflow, mas falha em tempo de uso): confira se os 6 GitHub Secrets do Firebase
  estão cadastrados corretamente (ver seção acima) e se os e-mails autorizados estão
  publicados em `firestore.rules` no Firebase Console.

## Caso precise voltar ao fluxo manual

Se por algum motivo quiser builder localmente e copiar à mão de novo (como na entrega anterior),
ainda funciona: entre em `mensalidades-src/`, rode `npm install` e depois `npm run build` — o
resultado fica em `mensalidades-src/dist/`, e o conteúdo dessa pasta é o que vai dentro de
`app/mensalidades/`.

## Ponto de atenção — domínio compartilhado com outros PWAs

Este projeto está configurado (`mensalidades-src/vite.config.ts`, constante `BASE_PATH`) para
funcionar especificamente em `/mensalidades/`. Se um dia mover este app para outra subpasta,
altere essa constante e o próximo build (automático, pelo Actions) já vai refletir a mudança.
