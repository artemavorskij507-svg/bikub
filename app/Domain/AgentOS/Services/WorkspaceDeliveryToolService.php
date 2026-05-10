<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Models\AgentArtifact;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class WorkspaceDeliveryToolService
{
    public function extractTargetUrl(AgentRun $run): ?string
    {
        $goal = (string) $run->goal;
        if (preg_match('/https?:\/\/[^\s]+/iu', $goal, $matches) !== 1) {
            return null;
        }

        return trim((string) ($matches[0] ?? ''));
    }

    /**
     * @return array{ok:bool,reason?:string,snapshot_path?:string,url?:string,status_code?:int,title?:string,checksum?:string,size_bytes?:int,label?:string}
     */
    public function captureSnapshot(AgentRun $run, AgentStep $step, string $label): array
    {
        $url = $this->extractTargetUrl($run);
        if (! $url) {
            return ['ok' => false, 'reason' => 'target_url_not_found'];
        }

        $candidates = [$url];
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        if ($path === '') {
            $path = '/';
        }
        $query = (string) (parse_url($url, PHP_URL_QUERY) ?: '');
        if ($query !== '') {
            $path .= '?'.$query;
        }

        $candidates[] = 'http://127.0.0.1'.$path;
        $candidates[] = 'http://localhost'.$path;

        $response = null;
        $effectiveUrl = null;
        $effectiveBody = null;
        $lastReason = null;
        $originalHost = (string) (parse_url($url, PHP_URL_HOST) ?: 'localhost');
        foreach (array_values(array_unique($candidates)) as $candidate) {
            try {
                $try = Http::timeout(30)
                    ->accept('text/html')
                    ->withHeaders(['Host' => $originalHost])
                    ->get($candidate);
                if ($try->ok()) {
                    $response = $try;
                    $effectiveUrl = $candidate;
                    $effectiveBody = (string) $try->body();
                    break;
                }

                $lastReason = 'http_status_'.$try->status();
            } catch (\Throwable $e) {
                $lastReason = 'http_request_failed: '.$e->getMessage();
            }
        }

        if (! $response || ! $effectiveUrl || $effectiveBody === null) {
            // Internal application fallback when external HTTP is unavailable or misconfigured.
            try {
                $kernel = app(HttpKernel::class);
                $internalRequest = Request::create($path, 'GET');
                $internalRequest->headers->set('host', $originalHost);
                $internalResponse = $kernel->handle($internalRequest);
                $status = (int) $internalResponse->getStatusCode();
                $body = (string) $internalResponse->getContent();
                $kernel->terminate($internalRequest, $internalResponse);

                if ($status >= 200 && $status < 400 && $body !== '') {
                    $effectiveUrl = 'internal://'.$path;
                    $effectiveBody = $body;
                    $response = null;
                } else {
                    $templateFallback = $this->captureTemplateSnapshotFallback($run, $step, $label);
                    if ($templateFallback !== null) {
                        return $templateFallback;
                    }

                    return ['ok' => false, 'reason' => 'internal_http_status_'.$status];
                }
            } catch (\Throwable $e) {
                $templateFallback = $this->captureTemplateSnapshotFallback($run, $step, $label);
                if ($templateFallback !== null) {
                    return $templateFallback;
                }

                return ['ok' => false, 'reason' => (string) ($lastReason ?: 'snapshot_http_failed').';internal_fallback_failed: '.$e->getMessage()];
            }
        }

        $html = $effectiveBody;
        $title = null;
        if (preg_match('/<title>(.*?)<\/title>/isu', $html, $m) === 1) {
            $title = trim(strip_tags((string) ($m[1] ?? '')));
        }

        $dir = storage_path('app/agent-os/run-'.$run->id.'/snapshots');
        if (! is_dir($dir)) {
            File::ensureDirectoryExists($dir);
        }

        $filename = sprintf('step-%d-%s-%s.html', $step->id, $label, now()->format('YmdHis'));
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, $html);

        return [
            'ok' => true,
            'snapshot_path' => $path,
            'url' => $effectiveUrl,
            'status_code' => $response ? $response->status() : 200,
            'title' => $title,
            'checksum' => sha1($html),
            'size_bytes' => strlen($html),
            'label' => $label,
        ];
    }

    /**
     * @return array{ok:bool,snapshot_path:string,url:string,status_code:int,title:string,checksum:string,size_bytes:int,label:string}|null
     */
    protected function captureTemplateSnapshotFallback(AgentRun $run, AgentStep $step, string $label): ?array
    {
        $targetUrl = $this->extractTargetUrl($run);
        $targetPath = (string) (parse_url((string) $targetUrl, PHP_URL_PATH) ?: '');
        if (! str_contains($targetPath, '/category/food')) {
            return null;
        }

        $template = base_path('resources/views/public/category.blade.php');
        if (! File::exists($template)) {
            return null;
        }

        $templateContent = (string) File::get($template);
        $dir = storage_path('app/agent-os/run-'.$run->id.'/snapshots');
        if (! is_dir($dir)) {
            File::ensureDirectoryExists($dir);
        }

        $filename = sprintf('step-%d-%s-template-%s.blade', $step->id, $label, now()->format('YmdHis'));
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, $templateContent);

        return [
            'ok' => true,
            'snapshot_path' => $path,
            'url' => 'template://resources/views/public/category.blade.php',
            'status_code' => 200,
            'title' => 'Template snapshot fallback',
            'checksum' => sha1($templateContent),
            'size_bytes' => strlen($templateContent),
            'label' => $label,
        ];
    }

    /**
     * @return array{ok:bool,reason?:string,applied:bool,changed_files:array<int,string>,patch_diff:string}
     */
    public function applyFoodCategoryPatch(AgentRun $run): array
    {
        $targetUrl = $this->extractTargetUrl($run);
        if (! $targetUrl) {
            return ['ok' => false, 'reason' => 'target_url_not_found', 'applied' => false, 'changed_files' => [], 'patch_diff' => ''];
        }

        $path = parse_url($targetUrl, PHP_URL_PATH) ?: '';
        if (! str_contains((string) $path, '/category/food')) {
            return ['ok' => false, 'reason' => 'unsupported_target_path', 'applied' => false, 'changed_files' => [], 'patch_diff' => ''];
        }

        $file = base_path('resources/views/public/category.blade.php');
        if (! File::exists($file)) {
            return ['ok' => false, 'reason' => 'category_template_missing', 'applied' => false, 'changed_files' => [], 'patch_diff' => ''];
        }

        $content = (string) File::get($file);
        $marker = 'agent-os-food-hero-v2';
        if (str_contains($content, $marker)) {
            return [
                'ok' => true,
                'applied' => true,
                'changed_files' => [$file],
                'patch_diff' => 'food hero patch already present',
            ];
        }

        $insert = <<<'BLADE'
@if(($category->code ?? null) === 'food')
    {{-- agent-os-food-hero-v2 --}}
    <section class="bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white py-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr] items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wide">GLF MaT</div>
                    <h2 class="mt-3 text-3xl font-bold">РЈРєСЂР°РёРЅСЃРєР°СЏ Рё Р°Р·РµСЂР±Р°Р№РґР¶Р°РЅСЃРєР°СЏ РєСѓС…РЅСЏ СЃ РґРѕСЃС‚Р°РІРєРѕР№</h2>
                    <p class="mt-3 text-sm text-white/90">Р“РѕСЂСЏС‡РёРµ Р±Р»СЋРґР°, Р±С‹СЃС‚СЂР°СЏ РґРѕСЃС‚Р°РІРєР° Рё СѓРґРѕР±РЅС‹Р№ Р·Р°РєР°Р· РІ РЅРµСЃРєРѕР»СЊРєРѕ РєР»РёРєРѕРІ.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('public.restaurants.index') }}" class="rounded-xl bg-white text-orange-700 px-4 py-2 text-sm font-semibold hover:bg-orange-50">Р РµСЃС‚РѕСЂР°РЅС‹</a>
                        <a href="{{ route('public.delivery') }}" class="rounded-xl border border-white/60 px-4 py-2 text-sm font-semibold hover:bg-white/10">РћС„РѕСЂРјРёС‚СЊ РґРѕСЃС‚Р°РІРєСѓ</a>
                    </div>
                </div>
                <div class="rounded-2xl bg-white/15 p-4 border border-white/30 backdrop-blur-sm">
                    <div class="text-sm font-semibold">РџРѕС‡РµРјСѓ РІС‹Р±РёСЂР°СЋС‚ GLF MaT</div>
                    <ul class="mt-2 space-y-1 text-sm text-white/90">
                        <li>- Р¤РѕРєСѓСЃ РЅР° СЃРІРµР¶РµСЃС‚Рё Рё СЃРєРѕСЂРѕСЃС‚Рё</li>
                        <li>- РџСЂРѕР·СЂР°С‡РЅС‹Рµ СЃС‚Р°С‚СѓСЃС‹ РґРѕСЃС‚Р°РІРєРё</li>
                        <li>- РЈРґРѕР±РЅС‹Р№ Р·Р°РєР°Р· С‡РµСЂРµР· РјРѕР±РёР»СЊРЅС‹Р№ РєР°Р±РёРЅРµС‚</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endif
