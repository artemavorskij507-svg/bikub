#!/bin/bash

# Script to migrate service modules to laravel/ directory
# Modules: Moving, Roadside, Handyman, Errand, Delivery, SocialCare, EcoDisposal

set -e

BASE_DIR="/home/dima/Стільниця/glfbikube (1-я копия)"
TARGET_DIR="$BASE_DIR/laravel"

echo "🚀 Starting module migration to laravel/ directory..."

# Define patterns for each module
MODULES=(
    "Moving"
    "Roadside"
    "Handyman"
    "Errand"
    "Delivery"
    "SocialCare"
    "Disposal"
    "EcoDisposal"
    "Eco"
    "Repair"
    "Social"
    "Narvik"
    "Vegvesen"
    "Care"
    "Road"
    "Vehicle"
    "Inspection"
    "Client"
    "Helper"
    "Trusted"
    "Traffic"
    "Travel"
    "Visit"
)

# Function to move files matching pattern
move_files() {
    local pattern=$1
    local source_dir=$2
    local target_subdir=$3
    
    echo "📦 Moving ${pattern} files from ${source_dir} to ${target_subdir}..."
    
    # Create target directory if needed
    mkdir -p "$TARGET_DIR/$target_subdir"
    
    # Find and move files
    find "$BASE_DIR/$source_dir" -iname "*${pattern}*" -type f 2>/dev/null | while read file; do
        # Get relative path from source_dir
        rel_path="${file#$BASE_DIR/$source_dir/}"
        target_path="$TARGET_DIR/$target_subdir/$rel_path"
        
        # Create parent directory
        mkdir -p "$(dirname "$target_path")"
        
        # Move file
        mv "$file" "$target_path"
        echo "  ✓ Moved: $rel_path"
    done
    
    # Find and move directories
    find "$BASE_DIR/$source_dir" -iname "*${pattern}*" -type d 2>/dev/null | while read dir; do
        # Skip if already processed
        if [ ! -d "$dir" ]; then
            continue
        fi
        
        # Get relative path
        rel_path="${dir#$BASE_DIR/$source_dir/}"
        target_path="$TARGET_DIR/$target_subdir/$rel_path"
        
        # Create parent and move
        mkdir -p "$(dirname "$target_path")"
        mv "$dir" "$target_path"
        echo "  ✓ Moved directory: $rel_path"
    done
}

# Migrate each module from each source directory
for module in "${MODULES[@]}"; do
    echo ""
    echo "🔧 Processing module: $module"
    echo "================================"
    
    # Models
    move_files "$module" "app/Models" "app/Models"
    
    # Services
    move_files "$module" "app/Services" "app/Services"
    
    # Controllers
    move_files "$module" "app/Http/Controllers" "app/Http/Controllers"
    
    # Filament Resources
    move_files "$module" "app/Filament/Resources" "app/Filament/Resources"
    
    # Filament Pages
    move_files "$module" "app/Filament/Pages" "app/Filament/Pages"
    
    # Filament Widgets
    move_files "$module" "app/Filament/Widgets" "app/Filament/Widgets"
    
    # Events
    move_files "$module" "app/Events" "app/Events"
    
    # Listeners
    move_files "$module" "app/Listeners" "app/Listeners"
    
    # Notifications
    move_files "$module" "app/Notifications" "app/Notifications"
    
    # Policies
    move_files "$module" "app/Policies" "app/Policies"
    
    # Observers
    move_files "$module" "app/Observers" "app/Observers"
    
    # Console Commands
    move_files "$module" "app/Console/Commands" "app/Console/Commands"
    
    # Middleware
    move_files "$module" "app/Http/Middleware" "app/Http/Middleware"
    
    # Requests
    move_files "$module" "app/Http/Requests" "app/Http/Requests"
    
    # Jobs
    move_files "$module" "app/Jobs" "app/Jobs"
    
    # Config
    move_files "$module" "config" "config"
    
    # Views
    move_files "$module" "resources/views" "resources/views"
    
    # Tests
    move_files "$module" "tests" "tests"
    
    # Seeders
    move_files "$module" "database/seeders" "database/seeders"
    
    # Migrations (lowercase pattern)
    module_lower=$(echo "$module" | tr '[:upper:]' '[:lower:]')
    move_files "$module_lower" "database/migrations" "database/migrations"
done

echo ""
echo "✅ Module migration completed!"
echo "📁 All modules moved to: $TARGET_DIR"
echo ""
echo "⚠️  Next steps:"
echo "   1. Update namespaces in migrated files"
echo "   2. Update autoload paths in composer.json"
echo "   3. Run: composer dump-autoload"
echo ""
