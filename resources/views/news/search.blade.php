@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Hasil Pencarian: {{ $query }}</h2>

    @foreach($articles as $article)
        <div class="card mb-3">
            <img src="{{ $article['urlToImage'] }}" class="card-img-top">
            <div class="card-body">
                <h5>{{ $article['title'] }}</h5>
                <p>{{ $article['description'] }}</p>
            </div>
        </div>
    @endforeach
</div>
@endsection