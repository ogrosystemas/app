# TCPDF — Instalação

O sistema precisa do TCPDF para gerar PDFs.

## Opção 1: Copiar do servidor (recomendado)
Se você já tem o TCPDF em `vendor/tcpdf/` no servidor, copie o conteúdo para esta pasta:
```
vendor/tcpdf/ → tcpdf/
```

## Opção 2: Download direto
1. Baixe o TCPDF em: https://github.com/tecnickcom/TCPDF/releases
2. Extraia e coloque os arquivos nesta pasta (`tcpdf/`)
3. O arquivo principal deve ser: `tcpdf/tcpdf.php`

## Opção 3: Composer
```bash
composer require tecnickcom/tcpdf
```
