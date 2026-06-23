<?php

namespace App\Http\Controllers;

use App\Models\SavedNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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

    const CATEGORY_KEYWORDS = [
        'teknologi' => '"kecerdasan buatan" OR startup OR aplikasi OR smartphone OR "perangkat lunak" OR gadget OR teknologi',
        'olahraga'  => '"timnas indonesia" OR "piala dunia" OR "sepak bola" OR "liga 1" OR atlet OR pertandingan OR olimpiade',
        'bisnis'    => '"rupiah" OR IHSG OR ekonomi OR investasi OR "bank indonesia" OR perusahaan OR saham',
        'kesehatan' => '"kementerian kesehatan" OR "rumah sakit" OR penyakit OR vaksin OR dokter OR kesehatan',
        'hiburan'   => 'film OR konser OR musisi OR artis OR sinetron OR selebriti',
        'sains'     => 'penelitian OR "ilmuwan" OR riset OR "luar angkasa" OR astronomi OR sains',
    ];

    // Domain yang sering memuat artikel promo/iklan/lowongan kerja yang
    // tidak relevan dengan berita kategori (judulnya kebetulan mengandung
    // kata kunci kategori, tapi isinya promosi produk/jasa).
    const EXCLUDED_DOMAINS = 'katalogpromosi.com,lokersemar.id';
    
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