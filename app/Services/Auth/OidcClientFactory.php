<?php

namespace App\Services\Auth;

use RuntimeException;

class OidcClientFactory
{
    public function make(?array $config): OidcProvider
    {
        if (empty($config)) {
            throw new RuntimeException('OIDC provider configuration is missing.');
        }

        return new OidcProvider($config);
    }
}
