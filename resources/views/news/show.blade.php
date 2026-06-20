@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $article['title'] }}</h1>

    <img src="{{ $article['urlToImage'] }}" class="img-fluid mb-3">

    <p>{{ $article['description'] }}</p>

    <div>
        {{ $article['content'] }}
    </div>
</div>
@endsection