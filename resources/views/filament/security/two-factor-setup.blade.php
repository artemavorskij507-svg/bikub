<x-filament::page>
    <div class="mx-auto max-w-2xl">
        <!-- Main Form -->
        <div class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm">
            {{ $this->form }}
        </div>

        <!-- Recovery Codes Display (shown after successful setup) -->
        @if($this->temp_recovery_codes && count($this->temp_recovery_codes) > 0 && $this->setup_state === 'enabled')
            <div class="fi-section mt-6 rounded-xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-yellow-900">Save Your Recovery Codes</h3>
                        <p class="mt-2 text-sm text-yellow-800">
                            These codes are your backup. Each code can be used once to recover your account if you lose access to your authenticator app. Store them in a safe place.
                        </p>
                        
                        <div class="mt-4 rounded-lg bg-white p-4 font-mono text-sm leading-relaxed text-gray-900">
                            <div class="recovery-codes-content">
                                @foreach($this->temp_recovery_codes as $code)
                                    <div class="py-1">{{ $code }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button 
                                type="button"
                                onclick="copyRecoveryCodes()"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Copy Codes
                            </button>
                            
                            <button 
                                type="button"
                                onclick="downloadRecoveryCodes()"
                                class="inline-flex items-center justify-center rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        function copyRecoveryCodes() {
            const codesElement = document.querySelector('.recovery-codes-content');
            if (!codesElement) return;

            const text = Array.from(codesElement.children)
                .map(el => el.textContent.trim())
                .join('\n');

            navigator.clipboard.writeText(text).then(() => {
                // Show success message
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>Copied!';
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 2000);
            });
        }

        function downloadRecoveryCodes() {
            const codesElement = document.querySelector('.recovery-codes-content');
            if (!codesElement) return;

            const text = Array.from(codesElement.children)
                .map(el => el.textContent.trim())
                .join('\n');

            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
            element.setAttribute('download', 'recovery-codes.txt');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }
    </script>
</x-filament::page>
