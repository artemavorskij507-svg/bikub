<?php

namespace App\Services\SocialCare;

use App\Models\ClientProfile;
use App\Models\TrustedContact;
use App\Models\User;

class CareContactResolver
{
    public function resolveTrustedContactFor(User $user, ?ClientProfile $client): ?TrustedContact
    {
        if (! $client) {
            return null;
        }

        if ($client->user_id === $user->id) {
            return null;
        }

        return $client->trustedContacts()
            ->where('user_id', $user->id)
            ->first();
    }
}
