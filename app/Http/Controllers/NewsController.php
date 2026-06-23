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


    private function fetchNews(string $category = 'general', int $page = 1, string $query = ''): array
    {
        $cacheKey = "news_{$category}_{$page}_{$query}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($category, $page, $query) {
            $endpoint = 'https://newsapi.org/v2/everything';
            $params = [
                'apiKey'         => config('services.newsapi.key'),
                'language'       => 'id',
                'pageSize'       => 30,
                'page'           => $page,
                'sortBy'         => 'publishedAt',
                'excludeDomains' => self::EXCLUDED_DOMAINS,
            ];

            if (!empty($query)) {
                $params['q'] = $query;
            } else {
                $params['qInTitle'] = self::CATEGORY_KEYWORDS[$category] ?? $category;
            }

            $collected = collect();
            $seenIds   = [];

            try {
                $response = Http::timeout(10)->get($endpoint, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    $batch = collect($data['articles'] ?? [])
                        ->map(function ($article, $index) use ($category) {
                            $article['id']       = md5($article['url'] ?? ($category . $index . microtime()));
                            $article['category'] = $category;

                            // Gambar yang sudah ada dari NewsAPI TIDAK diubah.
                            // Hanya artikel tanpa gambar diberi foto pengganti
                            // acak (foto sungguhan, bukan teks placeholder).
                            if (empty($article['urlToImage'])) {
                                $article['urlToImage'] = 'https://picsum.photos/seed/' . $article['id'] . '/600/400';
                            }

                            return $article;
                        })
                        ->filter(fn($a) => !empty($a['title'])
                            && $a['title'] !== '[Removed]'
                            && !empty($a['description']));

                    foreach ($batch as $article) {
                        if ($collected->count() >= 12) {
                            break;
                        }
                        if (!in_array($article['id'], $seenIds)) {
                            $seenIds[]  = $article['id'];
                            $collected->push($article);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('NewsAPI Error: ' . $e->getMessage());
            }

            // Jika hasil dari NewsAPI sudah genap 12, gunakan langsung.
            if ($collected->count() >= 12) {
                return [
                    'articles'     => $collected->values()->toArray(),
                    'totalResults' => 12,
                ];
            }

            // Jika hasil NewsAPI kurang dari 12 (atau kosong), lengkapi
            // sisanya dengan data dummy lokal yang relevan dengan kategori
            // agar total selalu 12 artikel dan isinya tetap sesuai topik.
            $dummy    = $this->dummyNews($category);
            $needed   = 12 - $collected->count();
            $fillers  = array_slice($dummy['articles'], 0, $needed);

            return [
                'articles'     => array_merge($collected->values()->toArray(), $fillers),
                'totalResults' => 12,
            ];
        });
    }

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