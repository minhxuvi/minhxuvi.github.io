@extends('layout')
@section('content')
<div class="intro"><h1>Ghi chú riêng</h1><p>Không gian để đọc, viết và nối tiếp những ý tưởng đang dở.</p></div>
<div class="card"><h2>Đăng nhập bằng GitHub</h2><p>Chỉ tài khoản GitHub của Minh được truy cập. {{ config('notes.demo_only') ? 'Bản thử dùng kho ghi chú mẫu.' : 'Nội dung riêng được lưu trong kho GitHub private.' }}</p>
@if($configured)<a class="button" href="/auth/github">Tiếp tục với GitHub ↗</a>@else<p>Đang hoàn tất kết nối GitHub App.</p>@endif
</div>
@endsection
