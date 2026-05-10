<?php

namespace App\Modules\Logistics\Models;

use App\Models\User;

class Customer extends User
{
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'user_id');
    }
}
