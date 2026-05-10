#!/bin/bash
# Встановлення DOM, tokenizer та xmlwriter

set -e

echo "🔧 Встановлення php83-dom, php83-tokenizer, php83-xmlwriter"
echo ""

# Знайти пакети
DOM_PKG=$(find /home/dima/.cache/yay/php83 -name "php83-dom-*.pkg.tar.zst" 2>/dev/null | head -1)
TOKENIZER_PKG=$(find /home/dima/.cache/yay/php83 -name "php83-tokenizer-*.pkg.tar.zst" 2>/dev/null | head -1)
XMLWRITER_PKG=$(find /home/dima/.cache/yay/php83 -name "php83-xmlwriter-*.pkg.tar.zst" 2>/dev/null | head -1)

if [ -z "$DOM_PKG" ] || [ -z "$TOKENIZER_PKG" ] || [ -z "$XMLWRITER_PKG" ]; then
    echo "⚠️  Пакети не знайдені, спробуємо зібрати..."
    yay -S php83-dom php83-tokenizer php83-xmlwriter --noconfirm --batchinstall --answerclean All --answerdiff None --removemake
    DOM_PKG=$(find /home/dima/.cache/yay/php83 -name "php83-dom-*.pkg.tar.zst" 2>/dev/null | head -1)
    TOKENIZER_PKG=$(find /home/dima/.cache/yay/php83 -name "php83-tokenizer-*.pkg.tar.zst" 2>/dev/null | head -1)
    XMLWRITER_PKG=$(find /home/dima/.cache/yay/php83 -name "php83-xmlwriter-*.pkg.tar.zst" 2>/dev/null | head -1)
fi

if [ -z "$DOM_PKG" ] || [ -z "$TOKENIZER_PKG" ] || [ -z "$XMLWRITER_PKG" ]; then
    echo "❌ Пакети не знайдені після збірки"
    echo "Виконайте вручну через su:"
    echo "  su -"
    echo "  yay -S php83-dom php83-tokenizer php83-xmlwriter"
    exit 1
fi

echo "Знайдені пакети:"
echo "  DOM: $DOM_PKG"
echo "  Tokenizer: $TOKENIZER_PKG"
echo "  XMLWriter: $XMLWRITER_PKG"
echo ""

echo "Встановлюємо через su..."
echo "Виконайте команди:"
echo "  su -"
echo "  pacman -U $DOM_PKG"
echo "  pacman -U $TOKENIZER_PKG"
echo "  pacman -U $XMLWRITER_PKG"
echo "  systemctl restart httpd"
echo "  exit"

