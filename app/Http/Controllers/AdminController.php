<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    /**
     * Mhs 3: Dashboard admin - statistik ringkas
     */
    public function dashboard()
    {
        $stats = [
            'total_users'    => User::count(),
            'active_users'   => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'total_comments' => Comment::count(),
            'recent_users'   => User::latest()->take(5)->get(),
            'recent_comments' => Comment::with('user')->latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // ─── Users ─────────────────────────────────────────────────────────────────

    /**
     * Halaman daftar user (DataTables)
     */
    public function users()
    {
        return view('admin.users');
    }

    /**
     * AJAX endpoint untuk DataTables - daftar user
     */
    public function getUsersData(Request $request)
    {
        if ($request->ajax()) {
            $data = User::select(['id', 'name', 'email', 'is_active', 'is_admin', 'created_at'])
                        ->where('id', '!=', auth()->id()); // Jangan tampilkan diri sendiri

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', fn($row) =>
                    $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-'
                )
                ->editColumn('is_active', fn($row) =>
                    $row->is_active
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-danger">Nonaktif</span>'
                )
                ->editColumn('is_admin', fn($row) =>
                    $row->is_admin
                        ? '<span class="badge bg-warning text-dark">Admin</span>'
                        : '<span class="badge bg-secondary">User</span>'
                )
                ->addColumn('action', function ($row) {
                    $toggleLabel = $row->is_active ? 'Nonaktifkan' : 'Aktifkan';
                    $toggleClass = $row->is_active ? 'btn-warning' : 'btn-success';

                    return '
                        <button
                            data-id="' . $row->id . '"
                            data-active="' . ($row->is_active ? '1' : '0') . '"
                            class="btn btn-sm ' . $toggleClass . ' btn-toggle-user me-1">
                            ' . $toggleLabel . '
                        </button>
                        <button
                            data-id="' . $row->id . '"
                            class="btn btn-sm btn-danger btn-delete-user">
                            Hapus
                        </button>';
                })
                ->rawColumns(['is_active', 'is_admin', 'action'])
                ->make(true);
        }

        return abort(403);
    }

    /**
     * Toggle status aktif user
     */
    public function toggleUser(int $id)
    {
        $user            = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status user berhasil diperbarui.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Hapus user
     */
    public function deleteUser(int $id)
    {
        $user = User::findOrFail($id);

        // Jangan hapus diri sendiri
        if ($id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus.']);
    }

    // ─── Comments ──────────────────────────────────────────────────────────────

    /**
     * Halaman daftar komentar (DataTables)
     */
    public function comments()
    {
        return view('admin.comments');
    }

    /**
     * AJAX endpoint untuk DataTables - daftar komentar
     */
    public function getCommentsData(Request $request)
    {
        if ($request->ajax()) {
            $data = Comment::with('user')
                           ->select(['comments.*']);

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', fn($row) =>
                    $row->created_at->format('d-m-Y H:i')
                )
                ->addColumn('user_name', fn($row) => $row->user->name ?? '-')
                ->editColumn('body', fn($row) =>
                    '<span title="' . e($row->body) . '">' . \Str::limit($row->body, 80) . '</span>'
                )
                ->addColumn('action', function ($row) {
                    return '
                        <button
                            data-id="' . $row->id . '"
                            class="btn btn-sm btn-danger btn-delete-comment">
                            Hapus
                        </button>';
                })
                ->rawColumns(['body', 'action'])
                ->make(true);
        }

        return abort(403);
    }

    /**
     * Hapus komentar via admin
     */
    public function deleteComment(int $id)
    {
        Comment::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Komentar berhasil dihapus.']);
    }
}
