@extends('layouts.admin')

@section('content')

<h2 class="mb-4">Dashboard Admin</h2>

<div class="row">

    <div class="col-md-4">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h3>{{ $stats['total_users'] }}</h3>
                <p>Total User</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3>{{ $stats['active_users'] }}</h3>
                <p>User Aktif</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h3>{{ $stats['inactive_users'] }}</h3>
                <p>User Nonaktif</p>
            </div>
        </div>
    </div>

</div>

@endsection