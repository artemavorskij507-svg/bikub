<?php

namespace App\Domain\Ops\Actions;

use Illuminate\Http\Request;

class BuildWorkbenchRequestHashAction
{
    public function execute(Request $request, string $actionName, array $extraContext = []): string
    {
        $routeParams = $request->route()?->parameters() ?? [];
        foreach ($routeParams as $key => $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                $routeParams[$key] = $value->getKey();
            }
        }

        $normalized = [
            'action' => $actionName,
            'method' => $request->method(),
            'path' => $request->path(),
            'route_params' => $this->sortRecursive((array) $routeParams),
            'payload' => $this->sortRecursive((array) $request->all()),
            'context' => $this->sortRecursive($extraContext),
        ];

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function sortRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortRecursive($value);
            }
        }
        ksort($data);

        return $data;
    }
}

