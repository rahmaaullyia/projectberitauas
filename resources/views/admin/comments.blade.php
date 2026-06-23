@extends('layouts.admin')
@section('title', 'Manajemen Komentar')

@section('content')

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2"></i>Daftar Komentar</h6>
        <span class="badge bg-light text-dark border" id="badge-total">Memuat...</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="commentsTable" class="table table-hover align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th width="150">Pengguna</th>
                        <th>Isi Komentar</th>
                        <th width="130">Tanggal</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

const table = $('#commentsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ route("admin.comments.data") }}',
        type: 'GET',
        headers: { 'X-CSRF-TOKEN': CSRF },
    },
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'user_name' },
        { data: 'body' },
        { data: 'created_at' },
        { data: 'action', orderable: false, searchable: false },
    ],
    language: {
        processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Memuat...',
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ komentar',
        emptyTable: 'Belum ada komentar',
        zeroRecords: 'Tidak ada komentar yang cocok',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
    },
    drawCallback: function () {
        const info = this.api().page.info();
        $('#badge-total').text(info.recordsTotal + ' komentar');
    }
});

$(document).on('click', '.btn-delete-comment', function () {
    const id = $(this).data('id');
    if (!confirm('Hapus komentar ini?')) return;

    fetch(`/admin/comments/${id}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            table.ajax.reload(null, false);
            const t = document.createElement('div');
            t.className = 'alert alert-danger position-fixed bottom-0 end-0 m-3 shadow';
            t.style.zIndex = 9999;
            t.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.message}`;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }
    });
});
</script>
@endpush
@endsection
