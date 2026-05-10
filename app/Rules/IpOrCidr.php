<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IpOrCidr implements Rule
{
    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        $value = trim($value);

        // Check plain IPv4
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        // Check CIDR format e.g. 203.0.113.0/24
        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,2})$/', $value, $m)) {
            $ip = $m[1];
            $mask = (int) $m[2];

            // Validate each octet
            $parts = explode('.', $ip);
            foreach ($parts as $p) {
                if ((int) $p < 0 || (int) $p > 255) {
                    return false;
                }
            }

            if ($mask < 0 || $mask > 32) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute must be a valid IPv4 address or CIDR range (e.g. 203.0.113.5 or 203.0.113.0/24).';
    }
}
