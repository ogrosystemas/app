# Instalação do TCPDF (Geração de PDF)

## Pré-requisito
PHP 8.1+ e Composer instalado no servidor.

## Instalação via Composer

Na raiz do projeto (`bar_system/`), execute:

```bash
composer install
```

Ou se o `vendor/` ainda não existe:

```bash
composer require tecnickcom/tcpdf
```

## Verificação

Após instalar, teste acessando:

```
https://seudominio.com/api/pdf.php?tipo=financeiro
```

Se aparecer o download do PDF, está funcionando.

## Hospedagem sem acesso SSH

Se não tiver acesso SSH, você pode:

1. Instalar localmente com `composer install`
2. Fazer upload da pasta `vendor/` gerada para o servidor
3. A pasta `vendor/` tem ~15MB

## URLs dos PDFs

| Relatório | URL |
|-----------|-----|
| Financeiro do mês atual | `/api/pdf.php?tipo=financeiro` |
| Financeiro mês específico | `/api/pdf.php?tipo=financeiro&mes=4&ano=2025` |
| Estoque atual | `/api/pdf.php?tipo=estoque` |
| Fechamento de caixa | `/api/pdf.php?tipo=caixa&id=N` |

## Sem TCPDF instalado

Se o TCPDF não estiver instalado, ao clicar em "Baixar PDF" aparecerá
uma página explicando como instalar. O resto do sistema funciona normalmente.
