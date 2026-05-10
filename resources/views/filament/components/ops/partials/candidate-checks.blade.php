@props([
    'candidate' => [],
])

<div class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-700">
    <div>
        Shift:
        @if(data_get($candidate, 'shift_fit.eligible') === true)
            Pass
        @elseif(data_get($candidate, 'shift_fit.eligible') === false)
            Fail - {{ \App\Support\Ops\DispatchReasonPresenter::label((string) data_get($candidate, 'shift_fit.reason', 'out_of_shift')) }}
        @else
            n/a
        @endif
    </div>
    <div>
        Window:
        @if(data_get($candidate, 'time_window_fit.fits') === true)
            Pass (risk: {{ data_get($candidate, 'time_window_fit.risk', 'low') }})
        @elseif(data_get($candidate, 'time_window_fit.fits') === false)
            Fail - {{ \App\Support\Ops\DispatchReasonPresenter::label('time_window_miss') }}
        @else
            n/a
        @endif
    </div>
    <div>
        Capacity:
        @if(data_get($candidate, 'capacity_fit.fits') === true)
            Pass
        @elseif(data_get($candidate, 'capacity_fit.fits') === false)
            Fail - {{ \App\Support\Ops\DispatchReasonPresenter::label((string) data_get($candidate, 'capacity_fit.reason', 'capacity_mismatch')) }}
        @else
            n/a
        @endif
    </div>
</div>

