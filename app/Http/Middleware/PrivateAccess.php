<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class PrivateAccess {
    public function handle(Request $request, Closure $next) {
        $owner = config('notes.owner_id');
        if (!$owner || (string) $request->session()->get('github_id') !== $owner) {
            return $request->expectsJson() ? response()->json(['message' => 'Phiên đăng nhập đã hết. Hãy đăng nhập lại; bản đang soạn vẫn được giữ.'], 401) : redirect('/auth/login');
        }
        return $next($request);
    }
}
