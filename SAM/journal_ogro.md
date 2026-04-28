# Ogro ERP-WMS — Journal Completo do Projeto (v2)

## Stack Técnica
- **Backend:** PHP 8.3 puro (sem framework, sem Composer)
- **Banco:** MariaDB porta 3306, banco `lupa_erp`, usuário `lupa_user`, senha `Lupa2026`
- **Servidor:** VPS Contabo 161.97.126.36, aaPanel 8.0.1, OpenLiteSpeed
- **Domínio:** https://lupa.ogrosystemas.com.br (SSL Let's Encrypt)
- **Frontend:** Tailwind CDN + Lucide Icons + Chart.js 4.4.0 + Alpine.js
- **IA:** Google Gemini via cURL
- **PDF:** TCPDF em `/home/www/lupa/lib/tcpdf/tcpdf.php`
- **APP_NAME:** `Ogro ERP-WMS`
- **PHP CLI (cron):** `/usr/local/lsws/lsphp83/bin/php8.3`

## Credenciais e Chaves
- **TOKEN_KEY:** `45e8789373d55a70ea5a2153ea9c7771f2c5c400e25791032684960bfe863787`
- **MASTER_SECRET:** `4bb6f3015f4649631c584aea8529e43220c7c20e9ab7d911ede8e24971cc1b12`
- **GEMINI_API_KEY:** `AIzaSyC3kUFFyma47on72PMlooWri3EYWKqg4Is`
- **ML App ID:** `7274929999947209`
- **Conta ML ativa:** BUTOBARCELOS (id: b1d51b1c-0db1-4ade-955a-8558ae27ed47)
- **Conta demo:** LOJA_ML_DEMO (id: meli-acc-0001-0000-000000000001, is_active=0 por padrão)

## Estrutura de Arquivos no Servidor
```
/home/www/lupa/
├── config.php, db.php, auth.php, crypto.php, index.php
├── .htaccess, sw.js, manifest.json, backup.sh, crontab.txt
├── lib/tcpdf/tcpdf.php  ← TCPDF instalado aqui
├── pages/
│   ├── layout.php, layout_end.php, login.php
│   ├── dashboard.php, sac.php, anuncios.php
│   ├── financeiro.php, logistica.php, estoque.php  ← NOVO
│   ├── admin.php, config_ml.php
│   └── errors/ (404.php, 403.php, bloqueado.php)
├── api/
│   ├── auth.php, worker.php, health.php, activate_license.php
│   ├── dashboard_data.php, sac_data.php, anuncios_data.php, financeiro_data.php
│   ├── upload_picture.php, publish_item.php, fiscal_note.php
│   ├── meli_callback.php, meli_connect.php, meli_refresh_token.php
│   ├── sync_orders.php, change_password.php  ← NOVOS
│   ├── pdf_financeiro.php, pdf_estoque.php, pdf_etiqueta.php  ← NOVOS (TCPDF)
│   ├── demo_reset.php
│   └── webhooks/meli.php
├── database/
│   ├── ogro_erp_complete.sql
│   ├── migration_products_account.sql  ← Adiciona meli_account_id em products, financial_entries, bank_accounts
│   ├── demo_activate.sql
│   ├── demo_reset.sql
│   ├── reset_para_cliente.sql
│   └── entregar_cliente.php  ← Script interativo de entrega com senha aleatória
├── assets/logo.png, assets/icons/
└── storage/logs/, storage/backups/, storage/nf/
```

## Banco de Dados — Colunas Novas (Migration)

### Executar ANTES de subir os arquivos PHP:
```bash
mysql -u lupa_user -pLupa2026 lupa_erp << 'EOF'
ALTER TABLE bank_accounts     ADD COLUMN IF NOT EXISTS meli_account_id VARCHAR(36) NULL AFTER tenant_id;
ALTER TABLE products          ADD COLUMN IF NOT EXISTS meli_account_id VARCHAR(36) NULL AFTER tenant_id;
ALTER TABLE financial_entries ADD COLUMN IF NOT EXISTS meli_account_id VARCHAR(36) NULL AFTER tenant_id;
-- ... (ver migration_products_account.sql completo para CONVERT collation)
EOF
```

### Problema de collation (utf8mb4_unicode_ci vs utf8mb4_general_ci)
**Solução:** migration_products_account.sql inclui `CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` em todas as 16 tabelas.

## Padrão Obrigatório — Filtro por Conta ML

