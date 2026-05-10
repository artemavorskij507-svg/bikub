<x-account-layout>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-h-[600px] flex">
        <div class="w-1/3 border-r border-gray-100 bg-gray-50 overflow-y-auto max-h-[600px]">
            <div class="p-4 border-b bg-white font-bold text-gray-700">Inbox</div>
            @foreach($conversations as $adId => $messages)
                @php 
                    $lastMsg = $messages->last(); 
                    $ad = $lastMsg->ad ?? null;
                @endphp
                @if($ad)
                    <div wire:click="selectConversation({{ $adId }})" class="p-4 border-b cursor-pointer hover:bg-white transition {{ $selectedConversation == $adId ? 'bg-white border-l-4 border-l-blue-600' : '' }}">
                        <div class="font-bold text-gray-900 text-sm truncate">{{ $ad->title ?? 'Unknown Ad' }}</div>
                        <div class="text-xs text-gray-500 mb-1">{{ $lastMsg->created_at->diffForHumans() }}</div>
                        <div class="text-sm text-gray-600 truncate">{{ $lastMsg->message }}</div>
                    </div>
                @endif
            @endforeach
            @if($conversations->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">No messages yet.</div>
            @endif
        </div>
        <div class="w-2/3 flex flex-col">
            @if($selectedConversation && isset($conversations[$selectedConversation]))
                @php 
                    $msgs = $conversations[$selectedConversation]->sortBy('created_at'); 
                    $ad = $msgs->first()->ad ?? null;
                @endphp
                @if($ad)
                    <div class="p-4 border-b flex justify-between items-center bg-white">
                        <a href="{{ route('classifieds.show', $ad->slug) }}" class="font-bold text-blue-600 hover:underline">
                            {{ $ad->title }}
                        </a>
                        <span class="text-sm text-green-600 font-bold">{{ $ad->priceFormatted ?? 'По договорённости' }}</span>
                    </div>
                    <div class="flex-grow p-4 overflow-y-auto space-y-4 bg-gray-50">
                        @foreach($msgs as $msg)
                            <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] {{ $msg->sender_id === auth()->id() ? 'bg-blue-600 text-white' : 'bg-white border text-gray-800' }} rounded-2xl px-4 py-2 text-sm shadow-sm">
                                    {{ $msg->message }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-4 border-t bg-white">
                        <div class="flex gap-2">
                            <input type="text" class="flex-grow border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Type a message..." disabled>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold disabled:opacity-50" disabled>Send</button>
                        </div>
                        <div class="text-xs text-gray-400 mt-2 text-center">Chat functionality in Inbox is read-only in this demo patch. Go to Ad page to reply.</div>
                    </div>
                @endif
            @else
                <div class="h-full flex items-center justify-center text-gray-400">
                    Select a conversation to read
                </div>
            @endif
        </div>
    </div>
</x-account-layout>


    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-h-[600px] flex">
        <div class="w-1/3 border-r border-gray-100 bg-gray-50 overflow-y-auto max-h-[600px]">
            <div class="p-4 border-b bg-white font-bold text-gray-700">Inbox</div>
            @foreach($conversations as $adId => $messages)
                @php 
                    $lastMsg = $messages->last(); 
                    $ad = $lastMsg->ad ?? null;
                @endphp
                @if($ad)
                    <div wire:click="selectConversation({{ $adId }})" class="p-4 border-b cursor-pointer hover:bg-white transition {{ $selectedConversation == $adId ? 'bg-white border-l-4 border-l-blue-600' : '' }}">
                        <div class="font-bold text-gray-900 text-sm truncate">{{ $ad->title ?? 'Unknown Ad' }}</div>
                        <div class="text-xs text-gray-500 mb-1">{{ $lastMsg->created_at->diffForHumans() }}</div>
                        <div class="text-sm text-gray-600 truncate">{{ $lastMsg->message }}</div>
                    </div>
                @endif
            @endforeach
            @if($conversations->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">No messages yet.</div>
            @endif
        </div>
        <div class="w-2/3 flex flex-col">
            @if($selectedConversation && isset($conversations[$selectedConversation]))
                @php 
                    $msgs = $conversations[$selectedConversation]->sortBy('created_at'); 
                    $ad = $msgs->first()->ad ?? null;
                @endphp
                @if($ad)
                    <div class="p-4 border-b flex justify-between items-center bg-white">
                        <a href="{{ route('classifieds.show', $ad->slug) }}" class="font-bold text-blue-600 hover:underline">
                            {{ $ad->title }}
                        </a>
                        <span class="text-sm text-green-600 font-bold">{{ $ad->priceFormatted ?? 'По договорённости' }}</span>
                    </div>
                    <div class="flex-grow p-4 overflow-y-auto space-y-4 bg-gray-50">
                        @foreach($msgs as $msg)
                            <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] {{ $msg->sender_id === auth()->id() ? 'bg-blue-600 text-white' : 'bg-white border text-gray-800' }} rounded-2xl px-4 py-2 text-sm shadow-sm">
                                    {{ $msg->message }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-4 border-t bg-white">
                        <div class="flex gap-2">
                            <input type="text" class="flex-grow border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Type a message..." disabled>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold disabled:opacity-50" disabled>Send</button>
                        </div>
                        <div class="text-xs text-gray-400 mt-2 text-center">Chat functionality in Inbox is read-only in this demo patch. Go to Ad page to reply.</div>
                    </div>
            @else
                <div class="h-full flex items-center justify-center text-gray-400">
                    Select a conversation to read
                </div>
            @endif
        </div>
    </div>
</x-account-layout>

