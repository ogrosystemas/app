# Deploy automático — Mutantes Moto Clube em app.ogrosystemas.com.br/mensalidades

## O que mudou

Antes você precisava rodar `npm run build` na sua máquina e copiar a pasta `dist/` manualmente
para dentro do repositório. **Agora isso é automático**: a partir desta entrega, o próprio
GitHub builda o app a cada push, exatamente como já acontece com seus outros PWAs — só que,
como este é um projeto React (precisa de build), o GitHub Actions faz esse passo de "build"
antes de publicar, em vez de servir o código-fonte direto.

## O que tem neste zip

```
.github/
└── workflows/
    └── deploy-mensalidades.yml   ← o robô que builda e publica automaticamente

mensalidades-src/                  ← código-fonte React (TypeScript, Vite, Tailwind...)
└── ... (todo o projeto)

COMO-PUBLICAR.md                   ← este arquivo
```

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

## Caso precise voltar ao fluxo manual

Se por algum motivo quiser builder localmente e copiar à mão de novo (como na entrega anterior),
ainda funciona: entre em `mensalidades-src/`, rode `npm install` e depois `npm run build` — o
resultado fica em `mensalidades-src/dist/`, e o conteúdo dessa pasta é o que vai dentro de
`app/mensalidades/`.

## Ponto de atenção — domínio compartilhado com outros PWAs

Este projeto está configurado (`mensalidades-src/vite.config.ts`, constante `BASE_PATH`) para
funcionar especificamente em `/mensalidades/`. Se um dia mover este app para outra subpasta,
altere essa constante e o próximo build (automático, pelo Actions) já vai refletir a mudança.
