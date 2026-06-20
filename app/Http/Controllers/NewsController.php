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
     * Mhs 2: Ambil berita dari NewsAPI dengan cache 30 menit
     */
    private function fetchNews(string $category = 'general', int $page = 1, string $query = ''): array
    {
        $cacheKey = "news_{$category}_{$page}_{$query}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($category, $page, $query) {
            $apiCategory = self::CATEGORIES[$category] ?? $category;

            $params = [
                'apiKey'    => config('services.newsapi.key'),
                'language'  => 'id',
                'pageSize'  => 12,
                'page'      => $page,
                'sortBy'    => 'publishedAt',
            ];

            // Gunakan endpoint /everything untuk query spesifik, /top-headlines untuk kategori
            if (!empty($query)) {
                $params['q']        = $query;
                $params['language'] = 'id';
                $endpoint = 'https://newsapi.org/v2/everything';
            } else {
                $params['category'] = $apiCategory;
                $params['country']  = 'id';
                $endpoint = 'https://newsapi.org/v2/top-headlines';
            }

            try {
                $response = Http::timeout(10)->get($endpoint, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    // Tambahkan id unik & slug kategori ke setiap artikel
                    $articles = collect($data['articles'] ?? [])
                        ->map(function ($article, $index) use ($category) {
                            $article['id']       = md5($article['url'] ?? $index);
                            $article['category'] = $category;
                            return $article;
                        })
                        ->filter(fn($a) => !empty($a['title']) && $a['title'] !== '[Removed]')
                        ->values()
                        ->toArray();

                    return [
                        'articles'    => $articles,
                        'totalResults' => $data['totalResults'] ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('NewsAPI Error: ' . $e->getMessage());
            }

            // Fallback: data dummy untuk development
            return $this->dummyNews($category);
        });
    }

    /**
     * Halaman utama: tampilkan berita dari semua kategori / kategori favorit
     */
    public function index(Request $request)
    {
        $user            = Auth::user();
        $activeCategory  = $request->get('category', 'semua');

        // Jika user login & punya favorit, tampilkan dari favorit
        $favoriteCategories = [];
        if ($user) {
            $favoriteCategories = json_decode($user->favorite_categories ?? '[]', true);
        }

        if ($activeCategory === 'semua') {
            // Ambil berita dari semua kategori (atau kategori favorit jika ada)
            $categoriesToFetch = !empty($favoriteCategories) ? $favoriteCategories : array_keys(self::CATEGORIES);
            $allArticles = [];
            foreach (array_slice($categoriesToFetch, 0, 3) as $cat) {
                $result = $this->fetchNews($cat);
                $allArticles = array_merge($allArticles, array_slice($result['articles'], 0, 4));
            }
            $articles     = $allArticles;
            $totalResults = count($articles);
        } else {
            $result       = $this->fetchNews($activeCategory, $request->get('page', 1));
            $articles     = $result['articles'];
            $totalResults = $result['totalResults'];
        }

        // Berita headline (3 teratas)
        $headlines = array_slice($articles, 0, 3);
        $restNews  = array_slice($articles, 3);

        return view('news.index', compact(
            'articles', 'headlines', 'restNews',
            'activeCategory', 'favoriteCategories', 'totalResults'
        ));
    }

    /**
     * Detail berita
     */
    public function show(Request $request, string $id)
    {
        $category = $request->get('category', 'teknologi');
        $result   = $this->fetchNews($category);

        $article = collect($result['articles'])->firstWhere('id', $id);

        if (!$article) {
            abort(404, 'Berita tidak ditemukan.');
        }

        // Cek apakah sudah disimpan
        $isSaved = false;
        if (Auth::check()) {
            $isSaved = SavedNews::where('user_id', Auth::id())
                                ->where('news_id', $id)
                                ->exists();
        }

        // Berita terkait (kategori sama, beda ID)
        $related = collect($result['articles'])
            ->where('id', '!=', $id)
            ->take(4)
            ->values();

        // Load komentar
        $comments = \App\Models\Comment::with('user')
            ->where('news_id', $id)
            ->latest()
            ->get();

        return view('news.show', compact('article', 'isSaved', 'related', 'comments', 'id'));
    }

    /**
     * Berita per kategori
     */
    public function byCategory(Request $request, string $category)
    {
        $page   = $request->get('page', 1);
        $result = $this->fetchNews($category, $page);

        return view('news.category', [
            'articles'       => $result['articles'],
            'totalResults'   => $result['totalResults'],
            'activeCategory' => $category,
            'page'           => $page,
        ]);
    }

    /**
     * Pencarian berita
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return redirect()->route('home');
        }

        $result = $this->fetchNews('semua', 1, $query);

        return view('news.search', [
            'articles'     => $result['articles'],
            'totalResults' => $result['totalResults'],
            'query'        => $query,
        ]);
    }

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

    /**
     * Data dummy untuk development (jika API key belum diset)
     */
    private function dummyNews(string $category): array
    {
        $articles = [];
        $titles   = [
            'teknologi' => ['AI Semakin Canggih di 2025', 'Startup Tech Indonesia Raih Unicorn', 'Peluncuran Smartphone Terbaru'],
            'olahraga'  => ['Timnas Indonesia Menang Telak', 'Liga 1 Musim Baru Dimulai', 'Atlet Indonesia Juara Dunia'],
            'bisnis'    => ['IHSG Naik Signifikan', 'Investasi Asing Masuk Indonesia', 'Rupiah Menguat Hari Ini'],
        ];

        $catTitles = $titles[$category] ?? ['Berita Terbaru ' . ucfirst($category)];

        for ($i = 0; $i < 12; $i++) {
            $title      = $catTitles[$i % count($catTitles)] . ' - ' . ($i + 1);
            $articles[] = [
                'id'          => md5($category . $i),
                'title'       => $title,
                'description' => 'Ini adalah ringkasan berita tentang ' . $title . '. Berita lengkap dapat dibaca di halaman detail.',
                'url'         => '#',
                'urlToImage'  => 'https://placehold.co/600x400/1a1a2e/ffffff?text=' . urlencode(ucfirst($category)),
                'publishedAt' => now()->subHours($i)->toISOString(),
                'source'      => ['name' => 'BeritaKini Demo'],
                'author'      => 'Redaksi BeritaKini',
                'content'     => 'Konten berita lengkap akan ditampilkan di sini. Pastikan NewsAPI key sudah dikonfigurasi untuk menampilkan berita nyata.',
                'category'    => $category,
            ];
        }

        return ['articles' => $articles, 'totalResults' => 12];
    }
}
