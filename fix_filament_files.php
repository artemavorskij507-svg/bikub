<?php

$directory = __DIR__.'/app/Filament';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($regex as $file) {
    $filePath = $file[0];
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Fix one-line header
    $content = preg_replace('/^<\?phpnamespace/', "<?php\n\nnamespace", $content);

    // Fix imports for Resources (v3 -> v2)
    $content = str_replace('use Filament\Forms\Form;', 'use Filament\Resources\Form;', $content);
    $content = str_replace('use Filament\Tables\Table;', 'use Filament\Resources\Table;', $content);

    // Fix Pages (v3 -> v2)
    $content = str_replace('use Filament\Actions;', 'use Filament\Pages\Actions;', $content);
    $content = str_replace('protected function getHeaderActions(): array', 'protected function getActions(): array', $content);

    // Fix direct usage of Filament\Actions namespace if any
    $content = str_replace('Filament\Actions\\', 'Filament\Pages\Actions\\', $content);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Fixed: $filePath\n";
    }
}

echo "Done.\n";
