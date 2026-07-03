<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectProductionSecurityGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! app()->isProduction()) {
            return $response;
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $content = (string) $response->getContent();
        if ($content === '' || ! str_contains(strtolower($content), '</body>')) {
            return $response;
        }

        if (! str_contains($content, '__psWebDevtoolsGuardLoaded')) {
            $guardScript = view('filament.security.devtools-guard')->render();
            $content = preg_replace('/<\/body>/i', $guardScript.'</body>', $content, 1) ?? $content;
            $response->setContent($content);
        }

        return $response;
    }
}
