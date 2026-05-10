#!/bin/bash

# Script to update namespaces in migrated files
# Changes: App\ -> Laravel\

set -e

LARAVEL_DIR="/home/dima/Стільниця/glfbikube (1-я копия)/laravel"

echo "🔧 Starting namespace update in laravel/ directory..."
echo ""

# Counter
files_updated=0

# Find all PHP files and update namespaces
find "$LARAVEL_DIR" -type f -name "*.php" | while read file; do
    # Check if file contains "namespace App\"
    if grep -q "namespace App\\\\" "$file"; then
        # Update namespace App\ to Laravel\
        sed -i 's/namespace App\\/namespace Laravel\\/g' "$file"
        echo "✓ Updated namespace in: ${file#$LARAVEL_DIR/}"
        ((files_updated++))
    fi
    
    # Update use statements
    if grep -q "use App\\\\" "$file"; then
        # Update use App\ to use Laravel\
        sed -i 's/use App\\/use Laravel\\/g' "$file"
        echo "✓ Updated imports in: ${file#$LARAVEL_DIR/}"
    fi
done

echo ""
echo "✅ Namespace update completed!"
echo ""
echo "📝 Summary:"
echo "   - All 'namespace App\' -> 'namespace Laravel\'"
echo "   - All 'use App\' -> 'use Laravel\'"
echo ""
echo "⚠️  Next steps:"
echo "   1. Review changes: git diff laravel/"
echo "   2. Update composer.json autoload section"
echo "   3. Run: composer dump-autoload"
echo "   4. Test the application"
echo ""
