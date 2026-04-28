#!/bin/bash
# setup_token_key.sh — Gera e configura TOKEN_KEY para criptografia dos tokens ML
# Execute UMA VEZ no servidor: bash /home/www/lupa/setup_token_key.sh

CONFIG="/home/www/lupa/config.php"
KEY=$(python3 -c "import secrets; print(secrets.token_hex(32))")

# Verifica se TOKEN_KEY já está configurada
if grep -q "define('TOKEN_KEY', '[a-f0-9]\{64\}')" "$CONFIG"; then
    echo "TOKEN_KEY já está configurada. Nenhuma ação necessária."
    exit 0
fi

# Aplica a chave
sed -i "s|define('TOKEN_KEY', getenv('OGRO_TOKEN_KEY') ?: '');|define('TOKEN_KEY', '${KEY}');|" "$CONFIG"

if grep -q "define('TOKEN_KEY', '${KEY}')" "$CONFIG"; then
    echo "✓ TOKEN_KEY configurada com sucesso!"
    echo "  Chave: ${KEY}"
    echo ""
    echo "IMPORTANTE: Faça backup desta chave! Se perder, os tokens ML precisarão ser reconectados."
else
    echo "ERRO: Não foi possível configurar TOKEN_KEY automaticamente."
    echo "Adicione manualmente ao config.php:"
    echo "  define('TOKEN_KEY', '${KEY}');"
fi
