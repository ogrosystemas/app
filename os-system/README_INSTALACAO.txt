============================================================
  OS-System — Gestão de Oficinas de Motocicletas
  Versão: 2.1
============================================================

INSTALAÇÃO (método recomendado)
--------------------------------
1. Copie a pasta ossystem_new/ para seu servidor web

2. Execute APENAS o arquivo:
      instalar.sql   ← script completo, cria tudo do zero

   Ou, se já tiver instalado v1/v2, execute:
      database_patch_v2.sql  ← adiciona apenas o que falta

3. Configure a conexão em config/database.php:
   - DB_HOST, DB_NAME (ossystem), DB_USER, DB_PASS

4. Acesse: http://seudominio/ossystem/

LOGINS PADRÃO
-------------
  admin@os-system.com    / (senha configurada no sistema)
  gerente@os-system.com  / password
  mecanico@os-system.com / password
  caixa@os-system.com    / password
  vendedor@os-system.com / password

CONFIGURAR O LOGO
-----------------
  Admin → Configurações → upload da sua logo (JPG/PNG, máx 5MB)

MAPA DE ARQUIVOS SQL
--------------------
  instalar.sql         → Instalação completa do zero (USE ESTE)
  database_patch_v2.sql → Atualização incremental (se já tem v1)

CORREÇÕES v2.1
--------------
✅ Logo corrigida com URL absoluta (aparece em todos os módulos)
✅ PDV completamente reescrito:
   - Filtro de categoria por botões clicáveis
   - Busca global em todos os produtos (mesmo os ocultos no PDV)
   - JS isolado em IIFE (sem conflitos)
   - Sangria/Suprimento direto no PDV
   - Troco calculado ao vivo
✅ OS Editar — valor calculado automaticamente (tempo × mão de obra/hora)
   Exibe preview "R$ X,XX" antes de salvar
✅ Produtos — gestão completa de categorias:
   - Aba "Categorias" com paleta de cores
   - Filtro por categoria na listagem
   - Chips de filtro rápido
   - Campo "Exibir no PDV" por produto
✅ instalar.sql unificado com todos os dados
