#!/bin/bash

# Script para configurar webhook local usando ngrok

echo "🚀 Configurando Webhook Local"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Verificar se ngrok está instalado
if ! command -v ngrok &> /dev/null; then
    echo "❌ ngrok não está instalado"
    echo ""
    echo "📥 Para instalar:"
    echo "  1. Acesse: https://ngrok.com/download"
    echo "  2. Ou use: brew install ngrok (macOS)"
    echo ""
    exit 1
fi

echo "✅ ngrok encontrado"
echo ""

# Iniciar servidor PHP
echo "🔧 Iniciando servidor PHP na porta 8000..."
php -S localhost:8000 -t public &
PHP_PID=$!
echo "✅ Servidor PHP iniciado (PID: $PHP_PID)"
echo ""

# Aguardar servidor iniciar
sleep 2

# Iniciar ngrok
echo "🌐 Iniciando ngrok..."
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 INSTRUÇÕES:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. Copie o URL HTTPS que aparecerá abaixo"
echo "2. Adicione /webhook_receiver.php ao final"
echo "3. Configure na Infobip:"
echo "   - Portal: https://portal.infobip.com"
echo "   - WhatsApp > Settings > Webhooks"
echo "   - Incoming Messages URL: https://xxxxx.ngrok.io/webhook_receiver.php"
echo ""
echo "4. Envie uma mensagem do WhatsApp para testar"
echo "5. Verifique: storage/logs/received_messages.log"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Iniciar ngrok
ngrok http 8000

# Cleanup ao sair
kill $PHP_PID
