<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class UserLoyaltyBalance extends Component
{
    /**
     * Whether to show the full card or just the badge
     */
    public bool $full = false;

    /**
     * Number of recent transactions to show
     */
    public int $recentTransactions = 5;

    public function render(): View
    {
        $user = auth()->user();

        if (! $user) {
            return view('livewire.user-loyalty-balance', [
                'balance' => null,
                'transactions' => collect(),
            ]);
        }

        $balance = $user->getOrCreateLoyaltyBalance();
        $transactions = $balance->transactions()
            ->latest()
            ->limit($this->recentTransactions)
            ->get();

        return view('livewire.user-loyalty-balance', [
            'balance' => $balance,
            'transactions' => $transactions,
            'pointsValue' => $balance->getPointsValue($balance->points),
        ]);
    }
}
