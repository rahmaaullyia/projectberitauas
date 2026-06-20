@extends('layouts.admin')

@section('content')

<h2 class="mb-4">Manajemen User</h2>

<div class="card">
    <div class="card-body">

        <table id="usersTable" class="table table-bordered">
            <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th>Aksi</th>
            </tr>
            </thead>
        </table>

    </div>
</div>

@endsection

@push('styles')

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

@endpush

@push('scripts')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>

$(function(){

    let table = $('#usersTable').DataTable({
        processing:true,
        serverSide:true,

        ajax:"{{ route('admin.users.data') }}",

        columns:[
            {data:'DT_RowIndex'},
            {data:'name'},
            {data:'email'},
            {data:'is_active'},
            {data:'created_at'},
            {data:'action'}
        ]
    });

    $(document).on('click','.btn-toggle-user',function(){

        let id = $(this).data('id');

        $.ajax({
            url:'/admin/users/'+id+'/toggle',
            type:'POST',
            data:{
                _token:'{{ csrf_token() }}'
            },
            success:function(res){
                table.ajax.reload();
                alert(res.message);
            }
        });

    });

});

</script>

@endpush