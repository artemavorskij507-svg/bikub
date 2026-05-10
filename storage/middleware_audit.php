<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$k = app(App\Http\Kernel::class);
$ref = new ReflectionClass($k);
$props = ['middleware', 'middlewareGroups', 'routeMiddleware', 'middlewareAliases'];
$issues = [];

foreach ($props as $propName) {
    if (!$ref->hasProperty($propName)) continue;
    $prop = $ref->getProperty($propName);
    $prop->setAccessible(true);
    $value = $prop->getValue($k);

    $iter = [];
    if ($propName === 'middleware') {
        foreach ($value as $mw) $iter[] = [$propName, $mw];
    } elseif ($propName === 'middlewareGroups') {
        foreach ($value as $group => $list) foreach ($list as $mw) $iter[] = ["group:$group", $mw];
    } else {
        foreach ($value as $alias => $mw) $iter[] = ["alias:$alias", $mw];
    }

    foreach ($iter as [$source, $mw]) {
        if (!is_string($mw)) continue;
        $class = explode(':', $mw, 2)[0];
        if (str_contains($class, '\\') && !class_exists($class)) {
            $issues[] = ['source' => $source, 'middleware' => $mw, 'problem' => 'Class not found'];
        }
    }
}

echo json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
