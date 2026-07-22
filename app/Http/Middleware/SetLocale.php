<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if lang parameter is in URL
        if ($request->has('lang')) {
            $lang = $request->get('lang');
            if ($request->hasSession()) {
                session()->put('locale', $lang);
            }
        } else {
            // 2. Check session
            $lang = $request->hasSession() ? session('locale') : null;
            
            // 3. Fallback to app config
            if (!$lang) {
                $lang = config('app.locale');
            }
        }

        // Validate supported locales to prevent setting arbitrary locales
        $supportedLocales = ['en', 'hi', 'gu'];
        if (in_array($lang, $supportedLocales)) {
            App::setLocale($lang);
        }

        return $next($request);
    }
}
