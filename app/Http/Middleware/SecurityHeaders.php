<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class SecurityHeaders {
    public function handle(Request $request, Closure $next) {
        $response = $next($request);
        $private = $request->is('private', 'private/*', 'auth/*', 'setup/*');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' ".($private ? '' : 'https: ')."; connect-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'none'; form-action 'self' https://github.com; frame-ancestors 'none'");
        if ($request->secure()) $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        if ($private) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Vary', 'Cookie');
        }
        return $response;
    }
}
