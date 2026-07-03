<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Mhs 3: Simpan komentar pada berita
     */
    public function store(Request $request, string $id)
    {
        $validated = $request->validate([
            'body' => 'required|string|min:3|max:1000',
        ]);

        Comment::create([
            'user_id' => Auth::id(),
            'news_id' => $id,
            'body'    => $validated['body'],
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan!');
    }

    /**
     * Hapus komentar (hanya pemilik atau admin)
     */
    public function destroy(int $id)
    {
        $comment = Comment::findOrFail($id);

        // Hanya owner atau admin yang boleh hapus
        if (Auth::id() !== $comment->user_id && !Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $comment->delete();

        return back()->with('success', 'Komentar berhasil dihapus.');
    }
}
