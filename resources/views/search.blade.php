@extends('layout')
@section('content')
<h1>{{ $title }}</h1><form class="search-form" method="get"><label for="query">Từ khóa</label><div><input id="query" type="search" name="q" value="{{ $q }}" maxlength="120" required><button>Tìm kiếm</button></div></form>
@if($q !== '')<p class="muted">{{ count($results) }} kết quả cho “{{ $q }}”</p><div class="results">@forelse($results as $result)<article><h2><a href="{{ $result['url'] }}">{{ $result['title'] }}</a></h2><p>{{ $result['excerpt'] }}</p></article>@empty<p>Không tìm thấy kết quả. Hãy thử từ khóa khác.</p>@endforelse</div>@endif
@endsection
