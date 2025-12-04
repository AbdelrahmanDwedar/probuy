<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', config('translatable.default_locale'));

        // Extract first locale from header (e.g., "en-US,en;q=0.9" -> "en")
        $locale = substr($locale, 0, 2);

        // Validate locale
        if (!array_key_exists($locale, config('translatable.locales'))) {
            $locale = config('translatable.default_locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
