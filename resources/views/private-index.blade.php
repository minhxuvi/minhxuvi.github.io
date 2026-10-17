@extends('layout')
@section('content')
<div class="intro"><h1>Ghi chú riêng</h1><p>Đọc, sửa và lưu trực tiếp vào kho Markdown trên GitHub.</p></div>
<div class="toolbar"><a class="button" href="/private/new">＋ Tạo ghi chú</a><a href="/private">Thư mục gốc</a></div>
@if($folder)<p>Thư mục: <strong>{{ $folder }}</strong></p>@endif
@if($folders)<div class="folder-list">@foreach($folders as $dir)<a class="folder" href="/private?folder={{ rawurlencode($dir) }}">▱ {{ basename($dir) }}</a>@endforeach</div>@endif
<ul class="note-list">@forelse($files as $file)<li><a href="/private/note?path={{ rawurlencode($file['path']) }}"><span>↳ {{ basename($file['path']) }}</span><span class="muted">Mở →</span></a></li>@empty<li>Chưa có ghi chú trong thư mục này.</li>@endforelse</ul>
@if($page > 1 || $total > $page*50)<nav class="toolbar" aria-label="Trang ghi chú">@if($page > 1)<a href="?{{ http_build_query(['folder'=>$folder,'page'=>$page-1]) }}">← Trang trước</a>@endif<span>Trang {{ $page }}</span>@if($total > $page*50)<a href="?{{ http_build_query(['folder'=>$folder,'page'=>$page+1]) }}">Trang sau →</a>@endif</nav>@endif
<div class="card small"><h2>Đồng bộ với Mac</h2><p>Trước khi sửa local, chạy thao tác đồng bộ để nhận bản mới từ web. Sau khi sửa, xem và chọn các file cần gửi lên. Web đọc lại bản GitHub ở mỗi lần mở.</p></div>
@endsection
