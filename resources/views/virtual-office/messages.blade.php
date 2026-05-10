@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
                        <p class="text-sm text-gray-500">All virtual office messages</p>
                    </div>
                    <button onclick="document.getElementById('create-message-modal').classList.remove('hidden')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        + New Message
                    </button>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex items-center space-x-4">
                    <input type="text" id="search" placeholder="Search messages..."
                        class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <select id="type-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="text">Text</option>
                        <option value="task">Task</option>
                        <option value="alert">Alert</option>
                        <option value="notification">Notification</option>
                    </select>
                </div>

                <!-- Messages List -->
                <div class="space-y-4">
                    @foreach(\App\Models\VirtualOffice\Message::with(['sender', 'receiver', 'zone'])->latest()->get() as $message)
                        <div class="message-card bg-gray-50 rounded-lg p-6 hover:shadow-lg transition">
                            <div class="flex items-start space-x-4">
                                <!-- Sender Avatar -->
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold"
                                    style="background-color: {{ $message->sender->category->color ?? '#6B7280' }}">
                                    {{ substr($message->sender->name ?? 'U', 0, 2) }}
                                </div>

                                <!-- Message Content -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-medium text-gray-900">{{ $message->sender->name ?? 'Unknown' }}</span>
                                            @if($message->receiver)
                                                <span class="text-gray-500 mx-2">→</span>
                                                <span class="font-medium text-gray-900">{{ $message->receiver->name }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs rounded-full
                                                {{ $message->type === 'text' ? 'bg-gray-100 text-gray-800' : '' }}
                                                {{ $message->type === 'task' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $message->type === 'alert' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $message->type === 'notification' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                                {{ ucfirst($message->type) }}
                                            </span>
                                            <span class="text-sm text-gray-500">{{ $message->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <p class="mt-2 text-gray-700">{{ $message->content }}</p>

                                    @if($message->zone)
                                        <div class="mt-3 flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">Zone:</span>
                                            <a href="{{ route('virtual-office.zones.show', $message->zone) }}"
                                                class="text-sm text-blue-600 hover:text-blue-700">{{ $message->zone->name }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Message Modal -->
<div id="create-message-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Send Message</h2>
        <form action="{{ route('api.virtual-office.messages.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From *</label>
                    <select name="sender_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Sender</option>
                        @foreach(\App\Models\VirtualOffice\Agent::all() as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To (optional)</label>
                    <select name="receiver_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Receiver</option>
                        @foreach(\App\Models\VirtualOffice\Agent::all() as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zone (optional)</label>
                    <select name="zone_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Zone</option>
                        @foreach(\App\Models\VirtualOffice\OfficeZone::all() as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                    <textarea name="content" rows="4" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="text">Text</option>
                        <option value="task">Task</option>
                        <option value="alert">Alert</option>
                        <option value="notification">Notification</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-message-modal').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Send Message
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