```php
$acctId    = $_SESSION['active_meli_account_id'] ?? null;
$acctSql   = $acctId ? " AND meli_account_id=?"    : "";   // queries simples
$acctSqlFe = $acctId ? " AND fe.meli_account_id=?" : "";   // queries com JOIN alias fe.
$acctP     = $acctId ? [$acctId] : [];                     // NUNCA null

// Uso:
db_all("SELECT ... WHERE tenant_id=?{$acctSql}", array_merge([$tenantId], (array)$acctP));
db_all("SELECT fe.* FROM financial_entries fe ... WHERE fe.tenant_id=?{$acctSqlFe}", ...);
```

**CRÍTICO:** usar `$acctSqlFe` (com prefixo `fe.`) em todas as queries que fazem JOIN com outras tabelas que também tenham `meli_account_id` (bank_accounts, chart_of_accounts). Sem isso: `Column 'meli_account_id' is ambiguous` → 500.

## Sistema de PDFs (TCPDF)

### Relatório Financeiro: `api/pdf_financeiro.php`
- GET: `month=YYYY-MM`, `tipo=dre|extrato|completo`
- Layout A4 retrato: cabeçalho azul ML, 5 KPIs, contas bancárias, DRE encadeada, extrato
- Download direto como `.pdf`

### Relatório de Estoque: `api/pdf_estoque.php`
- GET: `status=all|ok|critico|zerado`, `search=`, `order=`
- Layout A4 paisagem: cabeçalho azul, 5 KPIs, tabela completa de produtos
- Download direto como `.pdf`

### Etiqueta de Envio: `api/pdf_etiqueta.php`
- GET: `order_id=ID` (individual) ou `ids=ID1,ID2,...` (lote)
- Formato 10x15cm (A6 landscape), uma etiqueta por página
- Contém: destinatário, cidade/estado, remetente, status de envio, dados do pedido, itens
- Marca `pdf_printed=1` automaticamente no banco

## Sistema de Alertas de Token ML (layout.php)

Três camadas de alerta quando token expira/é revogado:

1. **Banner no topo** (todas as páginas):
   - Vermelho: `is_active=0` com `invalid_grant` → "Reconectar agora"
   - Amarelo: token expirado mas conta ativa → "Forçar renovação"
   - Info: expira em menos de 1h → aviso silencioso

2. **Seletor de conta no header**:
   - Ponto vermelho piscante (`animation:pulse-red`)
   - Borda do botão muda de cor
   - Conta revogada redireciona para config_ml ao clicar

3. **Página Integração ML**:
   - Card detalhado por conta com status, data de expiração
   - Botão "Reconectar" ou "Forçar refresh" conforme estado

## Troca de Senha (implementado)

- **Menu do avatar** no header: nome, cargo, "Trocar senha", "Sair"
- **Modal global** em `layout.php`: valida senha atual, mínimo 8 chars, confirmação
- **Endpoint** `api/change_password.php`: `password_verify` + `password_hash` custo 12 + audit_log
- Fecha ao clicar fora, Enter navega entre campos

## Entrega ao Cliente

### Script interativo (recomendado):
```bash
/usr/local/lsws/lsphp83/bin/php8.3 /home/www/lupa/database/entregar_cliente.php 2>/dev/null
```
- Pede empresa, e-mail, admin, plano
- Gera senha aleatória segura (formato `Xabc-7823-Ydef`)
- Exibe credenciais em caixa e salva log em `storage/logs/entrega_YYYYMMDD.txt`
- Zera todas as 17 tabelas e cria tenant + plano de contas padrão

## Demo

### Ativar demo (primeira vez):
```bash
mysql -u lupa_user -pLupa2026 lupa_erp < database/demo_activate.sql
```

### Resetar datas para apresentação:
```bash
mysql -u lupa_user -pLupa2026 lupa_erp < database/demo_reset.sql
```
Ou via browser (logado como admin):
```
https://lupa.ogrosystemas.com.br/api/demo_reset.php?secret=MASTER_SECRET
```

### Login demo:
- Email: `admin@lojaml.com.br` / Senha: `demo@1234`

## Crontab Instalado

```bash
crontab -e  # colar conteúdo de crontab.txt
```

Binário PHP correto para OLS: `/usr/local/lsws/lsphp83/bin/php8.3 2>/dev/null`

| Job | Frequência | Descrição |
|---|---|---|
| worker.php | * * * * * | Processa fila de webhooks ML |
| meli_refresh_token.php | */30 * * * * | Renova tokens antes de expirar (< 2h) |
| sync_orders.php --days=1 | */15 * * * * | Sincroniza pedidos recentes |
| backup.sh | 0 2 * * * | Backup do banco |
| DELETE queue_jobs DONE | 0 3 * * * | Limpeza de jobs antigos |
| DELETE sessions expiradas | 30 3 * * * | Limpeza de sessões |
| DELETE login_attempts | 0 4 * * * | Limpeza de tentativas de login |
| Auto-recovery jobs travados | */5 * * * * | Reprocessa jobs PROCESSING > 5min |

