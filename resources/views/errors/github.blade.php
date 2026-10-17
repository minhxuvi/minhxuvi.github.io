@extends('layout')
@section('content')
<h1>Chưa kết nối được GitHub</h1><p>{{ $message }}</p><a href="{{ request()->getPathInfo() }}">Thử lại</a>
@endsection
