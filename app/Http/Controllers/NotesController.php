<?php
namespace App\Http\Controllers;
use App\Services\GitHubNotes;
use App\Services\Markdown;
use Illuminate\Http\Request;
class NotesController extends Controller {
    public function index(Request $request, GitHubNotes $github) {
        $data = $request->validate(['folder' => ['nullable','string','max:500'], 'page' => ['nullable','integer','min:1']]);
        $folder = trim($data['folder'] ?? '', '/'); $prefix = $folder === '' ? '' : $folder.'/';
        $notes = array_filter($github->tree(), fn ($item) => preg_match('/\.mdx?$/i', $item['path']));
        $files = []; $folders = [];
        foreach ($notes as $note) {
            if (!str_starts_with($note['path'], $prefix)) continue;
            $remaining = substr($note['path'], strlen($prefix));
            if (str_contains($remaining, '/')) $folders[$prefix.explode('/', $remaining, 2)[0]] = true;
            else $files[] = $note;
        }
        usort($files, fn ($a, $b) => strnatcasecmp($a['path'], $b['path']));
        $folders = array_keys($folders); natcasesort($folders);
        $page = $data['page'] ?? 1; $total = count($files); $files = array_slice($files, ($page-1)*50, 50);
        return view('private-index', ['title' => 'Ghi chú riêng', 'files' => $files, 'folders' => $folders, 'folder' => $folder, 'page' => $page, 'total' => $total, 'private' => true]);
    }
    public function note(Request $request, GitHubNotes $github, Markdown $markdown) {
        $data = $request->validate(['path' => ['required','string','max:500']]);
        $path = $data['path']; $github->validatePath($path);
        $note = $github->read($path); $rendered = $markdown->render($note['content'], $path, array_column($github->tree(), 'path'));
        return view('note', [...$rendered, 'title' => $rendered['title'] ?: basename($path), 'note' => $note, 'private' => true]);
    }
    public function create() {
        return view('note', ['title' => 'Tạo ghi chú', 'html' => '', 'toc' => [], 'note' => ['path' => '', 'content' => '', 'sha' => null], 'private' => true, 'creating' => true]);
    }
    public function save(Request $request, GitHubNotes $github) {
        $data = $request->validate(['path' => ['required','string','max:500'], 'content' => ['present','string','max:2000000'], 'sha' => ['present','nullable','regex:/^[a-f0-9]{40}$/']]);
        $result = $github->save($data['path'], $data['content'], $data['sha']);
        return response()->json($result, isset($result['conflict']) ? 409 : 200);
    }
    public function preview(Request $request, Markdown $markdown, GitHubNotes $github) {
        $data = $request->validate(['path' => ['required','string','max:500'], 'content' => ['present','string','max:2000000']]);
        $github->validatePath($data['path']); return response()->json($markdown->render($data['content'], $data['path'], array_column($github->tree(), 'path')));
    }
    public function search(Request $request, GitHubNotes $github) {
        $data = $request->validate(['q' => ['nullable','string','max:120']]);
        return view('search', ['title' => 'Tìm trong khu riêng', 'q' => $data['q'] ?? '', 'results' => $github->search($data['q'] ?? ''), 'private' => true]);
    }
    public function asset(Request $request, GitHubNotes $github) {
        $data = $request->validate(['path' => ['required','string','max:500']]);
        $file = $github->read($data['path'], true);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file['content']);
        abort_unless(in_array($mime, ['image/png','image/jpeg','image/gif','image/webp','image/avif']), 415);
        return response($file['content'])->header('Content-Type', $mime)->header('Content-Disposition', 'inline');
    }
}
