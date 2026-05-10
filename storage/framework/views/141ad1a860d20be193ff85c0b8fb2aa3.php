<?php if (isset($component)) { $__componentOriginalcb9b2192b9152278a357ab8b3656b740 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb9b2192b9152278a357ab8b3656b740 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.page','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filament::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php
        $chief = $this->chiefAgent;
        $poll = max(2, (int) config('agent-os.chat.poll_interval_seconds', 5));
        $summary = $activeRunSummary ?? [];
        $goal = (string) ($summary['goal'] ?? '');
    ?>

    <?php if(! $chief): ?>
        <div class="space-y-2 rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-900 dark:bg-danger-950/30">
            <p class="text-danger-600 dark:text-danger-400">
                Координатор не найден. Ожидаемый slug:
                <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">director-agent</code>
            </p>
        </div>
    <?php else: ?>
        <div
            wire:poll.<?php echo e($poll); ?>s="refreshRunProgress"
            x-data="{
                echoBound: false,
                bindEcho() {
                    if (this.echoBound || typeof window.Echo === 'undefined') {
                        return;
                    }
                    const orgId = <?php echo \Illuminate\Support\Js::from((string) (auth()->user()?->organization_id ?? ''))->toHtml() ?>;
                    if (!orgId) {
                        return;
                    }
                    try {
                        window.Echo.private(`agent-os.organization.${orgId}`)
                            .listen('.run.event.appended', () => { $wire.refreshRunProgress(); })
                            .listen('.run.progress.updated', () => { $wire.refreshRunProgress(); })
                            .listen('.step.status.changed', () => { $wire.refreshRunProgress(); })
                            .listen('.run.terminal.reached', () => { $wire.refreshRunProgress(); });
                        this.echoBound = true;
                    } catch (_) {
                        // polling fallback remains active
                    }
                }
            }"
            x-init="bindEcho()"
            class="space-y-4"
        >
            <section class="rounded-2xl border border-primary-200 bg-gradient-to-r from-primary-50 to-sky-50 p-4 dark:border-primary-900/60 dark:from-gray-900 dark:to-gray-950">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="text-sm font-medium text-primary-700 dark:text-primary-300">Agent Workspace v2</div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo e($goal !== '' ? $goal : 'Ожидание новой цели'); ?></h2>
                        <div class="text-xs text-gray-600 dark:text-gray-300">Run #<?php echo e($activeRunId ?? '-'); ?> · <?php echo e($activeRunStatus ?? 'idle'); ?></div>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-white px-3 py-2 text-xs font-semibold text-success-700 dark:border-success-900 dark:bg-gray-900 dark:text-success-300">
                        <span class="inline-flex h-2 w-2 rounded-full bg-success-500"></span>
                        <?php echo e($chief->name); ?> online
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs lg:grid-cols-5">
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[10px] uppercase text-gray-500">Прогресс</div>
                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo e((int) ($summary['progress_percent'] ?? 0)); ?>%</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[10px] uppercase text-gray-500">Risk</div>
                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo e($summary['risk_level'] ?? 'medium'); ?></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[10px] uppercase text-gray-500">Artifacts</div>
                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo e(count($workspaceArtifacts)); ?></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[10px] uppercase text-gray-500">Blockers</div>
                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo e((int) ($summary['steps_blocked'] ?? 0)); ?></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-[10px] uppercase text-gray-500">Confidence</div>
                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo e((int) ($summary['steps_failed'] ?? 0) > 0 ? 'low' : ((int) ($summary['steps_needs_revision'] ?? 0) > 0 ? 'medium' : 'high')); ?></div>
                    </div>
                </div>
            </section>

            <section class="grid h-[calc(100vh-18rem)] grid-cols-1 gap-4 lg:grid-cols-[280px_minmax(0,1fr)_340px]">
                <aside class="flex min-h-0 flex-col gap-3 overflow-hidden rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active Team</div>
                    <div class="space-y-1 overflow-y-auto max-h-32">
                        <?php $__empty_1 = true; $__currentLoopData = $activeTeam; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200"><?php echo e($member); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="text-xs text-gray-500">Нет активных участников.</div>
                        <?php endif; ?>
                    </div>

                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Runs</div>
                    <div class="space-y-1 overflow-y-auto">
                        <?php $__currentLoopData = $recentRuns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button
                                type="button"
                                wire:click="selectRun(<?php echo e($run['id']); ?>)"
                                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'w-full rounded-md border px-2 py-2 text-left text-xs transition',
                                    'border-primary-300 bg-primary-50 text-primary-900 dark:border-primary-700 dark:bg-primary-950/30 dark:text-primary-200' => (int) ($activeRunId ?? 0) === (int) $run['id'],
                                    'border-gray-200 bg-white text-gray-700 hover:border-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200' => (int) ($activeRunId ?? 0) !== (int) $run['id'],
                                ]); ?>"
                            >
                                <div class="font-semibold">Run #<?php echo e($run['id']); ?> · <?php echo e($run['status']); ?></div>
                                <div class="mt-1 text-[11px] opacity-80"><?php echo e($run['progress_percent']); ?>% · <?php echo e($run['updated_at']); ?></div>
                                <div class="mt-1 truncate text-[11px] opacity-70"><?php echo e($run['goal']); ?></div>
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Threads</div>
                    <div class="space-y-1 overflow-y-auto max-h-40">
                        <?php $__currentLoopData = $workspaceThreads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $thread): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button
                                type="button"
                                wire:click="selectThread('<?php echo e($thread['thread_key']); ?>')"
                                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'w-full rounded-md border px-2 py-1 text-left text-xs transition',
                                    'border-primary-300 bg-primary-50 text-primary-800 dark:border-primary-700 dark:bg-primary-950/30 dark:text-primary-200' => $selectedThreadKey === $thread['thread_key'],
                                    'border-gray-200 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' => $selectedThreadKey !== $thread['thread_key'],
                                ]); ?>"
                            >
                                <?php echo e($thread['title']); ?> (<?php echo e($thread['events_count']); ?>)
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Health</div>
                    <div class="grid grid-cols-2 gap-1 text-[11px]">
                        <div class="rounded border border-gray-200 px-2 py-1 dark:border-gray-700"><?php echo e($health['execution_connection'] ?? 'n/a'); ?></div>
                        <div class="rounded border border-gray-200 px-2 py-1 dark:border-gray-700"><?php echo e($health['queue'] ?? 'n/a'); ?></div>
                        <div class="rounded border border-gray-200 px-2 py-1 dark:border-gray-700"><?php echo e($health['horizon_status'] ?? 'unknown'); ?></div>
                        <div class="rounded border border-gray-200 px-2 py-1 dark:border-gray-700"><?php echo e($this->activeAgentsOnlineCount); ?></div>
                    </div>
                </aside>

                <div class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-end gap-2 border-b border-gray-100 p-3 dark:border-gray-800">
                        <button
                            type="button"
                            wire:click="toggleSystemMessages"
                            class="rounded-full border border-gray-200 px-3 py-1 text-xs dark:border-gray-700"
                        >
                            <?php echo e($showSystemMessages ? 'Скрыть системные' : 'Показать системные'); ?>

                        </button>
                    </div>

                    <div
                        x-data="{
                            stick: true,
                            init(){
                                const box = this.$refs.box;
                                if(!box) return;
                                const obs = new MutationObserver(()=>{ if(this.stick){ box.scrollTop = box.scrollHeight; } });
                                obs.observe(box, { childList: true, subtree: true });
                                box.addEventListener('scroll', ()=>{
                                    this.stick = (box.scrollHeight - box.scrollTop - box.clientHeight) < 100;
                                });
                                box.scrollTop = box.scrollHeight;
                            }
                        }"
                        x-ref="box"
                        class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-gray-50/70 p-4 dark:bg-gray-950/40"
                    >
                        <?php $__empty_1 = true; $__currentLoopData = $workspaceEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $level = $event['event_level'] ?? 'info';
                                $bubbleClass = match($level) {
                                    'error' => 'border-danger-300 bg-danger-50 dark:border-danger-900 dark:bg-danger-950/30',
                                    'warning' => 'border-warning-300 bg-warning-50 dark:border-warning-900 dark:bg-warning-950/30',
                                    default => 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800',
                                };
                            ?>
                            <article class="rounded-xl border p-3 <?php echo e($bubbleClass); ?>">
                                <div class="mb-1 flex items-center gap-2 text-[10px] uppercase tracking-wide text-gray-500">
                                    <span><?php echo e($event['actor_key']); ?></span>
                                    <span>·</span>
                                    <span><?php echo e($event['thread_title']); ?></span>
                                    <span>·</span>
                                    <span><?php echo e($event['at']); ?></span>
                                    <span>·</span>
                                    <span><?php echo e($event['event_type']); ?></span>
                                </div>
                                <div class="text-sm text-gray-900 dark:text-gray-100"><?php echo e($event['message']); ?></div>
                                <?php if(!empty($event['payload'])): ?>
                                    <details class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                                        <summary class="cursor-pointer">payload</summary>
                                        <pre class="mt-1 whitespace-pre-wrap"><?php echo e(json_encode($event['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-sm text-gray-500 dark:border-gray-700">
                                Нет событий для выбранного канала.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border-t border-gray-100 bg-gray-50/95 p-3 dark:border-gray-800 dark:bg-gray-900/95">
                        <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
                            <textarea
                                wire:model="message"
                                rows="2"
                                placeholder="Поставьте задачу команде: audit, redesign, patch, validation..."
                                class="min-h-[42px] max-h-40 flex-1 resize-none rounded-xl border-gray-200 bg-white text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                            ></textarea>
                            <button type="submit" class="h-10 rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700">Отправить</button>
                        </form>
                    </div>
                </div>

                <aside class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Artifacts Panel</div>
                    <div class="space-y-2 overflow-y-auto">
                        <?php $__empty_1 = true; $__currentLoopData = $workspaceArtifacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $artifact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $status = $artifact['validation_status'] ?? 'unknown';
                                $statusClass = match($status) {
                                    'pass' => 'bg-success-100 text-success-700 dark:bg-success-950/30 dark:text-success-300',
                                    'fail' => 'bg-danger-100 text-danger-700 dark:bg-danger-950/30 dark:text-danger-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            ?>
                            <article class="rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs dark:border-gray-700 dark:bg-gray-800/60">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">#<?php echo e($artifact['id']); ?> · <?php echo e($artifact['artifact_type']); ?></div>
                                    <span class="rounded px-2 py-0.5 text-[10px] <?php echo e($statusClass); ?>"><?php echo e($status); ?></span>
                                </div>
                                <div class="mt-1 text-[11px] text-gray-500">step: <?php echo e($artifact['step_id']); ?> · <?php echo e($artifact['updated_at']); ?></div>
                                <div class="mt-1 line-clamp-3 text-gray-700 dark:text-gray-300"><?php echo e($artifact['content_preview']); ?></div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-xs text-gray-500 dark:border-gray-700">
                                Артефакты появятся после первых execution-шагов.
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </section>
        </div>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb9b2192b9152278a357ab8b3656b740)): ?>
<?php $attributes = $__attributesOriginalcb9b2192b9152278a357ab8b3656b740; ?>
<?php unset($__attributesOriginalcb9b2192b9152278a357ab8b3656b740); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb9b2192b9152278a357ab8b3656b740)): ?>
<?php $component = $__componentOriginalcb9b2192b9152278a357ab8b3656b740; ?>
<?php unset($__componentOriginalcb9b2192b9152278a357ab8b3656b740); ?>
<?php endif; ?>
<?php /**PATH /var/www/bikube/resources/views/filament/pages/agent-team-chat.blade.php ENDPATH**/ ?>