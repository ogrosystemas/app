# KM Corporate — Sistema de Gestão de Quilometragem

Sistema web em **PHP 8.3 + MySQL** para gestão de quilometragem corporativa e presença em eventos.

---

## 📋 Requisitos

- PHP 8.3+ com extensões: `pdo`, `pdo_mysql`, `mbstring`
- MySQL 8.0+ (ou MariaDB 10.5+)
- Servidor web: Apache (mod_rewrite) ou Nginx

---

## 🚀 Instalação

### 1. Copiar arquivos

Copie a pasta `km-system/` para a raiz do seu servidor web:

```
/var/www/html/km-system/   → Linux/Apache
C:/xampp/htdocs/km-system/ → Windows/XAMPP
C:/wamp/www/km-system/     → Windows/WAMP
```

### 2. Criar o banco de dados

Acesse o MySQL (phpMyAdmin ou terminal) e execute o arquivo:

```sql
SOURCE /caminho/para/km-system/database.sql;
```

Ou copie e cole o conteúdo do `database.sql` no phpMyAdmin.

### 3. Configurar a conexão

Edite o arquivo `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'km_system');
define('DB_USER', 'root');       // seu usuário MySQL
define('DB_PASS', '');           // sua senha MySQL

define('BASE_URL', 'http://localhost/km-system'); // URL do sistema
```

### 4. Acessar o sistema

Abra no navegador: `http://localhost/km-system`

---

## 🔐 Credenciais padrão

| Perfil        | E-mail                  | Senha     |
|---------------|-------------------------|-----------|
| Administrador | admin@sistema.com       | Admin@123 |
| Usuário demo  | usuario@sistema.com     | User@123  |

> ⚠️ **Altere as senhas imediatamente após o primeiro acesso!**

---

## 📁 Estrutura de arquivos

```
km-system/
├── index.php                  → Redireciona para login ou painel
├── login.php                  → Página de login
├── logout.php                 → Encerrar sessão
├── profile.php                → Perfil do usuário (todos)
├── database.sql               → Script de criação do banco
│
├── includes/
│   ├── config.php             → Configurações globais
│   ├── db.php                 → Conexão PDO singleton
│   ├── auth.php               → Autenticação, CSRF, sessão
│   ├── helpers.php            → Funções utilitárias
│   └── layout.php             → Sidebar, topbar, layout
│
├── admin/
│   ├── dashboard.php          → Painel administrativo
│   ├── users.php              → Gerenciar usuários
│   ├── events.php             → Gerenciar eventos
│   ├── attendances.php        → Registrar presenças e KM
│   └── reports.php            → Relatórios + exportação CSV
│
├── user/
│   ├── dashboard.php          → Painel do colaborador
│   ├── events.php             → Lista de eventos
│   └── history.php            → Histórico de participações
│
└── assets/
    └── css/
        └── main.css           → Design system completo
```

---

## ✅ Funcionalidades

### Área Administrativa
- Dashboard com totais de KM, presenças, ranking e eventos
- Gerenciar usuários (criar, editar, bloquear/ativar)
- Gerenciar eventos (cadastrar, editar, desativar)
- Registrar presenças e KM por evento e por colaborador
- KM extra individual além do KM padrão do evento
- Relatório de ranking com barra de progresso
- Relatório por evento
- Exportação em CSV (ranking, eventos, detalhado)
- Filtro por ano em todas as telas

### Área do Usuário
- Dashboard com total de KM anual, taxa de participação e ranking
- Lista de todos os eventos com status de presença
- Histórico completo de participações com KM detalhado
- Próximos eventos com indicação de presença confirmada
- Perfil: editar nome e alterar senha

---

## 🔒 Segurança

- Senhas com bcrypt (custo 12)
- Proteção CSRF em todos os formulários POST
- `session_regenerate_id` no login
- PDO com prepared statements (proteção SQL injection)
- `htmlspecialchars` em todo output (proteção XSS)
- Verificação de papel (admin/user) em cada página

---

## 📊 Lógica de KM

Cada presença registrada gera:

```
KM Total = km_awarded (do evento) + km_extra (individual)
```

- `km_awarded`: definido no cadastro do evento (igual para todos os presentes)
- `km_extra`: valor adicional definido pelo admin para cada presença individualmente

---

## 🛠️ Personalização

Para alterar a marca e cores, edite:

- **Nome do sistema**: `APP_NAME` em `includes/config.php`
- **Cores**: variáveis CSS em `assets/css/main.css` (seção `:root`)
- **Logo**: ícone 🚗 pode ser substituído por uma `<img>` no `includes/layout.php`
