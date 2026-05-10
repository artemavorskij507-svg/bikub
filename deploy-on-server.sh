#!/bin/bash
# Quick deployment script - run this on the server to update template

set -e

echo "🚀 BiKuBe Marketplace Deployment"
echo "================================"

REPO_DIR="/var/www/bikube"
TEMPLATE_FILE="$REPO_DIR/resources/views/public/delivery-market.blade.php"

# Check if file exists
if [ ! -f "$REPO_DIR/app/Http/Controllers/PublicController.php" ]; then
    echo "❌ Error: Not a valid BiKuBe directory"
    exit 1
fi

echo "✓ BiKuBe directory found"

# If deployed via Git
if [ -d "$REPO_DIR/.git" ]; then
    echo "📦 Pulling latest code from Git..."
    cd "$REPO_DIR"
    git pull origin main
    echo "✓ Git pull complete"
fi

# Clear Laravel cache
echo "🧹 Clearing Laravel cache..."
php artisan view:clear
php artisan config:cache
echo "✓ Cache cleared"

# Verify template exists
if [ -f "$TEMPLATE_FILE" ]; then
    SIZE=$(stat -c%s "$TEMPLATE_FILE" 2>/dev/null || stat -f%z "$TEMPLATE_FILE" 2>/dev/null)
    echo "✓ Template deployed: $TEMPLATE_FILE ($SIZE bytes)"
else
    echo "⚠️  Warning: Template file not found"
fi

# Test the route
echo ""
echo "🔍 Testing deployment..."
if curl -s http://localhost/category/delivery | grep -q "deliveryMarketplace"; then
    echo "✅ SUCCESS! Marketplace is live"
    echo "📍 URL: http://136.119.84.22/category/delivery"
else
    echo "⚠️  Template might not be loaded yet"
fi

echo ""
echo "✅ Deployment complete!"
