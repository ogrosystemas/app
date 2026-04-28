# 🍺 Bar System Pro

Sistema completo de gerenciamento de bar com PDV, controle de estoque, integração PagSeguro e relatórios.

---

## ✅ Funcionalidades

### PDV (Dashboard Principal)
- Interface escura com tema **âmbar** profissional
- **Grade de produtos em 3 colunas** com imagens
- Busca instantânea e filtro por categoria
- Carrinho com controle de quantidade
- Desconto por venda
- Formas de pagamento: Dinheiro, Débito, Crédito, PIX, Cortesia
- **Integração PagSeguro** — envia cobrança para máquina física e aguarda confirmação
- Troco automático para dinheiro
- Controle de mesa/identificação
- Abertura e fechamento de caixa com resumo
- Sangria e suprimento de caixa
- **Alertas de estoque baixo** em tempo real

### Controle de Estoque
- Estoque de **latas/unidades** com baixa automática na venda
- **Barris de chopp** com:
  - Cálculo automático de doses por barril
  - Fórmula: `ML úteis = capacidade × (rendimento% / 100)`
  - Doses estimadas: `ML úteis ÷ ML por copo`
  - Barra de progresso visual do barril
  - Abertura e fechamento de barris
  - Registro do custo e cálculo de custo por dose
- Calculadora de barril interativa
- Movimentações completas (entrada/saída/ajuste/perda)
- Alertas automáticos de estoque mínimo

### Produtos
- Cadastro com **upload de foto**
- Tipos: Unidade, Dose, Chopp Lata, Chopp Barril, Garrafa, Outro
- Destaque no PDV
- Categorias com cores e ícones FontAwesome personalizados

### Integração PagSeguro
- Classe PHP completa para API PagSeguro/PagBank
- Envia cobrança para **terminal físico (Smart POS)**
- Polling automático do status de pagamento
- Webhook para atualizações em tempo real
- Suporte a Sandbox e Produção
- Cancelamento de cobrança pendente

### Relatórios
- Faturamento mensal com gráfico de barras (últimos 6 meses)
- Vendas por forma de pagamento
- Top 10 produtos mais vendidos
- Vendas por dia
- Movimentações de estoque
- Valor do estoque atual
- Detalhamento de vendas

### Histórico de Caixa
- Resumo por caixa com diferença (sobra/falta)
- Sangrias e suprimentos
- Por forma de pagamento

---

## 🚀 Instalação

### Requisitos
- PHP 8.3+
- MySQL 8.0+ / MariaDB 10.6+
- Apache/Nginx com `mod_rewrite`
- Extensões: PDO, PDO_MySQL, curl, mbstring, json, fileinfo

### Passo a Passo

1. Copie a pasta `bar_system/` para `htdocs/` ou `/var/www/html/`
2. Acesse: `http://localhost/bar_system/install/install.php`
3. Preencha os dados de banco e URL
4. **Delete a pasta `install/`** após instalar
5. Acesse: `http://localhost/bar_system/`

---

## ⚙️ Configurar PagSeguro

1. Acesse **Configurações → PagSeguro**
2. Informe o **Bearer Token** da sua conta PagBank
3. Selecione o ambiente (Sandbox para testes)
4. Teste a conexão
5. Cadastre os **terminais físicos** com o Terminal ID
6. Configure o Webhook URL no painel PagBank:
   ```
   http://seudominio.com/bar_system/api/pagseguro.php?action=webhook
   ```

---

## 🍺 Fórmula do Barril de Chopp

```
ML Úteis     = Capacidade Total (ml) × (Rendimento% ÷ 100)
Doses        = PISO(ML Úteis ÷ ML por Dose)
Custo/Dose   = Custo do Barril ÷ Doses
Lucro/Barril = (Preço Venda × Doses) - Custo do Barril
```

**Exemplo — Barril 30L com 85% de rendimento e copos de 300ml:**
- ML úteis: 25.500ml (25,5L)
- Perda: 4.500ml (espuma, limpeza, transporte)  
- Doses: 85 copos de 300ml
- Se barril custou R$ 170 → custo/dose = R$ 2,00
- Se vende a R$ 9 → lucro por barril = R$ 595

---

## 📁 Estrutura de Arquivos

```
bar_system/
├── index.php              # PDV Dashboard
├── config/config.php      # Configurações
├── includes/
│   ├── DB.php             # Classe de banco de dados
│   ├── helpers.php        # Funções auxiliares + cálculo barril
│   └── PagSeguro.php      # Integração completa PagSeguro API
├── api/
│   ├── caixa.php          # Abrir/fechar/sangria/suprimento
│   ├── venda.php          # Finalizar venda + baixa estoque
│   ├── pagseguro.php      # Cobrar terminal, status, webhook
│   └── alertas.php        # Alertas de estoque
├── modules/
│   ├── produtos/          # Cadastro com upload de imagem
│   ├── estoque/           # Estoque + barris + movimentações
│   ├── caixa/             # Histórico de caixas
│   ├── relatorios/        # Relatórios financeiros e estoque
│   └── configuracoes/     # Config geral, PagSeguro, categorias
├── assets/
│   ├── css/pdv.css        # Tema dark âmbar do PDV
│   ├── css/admin.css      # Tema dark do backoffice
│   ├── js/pdv.js          # Lógica do PDV
│   └── uploads/produtos/  # Imagens dos produtos
└── install/               # Instalador (apagar após instalar)
```

---

## 📱 Formas de Pagamento Suportadas

| Forma | Como funciona |
|-------|--------------|
| Dinheiro | Calcula troco automaticamente |
| Cartão Débito/Crédito | Registro manual |
| PIX | Registro manual |
| **PagSeguro** | Envia para máquina física, aguarda aprovação |
| Cortesia | Sem cobrança (para controle) |

---

## 🔒 Segurança
- Proteja o arquivo `config/config.php`
- Delete a pasta `install/` após instalar
- Em produção, configure `SSL_VERIFYPEER => true` no cURL
- Configure o webhook secret do PagSeguro
