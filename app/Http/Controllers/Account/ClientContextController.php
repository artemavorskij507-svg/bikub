<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountContextManager;
use App\Services\SocialCare\CareAccountReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientContextController extends Controller
{
    public function switch(
        Request $request,
        CareAccountReadService $careRead,
        AccountContextManager $contextManager
    ): RedirectResponse {
        $user = $request->user();

        $data = $request->validate([
            'client_profile_id' => ['nullable', 'integer'],
        ]);

        $clients = $careRead->getClientsForUser($user);

        if (empty($data['client_profile_id'])) {
            $contextManager->clear();

            return back();
        }

        $client = $clients->firstWhere('id', (int) $data['client_profile_id']);

        if (! $client) {
            abort(403, 'Вы не можете управлять этим профилем');
        }

        $contextManager->setActiveClient($user, $client);

        return back();
    }
}
