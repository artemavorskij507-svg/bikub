<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login()
    {
        return redirect('/admin/login');
    }

    public function logout()
    {
        Auth::logout();

        return redirect('/admin/login');
    }
}
