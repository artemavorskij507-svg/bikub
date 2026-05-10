#!/bin/bash
# Remote deployment script - execute on server to pull template

REMOTE_URL="http://raw.githubusercontent.com/bikubeno/bikube/main/resources/views/public/delivery-market.blade.php"
TARGET="/var/www/bikube/resources/views/public/delivery-market.blade.php"

echo "🔄 Downloading template..."
if wget -O "$TARGET" "$REMOTE_URL" 2>/dev/null || curl -o "$TARGET" "$REMOTE_URL" 2>/dev/null; then
    chmod 644 "$TARGET"
    ls -lh "$TARGET"
    echo "✅ Success!"
else
    echo "❌ Download failed"
fi
