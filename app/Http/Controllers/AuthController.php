<?php
namespace App\Http\Controllers;
use App\Services\GitHubNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class AuthController extends Controller {
    public function login() {
        return view('login', ['title' => 'Khu riêng', 'configured' => is_file(config('notes.credentials'))]);
    }
    public function redirect(Request $request, GitHubNotes $github) {
        $credentials = $github->credentials();
        $state = Str::random(64); $request->session()->put('oauth_state', $state);
        return redirect()->away('https://github.com/login/oauth/authorize?'.http_build_query([
            'client_id' => $credentials['client_id'], 'redirect_uri' => url('/auth/callback'),
            'state' => $state, 'allow_signup' => 'false',
        ]));
    }
    public function callback(Request $request, GitHubNotes $github) {
        $expected = $request->session()->pull('oauth_state');
        abort_unless(is_string($expected) && is_string($request->state) && hash_equals($expected, $request->state), 403, 'Phiên xác thực không hợp lệ. Hãy thử đăng nhập lại.');
        abort_unless(is_string($request->code) && strlen($request->code) < 500, 403);
        $credentials = $github->credentials();
        try {
            $token = Http::asForm()->acceptJson()->connectTimeout(8)->timeout(20)->post('https://github.com/login/oauth/access_token', [
                'client_id' => $credentials['client_id'], 'client_secret' => $credentials['client_secret'],
                'code' => $request->code, 'redirect_uri' => url('/auth/callback'),
            ]);
            abort_unless($token->successful() && $token->json('access_token'), 403, 'GitHub chưa xác thực được. Hãy đăng nhập lại.');
            $user = Http::withToken($token->json('access_token'))->acceptJson()->connectTimeout(8)->timeout(20)->get('https://api.github.com/user');
        } catch (\Illuminate\Http\Client\ConnectionException $e) { abort(503, 'Không kết nối được GitHub. Hãy thử lại.'); }
        abort_unless($user->successful() && (string) $user->json('id') === config('notes.owner_id'), 403, 'Tài khoản này không được phép truy cập.');
        $request->session()->regenerate();
        $request->session()->put(['github_id' => (string) $user->json('id'), 'github_login' => $user->json('login')]);
        return redirect('/private');
    }
    public function logout(Request $request) {
        $request->session()->invalidate(); $request->session()->regenerateToken();
        return response()->view('logout', ['title' => 'Đã đăng xuất']);
    }
}
