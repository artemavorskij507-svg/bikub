<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FastOrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'address' => 'required|string|max:500',
            'comment' => 'nullable|string|max:1000',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
        ]);

        try {
            // Тут можна створити замовлення або зберегти запит
            // Поки що просто логуємо
            Log::info('Fast order request', $validated);

            return redirect()
                ->route('public.home')
                ->with('success', 'Ваш запит прийнято! Ми зв\'яжемося з вами найближчим часом.');
        } catch (\Exception $e) {
            Log::error('Fast order error', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Щось пішло не так. Спробуйте ще раз.');
        }
    }
}
