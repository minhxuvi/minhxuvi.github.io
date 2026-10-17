<?php
use App\Http\Controllers\{AuthController, NotesController};
use App\Http\Middleware\PrivateAccess;
use App\Services\PublicContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::get('/auth/login', [AuthController::class, 'login'])->name('login');
Route::get('/auth/github', [AuthController::class, 'redirect'])->middleware('throttle:10,1');
Route::get('/auth/callback', [AuthController::class, 'callback'])->middleware('throttle:20,1');
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::prefix('private')->middleware([PrivateAccess::class, 'throttle:120,1'])->group(function () {
    Route::get('/', [NotesController::class, 'index']);
    Route::get('/note', [NotesController::class, 'note']);
    Route::get('/new', [NotesController::class, 'create']);
    Route::get('/search', [NotesController::class, 'search']);
    Route::get('/asset', [NotesController::class, 'asset']);
    Route::post('/api/save', [NotesController::class, 'save'])->middleware('throttle:20,1');
    Route::post('/api/preview', [NotesController::class, 'preview']);
});
Route::get('/search', function (Request $request, PublicContent $content) {
    $data = $request->validate(['q' => ['nullable','string','max:120']]);
    return view('search', ['title' => 'Tìm nội dung công khai', 'q' => $data['q'] ?? '', 'results' => $content->search($data['q'] ?? '')]);
});
Route::get('/{slug?}', function (?string $slug = null) {
    abort_if($slug && preg_match('~^(private|auth|setup)(/|$)~', $slug), 404);
    $pages = app(PublicContent::class)->pages(); $url = '/'.trim($slug ?? '', '/');
    if (!isset($pages[$url])) {
        $canonical = preg_replace('~(?:/index)?\.mdx?$~', '', $url);
        if (isset($pages[$canonical])) return redirect($canonical, 301);
        abort(404);
    }
    return view('page', $pages[$url]);
})->where('slug', '.*');
