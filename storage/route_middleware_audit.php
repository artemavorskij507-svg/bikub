<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$router = app('router');
$aliases = $router->getMiddleware();
$groups = $router->getMiddlewareGroups();
$issues = [];

foreach ($router->getRoutes() as $route) {
    foreach ($route->gatherMiddleware() as $mw) {
        if (!is_string($mw)) {
            continue;
        }

        $name = explode(':', $mw, 2)[0];
        if ($name === '') continue;

        if (isset($aliases[$name]) || isset($groups[$name])) {
            continue;
        }

        if (str_contains($name, '\\')) {
            if (!class_exists($name)) {
                $issues[] = ['uri' => $route->uri(), 'middleware' => $mw, 'problem' => 'Middleware class not found'];
            }
            continue;
        }

        if (!isset($aliases[$name])) {
            $issues[] = ['uri' => $route->uri(), 'middleware' => $mw, 'problem' => 'Middleware alias not registered'];
        }
    }
}

echo json_encode(array_values(array_unique($issues, SORT_REGULAR)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
