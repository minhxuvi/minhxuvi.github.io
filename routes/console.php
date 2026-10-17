<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('notes:warm', function () {
    app(\App\Services\GitHubNotes::class)->warmNotes();
    $this->info('Đã chuẩn bị cache Markdown riêng ngoài web root.');
})->purpose('Chuẩn bị cache tìm kiếm riêng, không sửa GitHub.');
