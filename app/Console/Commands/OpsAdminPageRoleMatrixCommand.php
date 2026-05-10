<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class OpsAdminPageRoleMatrixCommand extends Command
{
    protected $signature = 'ops:admin-page-role-matrix
        {--emails= : Comma-separated user emails for role matrix run}
        {--paths= : Comma-separated admin paths to probe}
        {--host=136.119.84.22 : Host header used for internal request rendering}
        {--json= : Optional JSON report path}';

    protected $description = 'Run authenticated role matrix for critical admin workbench pages';

    public function handle(): int
    {
        $host = trim((string) $this->option('host'));
        $emails = $this->resolveEmails();
        $paths = $this->resolvePaths();
        $kernel = app(HttpKernel::class);

        $users = User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy(fn (User $user): string => Str::lower((string) $user->email));

        $results = [];

        foreach ($emails as $email) {
            $key = Str::lower($email);
            $user = $users->get($key);

            if (! $user) {
                $results[] = [
                    'email' => $email,
                    'status' => 'missing_user',
                    'roles' => [],
                    'pages' => [],
                ];
                $this->warn("MISSING USER: {$email}");
                continue;
            }

            $pages = [];

            foreach ($paths as $path) {
                try {
                    $request = Request::create($path, 'GET', [], [], [], [
                        'HTTP_HOST' => $host,
                        'REMOTE_ADDR' => '149.7.162.145',
                        'HTTP_USER_AGENT' => 'OpsAdminPageRoleMatrix/1.0',
                    ]);
                    $request->setUserResolver(fn () => $user);
                    app('auth')->guard('web')->setUser($user);

                    $response = $kernel->handle($request);
                    $body = (string) $response->getContent();

                    $pages[] = [
                        'path' => $path,
                        'status' => $response->getStatusCode(),
                        'location' => $response->headers->get('Location'),
                        'access_denied_text' => str_contains(mb_strtolower($body), 'access denied'),
                    ];

                    $kernel->terminate($request, $response);
                } catch (Throwable $e) {
                    $pages[] = [
                        'path' => $path,
                        'status' => 500,
                        'location' => null,
                        'access_denied_text' => false,
                        'exception' => $e->getMessage(),
                    ];
                }
            }

            $status = $this->classify($pages);
            $results[] = [
                'email' => (string) $user->email,
                'status' => $status,
                'roles' => $this->extractRoleNames($user),
                'pages' => $pages,
            ];

            $this->line(strtoupper($status).': '.$user->email);
        }

        $summary = [
            'total' => count($results),
            'ok' => collect($results)->where('status', 'ok')->count(),
            'warn' => collect($results)->where('status', 'warn')->count(),
            'fail' => collect($results)->where('status', 'fail')->count(),
            'missing_user' => collect($results)->where('status', 'missing_user')->count(),
        ];

        $report = [
            'generated_at' => now()->toIso8601String(),
            'host' => $host,
            'emails' => $emails,
            'paths' => $paths,
            'summary' => $summary,
            'results' => $results,
        ];

        $jsonPath = (string) ($this->option('json') ?: storage_path('app/ops-admin-page-role-matrix-report.json'));
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->line('Admin page role matrix summary:');
        $this->line('Total: '.$summary['total']);
        $this->line('OK: '.$summary['ok']);
        $this->line('WARN: '.$summary['warn']);
        $this->line('FAIL: '.$summary['fail']);
        $this->line('MISSING USER: '.$summary['missing_user']);
        $this->line('Report: '.$jsonPath);

        return $summary['fail'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function resolveEmails(): array
    {
        $raw = trim((string) $this->option('emails'));
        if ($raw === '') {
            $raw = implode(',', [
                'keks@glf.no',
                'keks@gfl.no',
                'oleksandr@glf.no',
                'maria@glf.no',
                'eva.nystad@glf.no',
            ]);
        }

        return collect(explode(',', $raw))
            ->map(fn (string $email): string => Str::lower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolvePaths(): array
    {
        $raw = trim((string) $this->option('paths'));
        if ($raw !== '') {
            return collect(explode(',', $raw))
                ->map(fn (string $path): string => trim($path))
                ->filter(fn (string $path): bool => str_starts_with($path, '/admin'))
                ->unique()
                ->values()
                ->all();
        }

        $sidebarPath = base_path('audit/_admin_sidebar_links.json');
        if (File::exists($sidebarPath)) {
            $payload = json_decode((string) File::get($sidebarPath), true);
            if (is_array($payload) && isset($payload['links']) && is_array($payload['links'])) {
                $fromSidebar = collect($payload['links'])
                    ->map(function ($row): ?string {
                        if (! is_array($row) || ! isset($row['href'])) {
                            return null;
                        }

                        $path = (string) parse_url((string) $row['href'], PHP_URL_PATH);
                        if (! str_starts_with($path, '/admin')) {
                            return null;
                        }

                        return $path;
                    })
                    ->filter(fn (?string $path): bool => $path !== null)
                    ->values()
                    ->unique()
                    ->all();

                if (! empty($fromSidebar)) {
                    return $fromSidebar;
                }
            }
        }

        return [
            '/admin/live-operations-map',
            '/admin/service-jobs',
            '/admin/operation-exceptions',
            '/admin/executor-shifts',
            '/admin/executor-breaks',
            '/admin/dispatch-rule-sets',
            '/admin/dispatch-rule-preview',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $pages
     */
    private function classify(array $pages): string
    {
        if (collect($pages)->contains(fn (array $row): bool => (int) ($row['status'] ?? 0) >= 500)) {
            return 'fail';
        }

        if (collect($pages)->contains(function (array $row): bool {
            $status = (int) ($row['status'] ?? 0);
            return in_array($status, [302, 401, 403, 419], true) || (bool) ($row['access_denied_text'] ?? false);
        })) {
            return 'warn';
        }

        return 'ok';
    }

    /**
     * @return array<int, string>
     */
    private function extractRoleNames(User $user): array
    {
        if (method_exists($user, 'getRoleNames')) {
            return collect($user->getRoleNames())->map(fn ($x): string => (string) $x)->values()->all();
        }

        if (method_exists($user, 'roles')) {
            return $user->roles()->pluck('name')->map(fn ($x): string => (string) $x)->values()->all();
        }

        return [];
    }
}
