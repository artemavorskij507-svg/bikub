@php($columns = $this->columns)
@php($employees = $this->getEmployees())
<div class="space-y-4">
    <div class="flex gap-4 overflow-x-auto">
        @foreach ($columns as $col)
            @php($items = $this->getTasksByStatus()[$col] ?? [])
            <div class="min-w-[280px] w-[320px] border rounded bg-white">
                <div class="px-3 py-2 font-semibold bg-gray-50 uppercase text-xs">{{ $col }} ({{ count($items) }})</div>
                <div class="p-2 space-y-2 max-h-[70vh] overflow-y-auto">
                    @forelse ($items as $t)
                        <div class="border rounded p-2 bg-gray-50">
                            <div class="text-sm font-semibold">#{{ $t->id }} • {{ $t->type }}</div>
                            <div class="text-xs text-gray-500">order {{ $t->order_id }} • {{ optional($t->window_start)->format('m-d H:i') }} → {{ optional($t->window_end)->format('H:i') }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <form wire:submit.prevent="quickMove({{ $t->id }}, $event.target.elements.to.value)">
                                    <select name="to" class="text-xs border rounded px-1 py-0.5">
                                        @foreach ($columns as $opt)
                                            <option value="{{ $opt }}" @selected($opt === $col)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="ml-1 text-xs px-2 py-0.5 border rounded">Move</button>
                                </form>
                                <form wire:submit.prevent="quickAssign({{ $t->id }}, parseInt($event.target.elements.assignee_id.value) || null)">
                                    <select name="assignee_id" class="text-xs border rounded px-1 py-0.5">
                                        <option value="">—</option>
                                        @foreach ($employees as $id => $name)
                                            <option value="{{ $id }}" @selected($t->assignee_id == $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="ml-1 text-xs px-2 py-0.5 border rounded">Assign</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-gray-400 p-2">empty</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

