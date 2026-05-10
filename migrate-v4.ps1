# Filament v3 to v4 Migration Script
# Automates basic migrations for Filament v4

$TargetDirectory = "D:\Bikube\laravel\app\Filament\Resources"

Write-Host "Starting Filament v3 to v4 Migration..." -ForegroundColor Cyan
Write-Host "Target: $TargetDirectory" -ForegroundColor Yellow

$filesProcessed = 0
$formImports = 0
$tableImports = 0
$badgeColumns = 0

$files = Get-ChildItem -Path $TargetDirectory -Filter "*.php" -Recurse

foreach ($file in $files) {
    Write-Host "`nProcessing: $($file.Name)" -ForegroundColor Green
    
    $content = Get-Content $file.FullName -Raw
    $changed = $false
    
    # Replace Form imports
    if ($content -match 'use Filament\\Resources\\Form;') {
        $content = $content -replace 'use Filament\\Resources\\Form;', 'use Filament\\Forms\\Form;'
        $formImports++
        $changed = $true
        Write-Host "  Form import replaced" -ForegroundColor DarkGreen
    }
    
    # Replace Table imports
    if ($content -match 'use Filament\\Resources\\Table;') {
        $content = $content -replace 'use Filament\\Resources\\Table;', 'use Filament\\Tables\\Table;'
        $tableImports++
        $changed = $true
        Write-Host "  Table import replaced" -ForegroundColor DarkGreen
    }
    
    # Replace BadgeColumn
    if ($content -match 'use Filament\\Tables\\Columns\\BadgeColumn;') {
        if ($content -notmatch 'use Filament\\Tables\\Columns\\TextColumn;') {
            $content = $content -replace '(use Filament\\Tables\\Columns\\BadgeColumn;)', "use Filament\\Tables\\Columns\\TextColumn;`n`$1"
        }
        
        $content = $content -replace 'use Filament\\Tables\\Columns\\BadgeColumn;', '// use Filament\\Tables\\Columns\\BadgeColumn; // v4: migrated to TextColumn'
        
        $badgeCount = ([regex]::Matches($content, 'BadgeColumn::make\(')).Count
        $content = $content -replace 'BadgeColumn::make\(', 'TextColumn::make('
        
        if ($badgeCount -gt 0) {
            $badgeColumns += $badgeCount
            $changed = $true
            Write-Host "  BadgeColumn to TextColumn: $badgeCount occurrences" -ForegroundColor DarkGreen
        }
    }
    
    if ($changed) {
        Set-Content $file.FullName -Value $content -NoNewline
        $filesProcessed++
        Write-Host "  File updated" -ForegroundColor Magenta
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Migration Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Files Processed: $filesProcessed" -ForegroundColor White
Write-Host "Form Imports:    $formImports" -ForegroundColor White
Write-Host "Table Imports:   $tableImports" -ForegroundColor White
Write-Host "Badge Columns:   $badgeColumns" -ForegroundColor White
Write-Host "`nNEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Review all BadgeColumn to TextColumn conversions"
Write-Host "2. Add badge() method to converted TextColumns"
Write-Host "3. Convert colors arrays to color callbacks"
Write-Host "4. Test each resource"
Write-Host "5. Run php artisan filament:optimize"
