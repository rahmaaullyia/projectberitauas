<?php

namespace App\Http\Controllers;

use App\Models\SavedNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NewsController extends Controller
{
    // Kategori yang tersedia
    const CATEGORIES = [
        'teknologi' => 'technology',
        'olahraga'  => 'sports',
        'bisnis'    => 'business',
        'kesehatan' => 'health',
        'hiburan'   => 'entertainment',
        'sains'     => 'science',
    ];

    /**
     * Mhs 1: Simpan berita
     */
    public function saveNews(string $id)
    {
        SavedNews::firstOrCreate([
            'user_id' => Auth::id(),
            'news_id' => $id,
        ]);

        return back()->with('success', 'Berita berhasil disimpan!');
    }

    /**
     * Mhs 1: Hapus berita tersimpan
     */
    public function unsaveNews(string $id)
    {
        SavedNews::where('user_id', Auth::id())
                 ->where('news_id', $id)
                 ->delete();

        return back()->with('success', 'Berita dihapus dari simpanan.');
    }

    /**
     * Mhs 1: Daftar berita tersimpan
     */
    public function savedNews()
    {
        $savedIds = SavedNews::where('user_id', Auth::id())
                             ->pluck('news_id')
                             ->toArray();

        // Untuk demo, tampilkan dari semua kategori dan filter berdasarkan saved ID
        $allArticles = [];
        foreach (array_keys(self::CATEGORIES) as $cat) {
            $result      = $this->fetchNews($cat);
            $allArticles = array_merge($allArticles, $result['articles']);
        }

        $savedArticles = collect($allArticles)
            ->filter(fn($a) => in_array($a['id'], $savedIds))
            ->unique('id')
            ->values();

        return view('news.saved', compact('savedArticles'));
    }

    /**
     * Mhs 1: Feed berdasarkan kategori favorit user
     */
    public function myFeed()
    {
        $user               = Auth::user();
        $favoriteCategories = json_decode($user->favorite_categories ?? '[]', true);

        if (empty($favoriteCategories)) {
            return redirect()->route('profile.edit')->with('info', 'Pilih kategori favorit terlebih dahulu.');
        }

        $articles = [];
        foreach ($favoriteCategories as $cat) {
            $result   = $this->fetchNews($cat);
            $articles = array_merge($articles, array_slice($result['articles'], 0, 6));
        }

        return view('news.feed', compact('articles', 'favoriteCategories'));
    }
}