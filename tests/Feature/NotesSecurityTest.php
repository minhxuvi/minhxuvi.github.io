<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Services\{Markdown,PublicContent,GitHubNotes};
use Illuminate\Support\Facades\Http;
class NotesSecurityTest extends TestCase {
    public function test_all_public_pages_metadata_navigation_and_routes_are_preserved(): void {
        $pages=app(PublicContent::class)->pages(); $this->assertCount(21,$pages);
        foreach($pages as $url=>$page) {
            $this->assertNotEmpty($page['description'],$url);
            $this->get($url)->assertOk()->assertSee($page['title']);
        }
        $this->get('/giao-duc/index.md')->assertRedirect('/giao-duc');
        $this->get('/search?q=Waldorf')->assertOk()->assertSee('Waldorf');
        $this->get('/hoa-am/tu-nen-tang-den-nang-cao')->assertSee('id="chang-1"', false)->assertSee('href="#chang-1"', false);
    }
    public function test_every_private_endpoint_requires_owner_session(): void {
        Http::preventStrayRequests();
        foreach(['/private','/private/note?path=README.md','/private/asset?path=Anh/mau.png','/private/search?q=secret','/private/new'] as $url) {
            $this->get($url)->assertRedirect('/auth/login')->assertHeader('Cache-Control','max-age=0, no-store, private');
            $this->getJson($url)->assertUnauthorized();
        }
        foreach(['/private/api/save','/private/api/preview'] as $url) $this->postJson($url,['path'=>'README.md','content'=>'secret','sha'=>null])->assertUnauthorized();
        $this->withSession(['github_id'=>'someone-else'])->getJson('/private')->assertUnauthorized();
    }
    public function test_markdown_does_not_execute_html_mdx_or_unsafe_urls_and_protects_images(): void {
        $rendered=app(Markdown::class)->render("# Mẫu\n\n<script>alert(1)</script>\n\n{danger()}\n\n[x](javascript:alert)\n\n![x](https://tracker.invalid/image.png)\n\n![mẫu](../Anh/mau.png)\n\n[note](../README.md)\n\n| A | B |\n| - | - |\n| 1 | 2 |\n\n- [x] Xong",'Thu-muc/Note.md');
        $this->assertStringNotContainsString('<script',$rendered['html']);
        $this->assertStringNotContainsString('javascript:',$rendered['html']);
        $this->assertStringNotContainsString('https://tracker.invalid',$rendered['html']);
        $this->assertStringContainsString('/private/asset?path=Anh%2Fmau.png',$rendered['html']);
        $this->assertStringContainsString('/private/note?path=README.md',$rendered['html']);
        $this->assertStringContainsString('<table>',$rendered['html']);
        $this->assertStringContainsString('checkbox',$rendered['html']);
        $this->assertStringContainsString('{danger()}',$rendered['html']);
    }
    public function test_bad_paths_and_non_markdown_are_rejected_before_github(): void {
        Http::preventStrayRequests();
        foreach(['../secret.md','/secret.md','.env','folder/../../secret.md','file.php','a\\file.md','a//file.md'] as $path) {
            $this->withSession(['github_id'=>config('notes.owner_id')])->getJson('/private/note?path='.rawurlencode($path))->assertStatus(422);
        }
    }
    private function fakeGitHub(string $sha, string $path = 'README.md'): void {
        $service=new class extends GitHubNotes { public function installationToken(): string {return 'test-token';} };
        $this->app->instance(GitHubNotes::class,$service);
        Http::fake([
            'api.github.com/repos/*/git/ref/*'=>Http::response(['object'=>['sha'=>str_repeat('c',40)]]),
            'api.github.com/repos/*/git/commits/*'=>Http::response(['tree'=>['sha'=>str_repeat('d',40)]]),
            'api.github.com/repos/*/git/trees/*'=>Http::response(['tree'=>[['path'=>$path,'mode'=>'100644','type'=>'blob','sha'=>$sha,'size'=>20]],'truncated'=>false]),
            'api.github.com/repos/*/git/blobs/*'=>Http::response(['content'=>base64_encode('Nội dung từ GitHub')]),
            'api.github.com/repos/*/contents/*'=>Http::response(['content'=>['sha'=>str_repeat('e',40)],'commit'=>['sha'=>str_repeat('f',40),'html_url'=>'https://github.com/minhxuvi/minh-notes-demo/commit/'.str_repeat('f',40)]]),
        ]);
    }
    public function test_stale_save_returns_remote_content_and_never_writes(): void {
        $this->fakeGitHub(str_repeat('b',40));
        $this->withSession(['github_id'=>config('notes.owner_id')])->postJson('/private/api/save',['path'=>'README.md','content'=>'Bản đang soạn','sha'=>str_repeat('a',40)])->assertConflict()->assertJsonPath('conflict',true)->assertJsonPath('remote.content','Nội dung từ GitHub');
        Http::assertNotSent(fn($request)=>$request->method()==='PUT');
    }
    public function test_successful_save_waits_for_github_commit(): void {
        $this->fakeGitHub(str_repeat('b',40));
        $this->withSession(['github_id'=>config('notes.owner_id')])->postJson('/private/api/save',['path'=>'README.md','content'=>'Đã sửa','sha'=>str_repeat('b',40)])->assertOk()->assertJsonPath('commit',str_repeat('f',40));
        Http::assertSent(fn($request)=>$request->method()==='PUT' && $request['sha']===str_repeat('b',40) && base64_decode($request['content'])==='Đã sửa');
    }
    public function test_github_network_failure_returns_safe_retryable_error(): void {
        $this->fakeGitHub(str_repeat('b',40));
        Http::fake(['*'=>Http::failedConnection()]);
        $this->withSession(['github_id'=>config('notes.owner_id')])->postJson('/private/api/save',['path'=>'README.md','content'=>'Giữ lại bản này','sha'=>str_repeat('b',40)])->assertStatus(503)->assertJsonPath('message','GitHub chưa phản hồi. Nội dung đang soạn được giữ lại; hãy thử lại.');
    }
    public function test_oauth_callback_rejects_invalid_state(): void {
        Http::preventStrayRequests();
        $this->get('/auth/callback?code=fake&state=wrong')->assertForbidden();
    }
    public function test_post_requires_csrf_even_with_an_owner_session(): void {
        $this->app->instance('env', 'production');
        Http::preventStrayRequests();
        $this->withSession(['github_id'=>config('notes.owner_id')])->postJson('/private/api/save',['path'=>'README.md','content'=>'x','sha'=>null])->assertStatus(419);
    }
    public function test_array_paths_and_svg_are_rejected(): void {
        Http::preventStrayRequests();
        $this->withSession(['github_id'=>config('notes.owner_id')])->getJson('/private/note?path[]=README.md')->assertStatus(422);
        $this->withSession(['github_id'=>config('notes.owner_id')])->getJson('/private/asset?path=tracking.svg')->assertStatus(422);
    }
    public function test_oauth_rejects_other_github_id(): void {
        $file=tempnam(sys_get_temp_dir(),'minh-test-creds');
        file_put_contents($file,json_encode(['client_id'=>'test','client_secret'=>'test']));
        config(['notes.credentials'=>$file]);
        Http::fake(['github.com/login/oauth/access_token'=>Http::response(['access_token'=>'test']),'api.github.com/user'=>Http::response(['id'=>123,'login'=>'other'])]);
        try { $this->withSession(['oauth_state'=>'expected'])->get('/auth/callback?code=fake&state=expected')->assertForbidden(); }
        finally {unlink($file);}
    }
    public function test_private_wikilinks_are_safe_and_code_stays_literal(): void {
        $source = "[[Đích|Mở]] ![[ảnh.png]] [[trùng]] `[[Đích]]`\n\n```\n[[Đích]]\n```\n\n[[javascript:alert(1)|An toàn]]";
        $html = app(Markdown::class)->render($source, 'Thư mục/Nguồn.md', ['Thư mục/Đích.md', 'Ảnh/ảnh.png', 'A/trùng.md', 'B/trùng.md'])['html'];
        $this->assertStringContainsString('/private/note?path=Th%C6%B0%20m%E1%BB%A5c%2F%C4%90%C3%ADch.md', $html);
        $this->assertStringContainsString('/private/asset?path=%E1%BA%A2nh%2F%E1%BA%A3nh.png', $html);
        $this->assertStringContainsString('/private/search?q=tr%C3%B9ng', $html);
        $this->assertStringContainsString('<code>[[Đích]]</code>', $html);
        $this->assertStringContainsString('<pre><code>[[Đích]]', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $public = app(Markdown::class)->render('[[Đích]]')['html'];
        $this->assertStringNotContainsString('/private/', $public);
    }
    public function test_large_vault_search_uses_all_markdown_and_never_returns_to_public(): void {
        $service = new class extends GitHubNotes {
            public function tree(): array { return array_map(fn ($i) => ['path' => 'Note-'.$i.'.md', 'sha' => str_repeat('a', 40)], range(1, 100)); }
            public function read(string $path, bool $image = false, ?array $tree = null): array { return ['path' => $path, 'content' => $path === 'Note-100.md' ? 'PRIVATE-ONLY-100' : 'Other']; }
        };
        $this->app->instance(GitHubNotes::class, $service);
        $this->withSession(['github_id'=>config('notes.owner_id')])->get('/private/search?q=PRIVATE-ONLY-100')->assertOk()->assertSee('Note-100.md');
        $this->get('/search?q=PRIVATE-ONLY-100')->assertOk()->assertDontSee('Note-100.md');
    }
    public function test_archive_seeding_verifies_git_hash_and_never_extracts_entries(): void {
        $dir = sys_get_temp_dir().'/minh-cache-test-'.bin2hex(random_bytes(8)); mkdir($dir,0700);
        config(['notes.cache_enabled'=>true,'notes.cache_directory'=>$dir.'/cache']);
        $content = '# Nội dung riêng fixture'; $sha = sha1('blob '.strlen($content)."\0".$content);
        $file = $dir.'/notes.zip'; $zip = new \ZipArchive; $zip->open($file, \ZipArchive::CREATE);
        $zip->addFromString('repo/Đúng.md', $content);
        $zip->addFromString('repo/Sai.md', 'Nội dung không khớp SHA');
        $zip->addFromString('repo/../../escape.php', '<?php die();'); $zip->close();
        $notes = [['path'=>'Đúng.md','sha'=>$sha,'size'=>strlen($content)], ['path'=>'Sai.md','sha'=>str_repeat('b',40),'size'=>20]];
        $service=app(GitHubNotes::class);
        try {
            (new \ReflectionMethod($service,'seedArchive'))->invoke($service,$file,$notes);
            Http::preventStrayRequests();
            $this->assertSame($content,$service->read('Đúng.md',false,$notes)['content']);
            $this->assertFileDoesNotExist($dir.'/escape.php');
            $this->assertFileDoesNotExist($dir.'/cache/'.hash('sha256',config('notes.repository')).'/blobs/'.str_repeat('b',40));
            $blob=$dir.'/cache/'.hash('sha256',config('notes.repository')).'/blobs/'.$sha;
            $this->assertSame(0600,fileperms($blob)&0777);
            $this->getJson('/private/note?path='.rawurlencode('Đúng.md'))->assertUnauthorized();
        } finally { \Illuminate\Support\Facades\File::deleteDirectory($dir); }
    }
    public function test_existing_img_extension_still_requires_safe_raster_mime(): void {
        $this->assertSame('Media/photo.img',app(GitHubNotes::class)->validatePath('Media/photo.img',true));
        $html=app(Markdown::class)->render('![[photo.img]]','Notes/a.md',['Media/photo.img'])['html'];
        $this->assertStringContainsString('src="/private/asset?path=Media%2Fphoto.img"',$html);
        $service=new class extends GitHubNotes { public function read(string $path,bool $image=false,?array $tree=null): array {return ['content'=>'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'];} };
        $this->app->instance(GitHubNotes::class,$service);
        $this->withSession(['github_id'=>config('notes.owner_id')])->get('/private/asset?path=Media/photo.img')->assertStatus(415);
    }
    public function test_question_mark_filename_is_preserved_and_encoded_when_saving(): void {
        $this->fakeGitHub(str_repeat('b',40),'Tại sao?.md');
        $service=app(GitHubNotes::class);
        $this->assertSame('Tại sao?.md',$service->validatePath('Tại sao?.md'));
        $this->assertSame('Nội dung từ GitHub',$service->read('Tại sao?.md')['content']);
        $this->withSession(['github_id'=>config('notes.owner_id')])->postJson('/private/api/save',['path'=>'Tại sao?.md','content'=>'Đã sửa','sha'=>str_repeat('b',40)])->assertOk();
        Http::assertSent(fn ($request) => $request->method()==='PUT' && str_ends_with($request->url(),'/contents/'.rawurlencode('Tại sao?.md')));
    }
}
