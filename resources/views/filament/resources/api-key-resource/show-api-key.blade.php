@extends('filament.layouts.page')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-yellow-900 mb-2">⚠️ Save Your API Key Now</h2>
            <p class="text-sm text-yellow-800 mb-4">
                This key will never be shown again. Copy it now and store it securely.
            </p>

            <div class="bg-white border border-yellow-300 rounded p-4 mb-4 font-mono text-sm break-all select-all">
                {{ $plaintext_key }}
            </div>

            <div class="flex gap-2">
                <button 
                    onclick="navigator.clipboard.writeText('{{ $plaintext_key }}').then(() => alert('Copied!'))"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                >
                    📋 Copy to Clipboard
                </button>

                <a 
                    href="{{ App\Filament\Resources\ApiKeyResource::getUrl('index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 text-sm"
                >
                    ✓ I've Saved It
                </a>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-bold text-blue-900 mb-2">📝 Key Details</h3>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="font-semibold text-blue-900">Name:</dt>
                    <dd>{{ $record->name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-blue-900">Owner:</dt>
                    <dd>{{ $record->owner_type }} (ID: {{ $record->owner_id }})</dd>
                </div>
                <div>
                    <dt class="font-semibold text-blue-900">Scopes:</dt>
                    <dd>{{ implode(', ', $record->scopes ?? []) ?: 'None' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-blue-900">Expires At:</dt>
                    <dd>{{ $record->expires_at ? $record->expires_at->format('Y-m-d H:i:s') : 'Never' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
