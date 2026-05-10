<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$issues = [];
foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    $action = $route->getActionName();
    if ($action === 'Closure' || str_starts_with($action, 'Livewire\\')) {
        continue;
    }

    if (str_contains($action, '@')) {
        [$class, $method] = explode('@', $action, 2);
        if (!class_exists($class)) {
            $issues[] = ['uri' => $route->uri(), 'name' => $route->getName(), 'action' => $action, 'problem' => 'Controller class not found'];
            continue;
        }
        if (!method_exists($class, $method)) {
            $issues[] = ['uri' => $route->uri(), 'name' => $route->getName(), 'action' => $action, 'problem' => 'Controller method not found'];
            continue;
        }
        $ref = new ReflectionMethod($class, $method);
        if (!$ref->isPublic()) {
            $issues[] = ['uri' => $route->uri(), 'name' => $route->getName(), 'action' => $action, 'problem' => 'Controller method is not public'];
        }
    } else {
        $class = $action;
        if (!class_exists($class)) {
            $issues[] = ['uri' => $route->uri(), 'name' => $route->getName(), 'action' => $action, 'problem' => 'Invokable controller class not found'];
            continue;
        }
        if (!method_exists($class, '__invoke')) {
            $issues[] = ['uri' => $route->uri(), 'name' => $route->getName(), 'action' => $action, 'problem' => 'Invokable controller missing __invoke'];
            continue;
        }
    }
}

echo json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
