<?php

namespace App\Services;

use App\Exceptions\GitHubFailure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class GitHubNotes
{
    public function credentials(): array
    {
        $file = config('notes.credentials');
        if (!is_file($file)) throw new GitHubFailure(503, 'Chưa cấu hình GitHub App.');
        return json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    private function request(string $method, string $endpoint, array $data = [], ?string $token = null): array
    {
        try {
            $request = Http::baseUrl('https://api.github.com')->withToken($token ?? $this->installationToken())
                ->acceptJson()->withHeaders(['X-GitHub-Api-Version' => '2026-03-10', 'User-Agent' => 'Minh-Notes-Laravel'])
                ->connectTimeout(8)->timeout(25)->withoutRedirecting();
            $response = $method === 'GET' ? $request->get($endpoint, $data) : $request->send($method, $endpoint, ['json' => $data]);
        } catch (ConnectionException $e) { throw new GitHubFailure; }
        if (!$response->successful()) {
            if (in_array($response->status(), [409, 422])) throw new GitHubFailure(409, 'Bản trên GitHub đã đổi hoặc tên file đã tồn tại. Bản đang soạn vẫn được giữ.');
            if ($response->status() === 404) throw new GitHubFailure(404, 'Không tìm thấy file hoặc kho chưa được cấp quyền.');
            throw new GitHubFailure;
        }
        return $response->json();
    }

    private function appToken(): string
    {
        $c = $this->credentials();
        $b64 = fn ($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $jwt = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$b64(json_encode(['iat' => time() - 60, 'exp' => time() + 480, 'iss' => $c['id']]));
        if (!openssl_sign($jwt, $signature, $c['pem'], OPENSSL_ALGO_SHA256)) throw new GitHubFailure;
        return $jwt.'.'.$b64($signature);
    }

    public function installationToken(): string
    {
        $repo = $this->repository();
        return Cache::remember('github-installation-'.hash('sha256', $repo.json_encode(array_intersect_key($this->credentials(), ['id'=>true]))), 2700, function () use ($repo) {
            $jwt = $this->appToken();
            $installation = $this->request('GET', '/repos/'.$repo.'/installation', [], $jwt);
            $token = $this->request('POST', '/app/installations/'.$installation['id'].'/access_tokens', ['repositories' => [explode('/', $repo)[1]], 'permissions' => ['contents' => 'write']], $jwt)['token'];
            $info = $this->request('GET', '/repos/'.$repo, [], $token);
            if (!$info['private']) throw new GitHubFailure(503, 'Kho ghi chú phải là kho private.');
            return $token;
        });
    }

    private function repository(): string
    {
        $repo = config('notes.repository');
        if (config('notes.demo_only') && $repo !== 'minhxuvi/minh-notes-demo') throw new GitHubFailure(503, 'Bản thử chỉ kết nối repo mẫu.');
        if (!in_array($repo, config('notes.allowed_repositories'), true)) throw new GitHubFailure(503, 'Kho chưa được cho phép trong cấu hình.');
        return $repo;
    }

    public function validatePath(string $path, bool $image = false): string
    {
        if (strlen($path) > 500 || preg_match('~[\x00-\x1f\x7f\\\\:<>|]~', $path) || str_starts_with($path, '/') || $path === '') abort(422, 'Đường dẫn không hợp lệ.');
        foreach (explode('/', $path) as $segment) if ($segment === '' || str_starts_with($segment, '.') || $segment !== trim($segment)) abort(422, 'Đường dẫn không hợp lệ.');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $image ? ['png','jpg','jpeg','gif','webp','avif','img'] : ['md','mdx'])) abort(422, 'Định dạng không hỗ trợ.');
        return $path;
    }

    public function tree(): array
    {
        $branch = rawurlencode(config('notes.branch'));
        $ref = $this->request('GET', '/repos/'.$this->repository().'/git/ref/heads/'.$branch);
        $revision = $ref['object']['sha'];
        if (config('notes.cache_enabled') && ($cached = Cache::get('notes-tree-'.hash('sha256', $this->repository().$revision)))) return $cached;
        $commit = $this->request('GET', '/repos/'.$this->repository().'/git/commits/'.$revision);
        $tree = $this->request('GET', '/repos/'.$this->repository().'/git/trees/'.$commit['tree']['sha'], ['recursive' => '1']);
        if ($tree['truncated'] ?? false) throw new GitHubFailure(503, 'GitHub trả danh sách chưa đầy đủ; không thể duyệt an toàn.');
        $files = array_values(array_filter($tree['tree'], fn ($item) => $item['type'] === 'blob' && $item['mode'] === '100644' && !preg_match('~(?:^|/)\.~', $item['path']) && preg_match('~\.(?:md|mdx|png|jpe?g|gif|webp|avif|img)$~i', $item['path'])));
        if (config('notes.cache_enabled')) Cache::put('notes-tree-'.hash('sha256', $this->repository().$revision), $files, 86400);
        return $files;
    }

    public function read(string $path, bool $image = false, ?array $tree = null): array
    {
        $path = $this->validatePath($path, $image);
        $item = collect($tree ?? $this->tree())->firstWhere('path', $path);
        if (!$item) abort(404, 'Không tìm thấy file.');
        if (($item['size'] ?? 0) > ($image ? 8_000_000 : config('notes.max_note_bytes'))) abort(413, 'File vượt giới hạn cho phép.');
        $content = $this->blobContent($item);
        return ['path' => $path, 'sha' => $item['sha'], 'content' => $content];
    }

    public function save(string $path, string $content, ?string $expectedSha): array
    {
        $path = $this->validatePath($path);
        $item = collect($this->tree())->firstWhere('path', $path);
        if (($item['sha'] ?? null) !== $expectedSha) {
            $remote = $item ? $this->read($path) : null;
            return ['conflict' => true, 'remote' => $remote];
        }
        $body = ['message' => 'Lưu ghi chú: '.$path, 'content' => base64_encode($content), 'branch' => config('notes.branch')];
        if ($expectedSha) $body['sha'] = $expectedSha;
        try {
            $result = $this->request('PUT', '/repos/'.$this->repository().'/contents/'.implode('/', array_map('rawurlencode', explode('/', $path))), $body);
        } catch (GitHubFailure $e) {
            if ($e->httpStatus !== 409) throw $e;
            $latest = collect($this->tree())->firstWhere('path', $path);
            return ['conflict' => true, 'remote' => $latest ? $this->read($path) : null];
        }
        return ['sha' => $result['content']['sha'], 'commit' => $result['commit']['sha'], 'commit_url' => $result['commit']['html_url']];
    }

    public function search(string $query): array
    {
        $results = []; $tree = $this->tree(); $query = mb_strtolower(trim($query));
        if ($query === '') return [];
        $notes = array_values(array_filter($tree, fn ($item) => preg_match('/\.mdx?$/i', $item['path'])));
        if (config('notes.cache_enabled')) $this->warmNotes($notes);
        foreach ($notes as $item) {
            $note = $this->read($item['path'], false, $tree);
            if (str_contains(mb_strtolower($note['path'].' '.$note['content']), $query)) $results[] = ['title' => $note['path'], 'url' => '/private/note?path='.rawurlencode($note['path']), 'excerpt' => mb_substr($note['content'], 0, 180)];
        }
        return $results;
    }

    private function blobFile(string $sha): string
    {
        if (!preg_match('/^[a-f0-9]{40}$/', $sha)) throw new GitHubFailure;
        $dir = config('notes.cache_directory', storage_path('app/private-notes')).'/'.hash('sha256', $this->repository()).'/blobs';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new GitHubFailure;
        return $dir.'/'.$sha;
    }

    private function cacheBlob(string $sha, string $content): void
    {
        $file = $this->blobFile($sha); $tmp = tempnam(dirname($file), 'blob-');
        try { chmod($tmp, 0600); file_put_contents($tmp, $content, LOCK_EX); rename($tmp, $file); }
        finally { if (is_file($tmp)) unlink($tmp); }
    }

    private function blobContent(array $item): string
    {
        if (config('notes.cache_enabled') && is_file($file = $this->blobFile($item['sha']))) return file_get_contents($file);
        $blob = $this->request('GET', '/repos/'.$this->repository().'/git/blobs/'.$item['sha']);
        $content = base64_decode($blob['content'], true);
        if ($content === false) throw new GitHubFailure;
        if (config('notes.cache_enabled') && strlen($content) <= config('notes.max_note_bytes')) $this->cacheBlob($item['sha'], $content);
        return $content;
    }

    // A cold large vault uses one pinned archive, rather than hundreds of API calls.
    // No archive entry is extracted; only validated Markdown blobs are cached.
    public function warmNotes(?array $notes = null): void
    {
        $notes ??= array_values(array_filter($this->tree(), fn ($item) => preg_match('/\.mdx?$/i', $item['path'])));
        $missing = array_filter($notes, fn ($item) => !is_file($this->blobFile($item['sha'])));
        if (count($missing) <= 12) return;
        try { Cache::lock('notes-archive-'.hash('sha256', $this->repository()), 180)->block(10, function () use ($notes) {
            if (count(array_filter($notes, fn ($item) => !is_file($this->blobFile($item['sha'])))) <= 12) return;
            $ref = $this->request('GET', '/repos/'.$this->repository().'/git/ref/heads/'.rawurlencode(config('notes.branch')));
            $token = $this->installationToken();
            $tmp = tempnam(storage_path('app'), 'notes-archive-'); chmod($tmp, 0600);
            try {
                $redirect = Http::withToken($token)->withHeaders(['User-Agent' => 'Minh-Notes-Laravel'])->connectTimeout(8)->timeout(25)->withoutRedirecting()->get('https://api.github.com/repos/'.$this->repository().'/zipball/'.$ref['object']['sha']);
                $url = $redirect->header('Location');
                if ($redirect->status() !== 302 || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'codeload.github.com') throw new GitHubFailure;
                $download = Http::connectTimeout(10)->timeout(100)->withoutRedirecting()->withOptions(['sink' => $tmp, 'progress' => function ($total, $downloaded) { if ($total > 160_000_000 || $downloaded > 160_000_000) throw new \RuntimeException('Archive limit'); }])->get($url);
                if (!$download->successful()) throw new GitHubFailure;
                $this->seedArchive($tmp, $notes);
            } catch (GitHubFailure $e) { throw $e; }
            catch (\Throwable $e) { throw new GitHubFailure(503, 'Chưa tải được chỉ mục riêng. Hãy thử lại; nội dung GitHub không thay đổi.'); }
            finally { if (is_file($tmp)) unlink($tmp); }
        }); } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            throw new GitHubFailure(503, 'Chỉ mục riêng đang cập nhật. Hãy thử lại sau ít giây.');
        }
    }
    private function seedArchive(string $file, array $notes): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($file) !== true) throw new GitHubFailure;
        try {
            $wanted = array_column($notes, null, 'path');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i); $path = explode('/', $stat['name'], 2)[1] ?? '';
                if (!isset($wanted[$path]) || $stat['size'] > config('notes.max_note_bytes') || !preg_match('/\.mdx?$/i', $path)) continue;
                $content = $zip->getFromIndex($i);
                if ($content === false) continue;
                $sha = sha1('blob '.strlen($content)."\0".$content);
                if (hash_equals($wanted[$path]['sha'], $sha)) $this->cacheBlob($sha, $content);
            }
        } finally { $zip->close(); }
    }
}
