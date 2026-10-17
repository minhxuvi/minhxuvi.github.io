<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Đỗ Anh Minh' }} | Đỗ Anh Minh</title>
    <meta name="description" content="{{ $description ?? 'Ghi chép về giáo dục, âm nhạc và cuộc sống.' }}">
    @if($private ?? false)<meta name="robots" content="noindex,nofollow">@endif
    <link rel="stylesheet" href="/assets/site.css?v=3">
    <script src="/assets/site.js?v=3" defer></script>
</head>
<body data-private="{{ ($private ?? false) ? '1' : '0' }}" data-owner="{{ session('github_id', '') }}">
<a class="skip" href="#main">Đến nội dung</a>
<header class="header">
    <a class="brand" href="/">Đỗ Anh Minh<span>Ghi chép & thực hành</span></a>
    <nav class="header-links" aria-label="Điều hướng chính"><a href="/ve-minh">Giới thiệu</a><a href="/giao-duc">Giáo dục</a><a href="/hoa-am">Hòa âm</a><a href="/private" class="private-link">Khu riêng ↗</a></nav>
    <details class="mobile-menu"><summary>Menu</summary><nav aria-label="Menu điện thoại">
    @foreach($navigation[''] as $slug => $item)<a href="{{ $item['href'] ?? ($slug === 'index' ? '/' : '/'.$slug) }}">{{ $item['title'] }}</a>@endforeach
    <a href="/private">Khu riêng</a></nav></details>
</header>
<div class="workspace">
<aside class="sidebar">
    <form class="search-form" action="{{ ($private ?? false) ? '/private/search' : '/search' }}" method="get"><label for="site-q">{{ ($private ?? false) ? 'Tìm ghi chú riêng' : 'Tìm nội dung công khai' }}</label><div><input id="site-q" name="q" type="search" maxlength="120" placeholder="Nhập từ khóa…" value="{{ $q ?? '' }}"><button>Tìm</button></div></form>
    @if($private ?? false)
    <nav aria-label="Khu riêng"><a href="/private">Tất cả ghi chú</a><a href="/private/new">＋ Tạo ghi chú</a><a href="/">Về trang công khai</a></nav>
    <p class="muted small">{{ config('notes.demo_only') ? 'Kho dữ liệu mẫu' : 'Kho ghi chú riêng' }}<br>Đăng nhập: {{ session('github_login') }}</p>
    <form action="/auth/logout" method="post" data-logout>@csrf<button class="secondary">Đăng xuất</button></form>
    @else
    <nav aria-label="Các chủ đề">
    @foreach($navigation[''] as $slug => $item)
        <a class="section-link {{ request()->is($slug, $slug.'/*') || ($slug === 'index' && request()->is('/')) ? 'active' : '' }}" href="{{ $item['href'] ?? ($slug === 'index' ? '/' : '/'.$slug) }}">{{ $item['title'] }}</a>
        @if(isset($navigation[$slug]))<div class="subnav">@foreach($navigation[$slug] as $child => $label)<a href="/{{ $slug }}{{ $child === 'index' ? '' : '/'.$child }}" @if(request()->path() === $slug.($child === 'index' ? '' : '/'.$child)) aria-current="page" @endif>{{ $label }}</a>@endforeach</div>@endif
    @endforeach
    </nav>
    @endif
</aside>
<main id="main" class="main" tabindex="-1">
    <div class="eyebrow">{{ ($private ?? false) ? (config('notes.demo_only') ? 'KHÔNG GIAN RIÊNG · DỮ LIỆU MẪU' : 'KHÔNG GIAN RIÊNG') : 'SỔ TAY MỞ' }}</div>
    @yield('content')
</main>
@if(!empty($toc))<aside class="toc"><details open><summary>Trong trang này</summary><nav aria-label="Mục lục">@foreach($toc as $heading)<a class="level-{{ $heading['level'] }}" href="#{{ $heading['id'] }}">{{ $heading['title'] }}</a>@endforeach</nav></details><a class="back-top" href="#main">↑ Lên đầu trang</a></aside>@endif
</div>
<footer>Đỗ Anh Minh · Học, làm và ghi lại.<a href="/private">Khu riêng</a><span class="muted">Ghi chép & thực hành</span></footer>
</body>
</html>