## Módulos — Status Atual

### Dashboard ✅
- KPIs filtrados por conta ML ativa
- Card "Resultado ML da conta selecionada": GMV, taxa ML, receita líquida, ticket médio, mini gráfico 6 meses
- Card "Movimentação financeira — Todas as contas": consolidado da empresa
- `$activeAcctNickname` buscado do banco antes do include layout

### SAC ✅
- KPIs e conversas filtrados por conta ativa
- Status da conversa atualiza visualmente sem reload (border-left + ícone)
- Paginação: 30 conversas por página, ordenadas por não lidas primeiro
- IA Gemini para sugestão de resposta
- Envio real via API ML com token descriptografado

### Anúncios ✅
- Listagem filtrada por conta (agora com meli_account_id em products)
- Upload fotos → ML, Publicação real → ML
- Calculadora de margem

### Financeiro ✅
- Filtra por conta ativa (`financial_entries` tem meli_account_id)
- DRE encadeada, fluxo diário, lançamentos com recorrência
- Contas bancárias filtradas por conta ML ativa (bank_accounts tem meli_account_id)
- Botões: Extrato PDF | DRE PDF | Completo PDF (todos via TCPDF)

### Logística/Expedição ✅
- Etiqueta PDF via TCPDF (10x15cm, uma por página, lote suportado)
- Etiqueta ZPL para impressora Zebra
- Upload manual de NF (PDF ou XML, máx 5MB)
- Busca NF do ML + opção de substituir por NF própria

### Estoque ✅ (NOVO)
- Menu: Logística → Estoque (permissão `can_access_logistica`)
- KPIs: total, críticos, zerados, valor pelo custo, potencial de venda
- Filtros por status + busca + ordenação
- Edição inline de quantidade, mínimo e custo
- Exportar PDF via TCPDF (A4 paisagem)

### Admin ✅
- RBAC por módulo
- Criar novo usuário com modal completo

### Integração ML ✅
- Credenciais, reconexão ML
- Card de licença com trial, ativação inline
- Status detalhado de cada conta: OK / Expirando / Expirado / Revogado
- Botão "Reconectar" e "Forçar refresh" por conta

## Problemas Conhecidos e Soluções

### `Column 'meli_account_id' is ambiguous` → 500
- **Causa:** query com JOIN usa `{$acctSql}` sem prefixo de tabela
- **Solução:** usar `$acctSqlFe = $acctId ? " AND fe.meli_account_id=?" : ""` em queries com alias `fe.`

### ENUM `RECEIVED` não existe em financial_entries
- **Status válidos:** `PENDING`, `PAID`, `CANCELLED`, `OVERDUE`
- **Nunca usar** `'RECEIVED'` em queries — causa erro SQL silencioso que vira 500

### `$mesAtual` undefined no dashboard
- **Causa:** variável definida depois de ser usada
- **Solução:** definir `$mesAtual = date('Y-m')` antes do bloco `if ($acctId)` na linha ~14

### Login erro de conexão
- `sessions` usa `$_SESSION` PHP nativo apenas
- `login_attempts` usa `db_query` direto (id INT AUTO_INCREMENT, não UUID)

### `audit_log()` redeclarada
- Definida APENAS em `config.php` — nunca em `auth.php`

### Collation mismatch em JOINs
- Executar `migration_products_account.sql` que faz CONVERT em todas as tabelas

## Observações Importantes
1. TCPDF em `/home/www/lupa/lib/tcpdf/tcpdf.php`
2. PHP CLI para cron: `/usr/local/lsws/lsphp83/bin/php8.3 2>/dev/null` (warnings são falsos alarmes de extensões compiladas estaticamente)
3. `audit_log()` está em `config.php` — NÃO redeclarar
4. Sempre usar `(array)$acctP` no array_merge
5. Usar `$acctSqlFe` (com `fe.`) em queries com JOIN
6. TOKEN_KEY e MASTER_SECRET são diferentes
7. O servidor usa OpenLiteSpeed — restart: `systemctl restart lsws`
8. BUTOBARCELOS é a conta real — webhooks populam quando chegarem vendas
9. `financial_entries.status` ENUM: apenas `PENDING`, `PAID`, `CANCELLED`, `OVERDUE`
10. `bank_accounts`, `products` e `financial_entries` agora têm `meli_account_id` (migration necessária)
