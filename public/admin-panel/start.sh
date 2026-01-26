#!/bin/bash

# WhatsApp HSM Admin Panel - Start Script

echo "🚀 Iniciando WhatsApp HSM Admin Panel..."
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP não está instalado!"
    echo "   Instale o PHP 7.4 ou superior"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "✅ PHP $PHP_VERSION detectado"

# Create messages.json if it doesn't exist
if [ ! -f "messages.json" ]; then
    echo "[]" > messages.json
    echo "✅ Ficheiro messages.json criado"
fi

# Set permissions
chmod 666 messages.json 2>/dev/null
chmod 666 webhook.log 2>/dev/null

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📱 WhatsApp HSM Admin Panel"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 Servidor iniciado em: http://localhost:8080"
echo ""
echo "📋 Funcionalidades:"
echo "   • Listar templates HSM"
echo "   • Enviar mensagens HSM"
echo "   • Ver respostas recebidas"
echo ""
echo "🔧 Para configurar webhook (receber mensagens):"
echo "   1. Instale ngrok: brew install ngrok"
echo "   2. Execute: ngrok http 8080"
echo "   3. Configure URL na Infobip: https://xxx.ngrok.io/webhook.php"
echo ""
echo "⏹️  Para parar: Pressione Ctrl+C"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Start PHP server
php -S localhost:8080
