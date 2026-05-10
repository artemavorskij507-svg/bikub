<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    /**
     * Обработка сообщения от ассистента
     */
    public function message(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $message = $request->string('message')->toString();

        // Интеллектуальный ответ на основе контекста пользователя
        $reply = $this->generateSmartReply($user, $message);

        return response()->json([
            'reply' => $reply,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Генерация умного ответа
     */
    private function generateSmartReply(User $user, string $message): string
    {
        $messageLower = strtolower($message);

        // Определяем категорию вопроса
        if ($this->isAboutOrders($messageLower)) {
            return $this->getOrdersInfo($user);
        } elseif ($this->isAboutSchedule($messageLower)) {
            return $this->getScheduleInfo($user);
        } elseif ($this->isAboutEarnings($messageLower)) {
            return $this->getEarningsInfo($user);
        } elseif ($this->isAboutStats($messageLower)) {
            return $this->getStatsInfo($user);
        } elseif ($this->isAboutHelp($messageLower)) {
            return $this->getHelpInfo();
        }

        // Дефолтный ответ
        return $this->getDefaultReply($message);
    }

    /**
     * Информация о заказах
     */
    private function getOrdersInfo(User $user): string
    {
        $activeOrders = Order::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'confirmed'])
            ->count();

        $todayOrders = Order::where('assigned_to', $user->id)
            ->whereDate('created_at', today())
            ->count();

        $completedToday = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();

        if ($activeOrders === 0) {
            return '📭 На данный момент у вас нет активных заказов. Проверьте новые предложения в приложении!';
        }

        return "📦 **Ваши заказы:**\n".
            "🔥 Активных: **$activeOrders**\n".
            "📅 Сегодня создано: **$todayOrders**\n".
            "✅ Выполнено сегодня: **$completedToday**\n\n".
            'Отличный прогресс! Продолжайте в том же духе! 💪';
    }

    /**
     * Информация о графике
     */
    private function getScheduleInfo(User $user): string
    {
        $hasExecutorRole = $user->hasAnyRole(['executor', 'handyman', 'courier']);

        if (! $hasExecutorRole) {
            return '📅 Я не вижу информацию о вашем графике. Если вы работаете исполнителем, обновите роль в профиле.';
        }

        return "📅 **График работы:**\n".
            "Ваш график управляется в разделе 'График и смены'.\n".
            "Там вы можете указать время доступности для получения новых заказов.\n".
            '⏰ Совет: чем больше часов вы указали - тем больше заказов получите!';
    }

    /**
     * Информация о заработках
     */
    private function getEarningsInfo(User $user): string
    {
        $todayEarnings = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->sum('total_amount');

        $weekEarnings = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', '>=', now()->subDays(7))
            ->sum('total_amount');

        $totalEarnings = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->sum('total_amount');

        $formattedToday = number_format($todayEarnings, 0);
        $formattedWeek = number_format($weekEarnings, 0);
        $formattedTotal = number_format($totalEarnings, 0);

        return "💰 **Ваши заработки:**\n".
            "📊 Сегодня: **$formattedToday kr**\n".
            "📈 За неделю: **$formattedWeek kr**\n".
            "🎯 Всего: **$formattedTotal kr**\n\n".
            'Хотите увеличить доход? Выполняйте больше заказов! 📈';
    }

    /**
     * Статистика пользователя
     */
    private function getStatsInfo(User $user): string
    {
        $totalOrders = Order::where('assigned_to', $user->id)->count();
        $completedOrders = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->count();
        $rating = $completedOrders > 0 ? round(($completedOrders / max($totalOrders, 1)) * 100) : 0;

        $activeDeliveries = DeliveryOrder::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->count();

        $stats = "📊 **Ваша статистика:**\n".
            "📦 Всего заказов: **$totalOrders**\n".
            "✅ Выполнено: **$completedOrders**\n".
            "⭐ Рейтинг выполнения: **$rating%**\n";

        if ($activeDeliveries > 0) {
            $stats .= "🚗 Активных доставок: **$activeDeliveries**\n";
        }

        return $stats."\nВы делаете отличную работу! 🌟";
    }

    /**
     * Справка/помощь
     */
    private function getHelpInfo(): string
    {
        return "💡 **Я могу помочь вам с:**\n\n".
            "📦 **Заказы** - информация об активных и выполненных заказах\n".
            "📅 **График** - информация о работе и доступности\n".
            "💰 **Заработки** - статистика по доходам\n".
            "📊 **Статистика** - общая информация о вашей работе\n\n".
            'Просто спросите меня о нужной информации! 🚀';
    }

    /**
     * Дефолтный ответ
     */
    private function getDefaultReply(string $message): string
    {
        $suggestions = [
            'Спросите меня о ваших заказах 📦',
            'Узнайте о своих заработках 💰',
            'Проверьте статистику 📊',
            'Информация о графике 📅',
        ];

        $suggestion = $suggestions[array_rand($suggestions)];

        return "👋 Интересный вопрос!\n\n".
            'Вы спросили: "'.mb_strimwidth($message, 0, 50, '...')."\"\n\n".
            "Мне сложновать с этим, но я могу вам помочь с другим:\n\n".
            "$suggestion\n\n".
            'Введите соответствующий вопрос! 😊';
    }

    /**
     * Проверка: вопрос о заказах?
     */
    private function isAboutOrders(string $message): bool
    {
        $keywords = ['заказ', 'заказы', 'порядок', 'активн', 'новый', 'выполнен'];

        return $this->containsKeywords($message, $keywords);
    }

    /**
     * Проверка: вопрос о графике?
     */
    private function isAboutSchedule(string $message): bool
    {
        $keywords = ['график', 'смен', 'работ', 'доступн', 'расписан', 'время'];

        return $this->containsKeywords($message, $keywords);
    }

    /**
     * Проверка: вопрос о заработках?
     */
    private function isAboutEarnings(string $message): bool
    {
        $keywords = ['заработ', 'доход', 'вывод', 'выплат', 'баланс', 'кошелек', 'деньг'];

        return $this->containsKeywords($message, $keywords);
    }

    /**
     * Проверка: вопрос о статистике?
     */
    private function isAboutStats(string $message): bool
    {
        $keywords = ['статистик', 'рейтинг', 'показатель', 'профиль', 'данные', 'сколько'];

        return $this->containsKeywords($message, $keywords);
    }

    /**
     * Проверка: запрос помощи?
     */
    private function isAboutHelp(string $message): bool
    {
        $keywords = ['помощь', 'что можно', 'помочь', 'как', 'помощи', 'спросить'];

        return $this->containsKeywords($message, $keywords);
    }

    /**
     * Проверка наличия ключевых слов
     */
    private function containsKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