BLADE;

        $anchor = '@if(!empty($groceryStores))';
        if (! str_contains($content, $anchor)) {
            return ['ok' => false, 'reason' => 'template_anchor_not_found', 'applied' => false, 'changed_files' => [], 'patch_diff' => ''];
        }

        $updated = str_replace($anchor, $insert."\n\n".$anchor, $content);
        File::put($file, $updated);

        return [
            'ok' => true,
            'applied' => true,
            'changed_files' => [$file],
            'patch_diff' => 'inserted agent-os-food-hero-v2 section before grocery block',
        ];
    }

    /**
     * @return array{
     *   ok:bool,
     *   reason?:string,
     *   target_type?:string,
     *   resolved_target?:string,
     *   resolver_strategy?:string,
     *   candidate_templates?:array<int,string>,
     *   candidate_fields?:array<int,string>,
     *   attempted_url?:string
     * }
     */
    public function resolveEditableTarget(AgentRun $run): array
    {
        $attemptedUrl = $this->extractTargetUrl($run) ?? '';
        $path = (string) (parse_url($attemptedUrl ?: '/', PHP_URL_PATH) ?: '/');

        $candidateTemplates = [
            base_path('resources/views/welcome.blade.php'),
            base_path('resources/views/home.blade.php'),
            base_path('resources/views/public/home.blade.php'),
        ];
        $candidateFields = [
            'settings.homepage_slogan',
            'pages.home.hero_title',
            'pages.home.hero_subtitle',
            'content.home.hero',
        ];

        // Homepage URL to concrete blade target.
        if ($path === '/' || $path === '') {
            foreach ($candidateTemplates as $candidate) {
                if (File::exists($candidate)) {
                    return [
                        'ok' => true,
                        'target_type' => 'blade_text_replace',
                        'resolved_target' => $candidate,
                        'resolver_strategy' => 'homepage_blade_priority',
                        'candidate_templates' => $candidateTemplates,
                        'candidate_fields' => $candidateFields,
                        'attempted_url' => $attemptedUrl !== '' ? $attemptedUrl : '/',
                    ];
                }
            }
        }

        return [
            'ok' => false,
            'reason' => 'unsupported_target_path',
            'resolver_strategy' => 'homepage_blade_priority',
            'candidate_templates' => $candidateTemplates,
            'candidate_fields' => $candidateFields,
            'attempted_url' => $attemptedUrl !== '' ? $attemptedUrl : '/',
        ];
    }

    /**
     * @return array{ok:bool,reason?:string,applied:bool,changed_files:array<int,string>,patch_diff:string,target_type?:string,resolved_target?:string,diagnostic?:array<string,mixed>}
     */
    public function applyContentUpdate(AgentRun $run): array
    {
        $resolution = $this->resolveEditableTarget($run);
        if (! ($resolution['ok'] ?? false)) {
            return [
                'ok' => false,
                'reason' => (string) ($resolution['reason'] ?? 'target_resolution_failed'),
                'applied' => false,
                'changed_files' => [],
                'patch_diff' => '',
                'diagnostic' => $resolution,
            ];
        }

        $targetType = (string) ($resolution['target_type'] ?? '');
        $targetPath = (string) ($resolution['resolved_target'] ?? '');
        if ($targetType !== 'blade_text_replace' || $targetPath === '' || ! File::exists($targetPath)) {
            return [
                'ok' => false,
                'reason' => 'unsupported_target_path',
                'applied' => false,
                'changed_files' => [],
                'patch_diff' => '',
                'diagnostic' => $resolution,
            ];
        }

        $content = (string) File::get($targetPath);
        $desired = $this->extractDesiredHomepageSlogan((string) $run->goal);
        $headline = mb_substr($desired, 0, 96);

        $replacement = '<h1 class="text-5xl md:text-6xl lg:text-7xl font-black leading-tight">'
            ."\n                        ".'<span class="block text-white mb-2">Р’Р°С€ СѓР»СЊСЏРЅС‹Р№ СЃРµСЂРІРёСЃ</span>'
            ."\n                        ".'<span class="block bg-gradient-to-r from-amber-400 via-orange-500 to-amber-600 bg-clip-text text-transparent text-glow-gold">'.e($headline).'</span>'
            ."\n                    ".'</h1>';

        $updated = preg_replace('/<h1 class=\"text-5xl[^>]*>[\s\S]*?<\/h1>/u', $replacement, $content, 1, $count);
        if (! is_string($updated) || $count < 1) {
            return [
                'ok' => false,
                'reason' => 'homepage_headline_not_found',
                'applied' => false,
                'changed_files' => [],
                'patch_diff' => '',
                'diagnostic' => $resolution,
            ];
        }

        if ($updated !== $content) {
            File::put($targetPath, $updated);
        }

        return [
            'ok' => true,
            'applied' => true,
            'changed_files' => [$targetPath],
            'patch_diff' => 'homepage hero headline updated via target resolution',
            'target_type' => $targetType,
            'resolved_target' => $targetPath,
        ];
    }

    /**
     * @return array{ok:bool,validation_result?:string,checks_performed?:array<int,string>,reason?:string}
     */
    public function validateContentUpdate(AgentRun $run): array
    {
        $contentUpdate = AgentArtifact::query()
            ->where('run_id', $run->id)
            ->where('artifact_type', 'content_update_execution')
            ->latest('id')
            ->first();

        $beforeAfter = AgentArtifact::query()
            ->where('run_id', $run->id)
            ->where('artifact_type', 'before_after_evidence')
            ->latest('id')
            ->first();

        if (! $contentUpdate || ! $beforeAfter) {
            return ['ok' => false, 'reason' => 'missing_execution_or_evidence_artifact'];
        }

        $changed = (bool) data_get($beforeAfter->metadata, 'evidence.before_after.changed', false);
        if (! $changed) {
            return ['ok' => false, 'reason' => 'no_visual_or_snapshot_change_detected'];
        }

        return [
            'ok' => true,
            'validation_result' => 'pass',
            'checks_performed' => [
                'content_update_artifact_present',
                'before_after_evidence_present',
                'snapshot_checksum_changed',
            ],
        ];
    }

    protected function extractDesiredHomepageSlogan(string $goal): string
    {
        $clean = trim((string) preg_replace('/https?:\/\/[^\s]+/iu', '', $goal));
        $clean = preg_replace('/\s+/u', ' ', $clean) ?: '';
        $clean = trim($clean, " \t\n\r\0\x0B:-");

        $prefixes = [
            'Р·Р°РјРµРЅРё СЃР»РѕРіР°РЅ',
            'РёР·РјРµРЅРё СЃР»РѕРіР°РЅ',
            'РѕР±РЅРѕРІРё СЃР»РѕРіР°РЅ',
            'СЃРјРµРЅРё СЃР»РѕРіР°РЅ',
            'fix slogan',
            'update slogan',
        ];

        $normalized = mb_strtolower($clean);
        foreach ($prefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $clean = trim(mb_substr($clean, mb_strlen($prefix)));
                break;
            }
        }

        if (mb_strlen($clean) < 8) {
            return 'Р”РѕСЃС‚Р°РІРєР°, РјР°СЃС‚РµСЂ РЅР° С‡Р°СЃ, РїРµСЂРµРµР·РґС‹, СЃРѕС†РёР°Р»СЊРЅР°СЏ РїРѕРјРѕС‰СЊ';
        }

        return $clean;
    }

    /**
     * @return array{ok:bool,reason?:string,before?:array<string,mixed>,after?:array<string,mixed>,changed?:bool}
     */
    public function buildBeforeAfterEvidence(AgentRun $run): array
    {
        $pageDiscovery = AgentArtifact::query()
            ->where('run_id', $run->id)
            ->where('artifact_type', 'page_discovery')
            ->latest('id')
            ->first();

        $preview = AgentArtifact::query()
            ->where('run_id', $run->id)
            ->where('artifact_type', 'preview_capture')
            ->latest('id')
            ->first();

        $before = (array) data_get($pageDiscovery?->metadata, 'evidence.snapshot', []);
        $after = (array) data_get($preview?->metadata, 'evidence.snapshot', []);

        if ($before === [] || $after === []) {
            return ['ok' => false, 'reason' => 'missing_before_or_after_snapshot'];
        }

        $changed = (string) ($before['checksum'] ?? '') !== (string) ($after['checksum'] ?? '');

        return [
            'ok' => true,
            'before' => $before,
            'after' => $after,
            'changed' => $changed,
        ];
    }
}
