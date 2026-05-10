<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;

class LanguageController extends Controller
{
    protected array $availableLocales = [
        'en', 'ru', 'uk', 'ua',
    ];

    public function switch(string $locale): RedirectResponse
    {
        $normalized = strtolower($locale);

        if (! in_array($normalized, $this->availableLocales, true)) {
            abort(404);
        }

        session(['locale' => $normalized]);
        App::setLocale($normalized);

        $previous = url()->previous();

        if (! $previous || $previous === url()->current()) {
            $previous = route('public.home');
        }

        return Redirect::to($previous);
    }
}
