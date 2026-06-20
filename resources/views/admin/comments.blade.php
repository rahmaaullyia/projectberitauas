@extends('layouts.admin')

@section('content')

<h2 class="mb-4">Manajemen Komentar</h2>

<div class="card">
    <div class="card-body">

        <table id="commentsTable" class="table table-bordered">
            <thead>
            <tr>
                <th>No</th>
                <th>User</th>
                <th>Komentar</th>
                <th>Tanggal</th>
            </tr>
            </thead>

            <tbody>

            @foreach($comments ?? [] as $comment)

                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $comment->user->name ?? '-' }}</td>
                    <td>{{ $comment->body }}</td>
                    <td>{{ $comment->created_at }}</td>
                </tr>

            @endforeach

            </tbody>

        </table>

    </div>
</div>

@endsection