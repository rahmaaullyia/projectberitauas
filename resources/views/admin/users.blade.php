@extends('layouts.admin')
@section('title', 'Manajemen User')

@section('content')

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Daftar User Terdaftar</h6>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark border" id="badge-total">Memuat...</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th>Daftar</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-warning mb-2" style="font-size:2rem"></i>
                <p class="fw-semibold mb-1" id="confirmMessage">Yakin?</p>
                <p class="small text-muted mb-3" id="confirmSubtext"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-sm btn-danger" id="confirmBtn">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const modal = new bootstrap.Modal('#confirmModal');
let pendingAction = null;

// ─── DataTables AJAX (sesuai referensi PDF) ───────────────────────────────────
const table = $('#usersTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ route("admin.users.data") }}',
        type: 'GET',
        headers: { 'X-CSRF-TOKEN': CSRF },
    },
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'name' },
        { data: 'email' },
        { data: 'is_active', orderable: false },
        { data: 'is_admin', orderable: false },
        { data: 'created_at' },
        { data: 'action', orderable: false, searchable: false },
    ],
    language: {
        processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Memuat...',
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ user',
        emptyTable: 'Tidak ada data tersedia',
        zeroRecords: 'Tidak ada data yang cocok',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
    },
    drawCallback: function () {
        const info = this.api().page.info();
        $('#badge-total').text(info.recordsTotal + ' user');
    }
});

// ─── Toggle aktif/nonaktif ────────────────────────────────────────────────────
$(document).on('click', '.btn-toggle-user', function () {
    const id     = $(this).data('id');
    const active = $(this).data('active') == 1;
    $('#confirmMessage').text(active ? 'Nonaktifkan user ini?' : 'Aktifkan user ini?');
    $('#confirmSubtext').text(active ? 'User tidak bisa login setelah dinonaktifkan.' : 'User akan bisa login kembali.');
    $('#confirmBtn').removeClass('btn-danger btn-success').addClass(active ? 'btn-warning' : 'btn-success').text(active ? 'Nonaktifkan' : 'Aktifkan');

    pendingAction = () => {
        fetch(`/admin/users/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { table.ajax.reload(null, false); modal.hide(); showToast(data.message, 'success'); }
        });
    };
    modal.show();
});

// ─── Hapus user ───────────────────────────────────────────────────────────────
$(document).on('click', '.btn-delete-user', function () {
    const id = $(this).data('id');
    $('#confirmMessage').text('Hapus user ini?');
    $('#confirmSubtext').text('Tindakan ini tidak dapat dibatalkan.');
    $('#confirmBtn').removeClass('btn-warning btn-success').addClass('btn-danger').text('Hapus');

    pendingAction = () => {
        fetch(`/admin/users/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { table.ajax.reload(); modal.hide(); showToast(data.message, 'danger'); }
            else { showToast(data.message, 'warning'); modal.hide(); }
        });
    };
    modal.show();
});

$('#confirmBtn').on('click', () => pendingAction && pendingAction());

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
    t.style.zIndex = 9999;
    t.innerHTML = `<i class="bi bi-${type==='success'?'check-circle':'exclamation-circle'} me-2"></i>${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
</script>
@endpush
@endsection