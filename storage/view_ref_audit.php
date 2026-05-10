<?php
$root = __DIR__;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app'));
$issues = [];
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) continue;
    if (preg_match_all("/view\(\s*['\"]([^'\"]+)['\"]/", $content, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as [$viewName, $offset]) {
            if (str_contains($viewName, '::') || str_contains($viewName, '$')) continue;
            $viewPath = $root . '/resources/views/' . str_replace('.', '/', $viewName) . '.blade.php';
            if (!file_exists($viewPath)) {
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                $issues[] = ['file' => str_replace('\\','/',$path), 'line' => $line, 'view' => $viewName, 'expected' => str_replace('\\','/',$viewPath)];
            }
        }
    }
}
echo json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
