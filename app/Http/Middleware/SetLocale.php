<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['en', 'am'];

    public function handle(Request $request, Closure $next): Response
    {
        // ?lang=am in a link (e.g. hreflang alternates) wins and is remembered.
        if (in_array($request->query('lang'), self::SUPPORTED, true)) {
            $request->session()->put('locale', $request->query('lang'));
        }

        $locale = $request->session()->get('locale')
            ?? $request->getPreferredLanguage(self::SUPPORTED)
            ?? config('app.locale');

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'en');
        \Carbon\Carbon::setLocale(app()->getLocale());

        return $next($request);
    }
}
