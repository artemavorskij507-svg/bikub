<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $baseQuery = SupportTicket::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        $activeTickets = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->get();

        $resolvedTickets = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->get();

        // Простенький FAQ — пока статичен, потом можем вынести в БД
        $faqItems = [
            [
                'question' => 'Опоздал на заказ или смену — что делать?',
                'answer' => 'Сразу сообщите диспетчеру через чат или звонок, а также создайте тикет с пометкой "Смена/опоздание".',
                'tag' => 'shift',
            ],
            [
                'question' => 'Клиент просит сделать больше работы, чем указано в заказе.',
                'answer' => 'Не соглашайтесь на дополнительные задачи без согласования. Свяжитесь с диспетчером и опишите ситуацию.',
                'tag' => 'order',
            ],
            [
                'question' => 'У меня проблема с оплатой/выплатами.',
                'answer' => 'Проверьте раздел "Кошелёк", а если что-то не так — создайте тикет с темой "Выплаты".',
                'tag' => 'wallet',
            ],
        ];

        return view('lk.support', [
            'activeTickets' => $activeTickets,
            'resolvedTickets' => $resolvedTickets,
            'faqItems' => $faqItems,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ]);

        // роль/контекст пользователя (если есть роли — берём их)
        $roleContext = null;
        if (method_exists($user, 'roles') && $user->roles()->exists()) {
            $roleContext = $user->roles()->pluck('name')->implode(',');
        } elseif (property_exists($user, 'primary_role')) {
            $roleContext = $user->primary_role;
        } elseif (property_exists($user, 'role')) {
            $roleContext = $user->role;
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'role_context' => $roleContext,
            'subject' => $data['subject'],
            'message' => $data['message'], // В БД колонка называется message
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'source' => 'web_form',
            'channel' => 'worker_lk',
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ],
        ]);

        // можно залогировать, но БЕЗ dd()
        \Log::info('Worker support ticket created', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        return redirect()
            ->route('lk.support.tickets.show', $ticket)
            ->with('status', 'Запрос в поддержку создан.');
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->load(['messages.user']);

        return view('lk.support-show', [
            'ticket' => $ticket,
            'messages' => $ticket->messages,
        ]);
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $msg = new SupportTicketMessage([
            'user_id' => $user->id,
            'sender_type' => 'worker',
            'message' => $data['message'],
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        $ticket->messages()->save($msg);

        if ($ticket->status !== 'open' && $ticket->status !== 'in_progress') {
            $ticket->status = 'open';
            $ticket->save();
        }

        return redirect()
            ->route('lk.support.tickets.show', $ticket)
            ->with('status', 'Сообщение отправлено.');
    }
}
