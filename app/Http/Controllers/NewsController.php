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

    // Kata kunci pencarian bahasa Indonesia untuk setiap kategori,
    // dipakai dengan parameter qInTitle (mencari di judul saja) agar
    // hasil benar-benar relevan dengan kategori, bukan sekadar
    // menyebut satu kata umum di mana saja dalam artikel.
    // Kata kunci dibuat lebih spesifik (frasa, bukan kata umum tunggal)
    // agar tidak menangkap artikel promo/iklan yang kebetulan menyebut
    // satu kata yang sama (misal "bola" pada promo makanan bertema Piala Dunia).
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

    /**
     * Mhs 2: Ambil berita dari NewsAPI dengan cache 30 menit.
     * Mengembalikan tepat 12 artikel per kategori:
     * - Sumber utama: NewsAPI dengan qInTitle (kata kunci spesifik di
     *   judul) dan excludeDomains untuk menyaring domain promo/iklan
     *   yang sering muncul meskipun judulnya mengandung kata kunci.
     * - Jika hasil relevan dari NewsAPI < 12, sisanya dilengkapi dari
     *   data dummy lokal (DUMMY_ARTICLES) yang sudah ditulis sesuai
     *   topik kategori masing-masing, supaya isi tetap konsisten dan
     *   tidak tercampur konten promo/tidak relevan.
     * Gambar yang sudah ada dari NewsAPI tidak pernah diganti; hanya
     * artikel tanpa gambar yang diberi foto pengganti acak (picsum.photos).
     */
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
     * Data dummy untuk development (jika API key belum diset).
     * Setiap kategori memiliki 12 artikel unik (judul, ringkasan, konten,
     * sumber, dan penulis berbeda-beda) serta gambar yang berbeda di
     * setiap kartu (tidak lagi memakai placeholder teks kategori yang sama).
     */
    private function dummyNews(string $category): array
    {
        $data = self::DUMMY_ARTICLES[$category] ?? [
            [
                'title'       => 'Berita Terbaru ' . ucfirst($category),
                'description' => 'Ringkasan berita terbaru seputar ' . $category . ' yang sedang menjadi perhatian.',
                'content'     => 'Berita lengkap seputar ' . $category . ' akan segera diperbarui oleh redaksi BeritaKini.',
                'source'      => 'BeritaKini',
                'author'      => 'Redaksi BeritaKini',
            ],
        ];

        $sources = ['Antara News', 'Kompas', 'Tempo', 'CNN Indonesia', 'Detik', 'BeritaKini Redaksi'];

        $articles = [];
        foreach ($data as $i => $item) {
            $articles[] = [
                'id'          => md5($category . $i),
                'title'       => $item['title'],
                'description' => $item['description'],
                'url'         => '#',
                // Setiap artikel mendapat foto acak yang berbeda (seed unik per artikel)
                'urlToImage'  => 'https://picsum.photos/seed/' . $category . '-' . ($i + 1) . '/600/400',
                'publishedAt' => now()->subHours($i * 3)->toISOString(),
                'source'      => ['name' => $item['source'] ?? $sources[$i % count($sources)]],
                'author'      => $item['author'] ?? 'Redaksi BeritaKini',
                'content'     => $item['content'],
                'category'    => $category,
            ];
        }

        return ['articles' => $articles, 'totalResults' => count($articles)];
    }

    /**
     * Kumpulan artikel dummy (ditulis sendiri, bukan teks template berulang).
     */
    private const DUMMY_ARTICLES = [
        'teknologi' => [
            ['title' => 'Startup AI Lokal Luncurkan Asisten Virtual Berbahasa Daerah', 'description' => 'Sebuah startup teknologi dalam negeri memperkenalkan asisten virtual yang mendukung beberapa bahasa daerah untuk memudahkan masyarakat di luar kota besar.', 'content' => 'Asisten virtual ini dikembangkan dengan fokus pada pengenalan suara dalam bahasa Jawa, Sunda, dan Minang. Tim pengembang menyebut proyek ini sebagai langkah awal agar teknologi AI lebih inklusif bagi seluruh masyarakat Indonesia, terutama yang belum terbiasa menggunakan bahasa Indonesia formal dalam interaksi digital.', 'source' => 'Tempo'],
            ['title' => 'Pusat Data Nasional Baru Resmi Dibangun di Batam', 'description' => 'Pemerintah menggandeng sejumlah mitra swasta untuk membangun fasilitas pusat data berskala besar guna mendukung layanan digital pemerintahan.', 'content' => 'Fasilitas ini dirancang dengan standar keamanan tinggi dan sumber energi yang lebih ramah lingkungan. Pembangunan ditargetkan selesai dalam dua tahap, dengan tahap pertama difokuskan pada penyimpanan data layanan publik agar akses masyarakat semakin cepat dan aman.', 'source' => 'Antara News'],
            ['title' => 'Produsen Smartphone Rilis Model Kamera 200MP untuk Segmen Menengah', 'description' => 'Sebuah produsen ponsel mengumumkan perangkat terbaru dengan sensor kamera resolusi tinggi yang dibanderol di kelas harga menengah.', 'content' => 'Perangkat ini menyasar konsumen muda yang gemar fotografi namun memiliki anggaran terbatas. Selain kamera, perangkat juga dilengkapi baterai berkapasitas besar dan pengisian cepat, menjadikannya pilihan menarik di pasar ponsel kelas menengah tahun ini.', 'source' => 'Detik'],
            ['title' => 'Perusahaan Teknologi Dalam Negeri Kembangkan Chip untuk Kebutuhan AI', 'description' => 'Sejumlah insinyur lokal mulai merancang chip khusus yang ditujukan untuk mempercepat pemrosesan kecerdasan buatan pada perangkat hemat energi.', 'content' => 'Proyek pengembangan chip ini masih dalam tahap riset dan pengujian prototipe. Jika berhasil, chip tersebut diharapkan dapat digunakan pada perangkat IoT lokal sehingga mengurangi ketergantungan pada komponen impor untuk aplikasi berbasis AI sederhana.', 'source' => 'CNN Indonesia'],
            ['title' => 'Aplikasi Belajar Daring Catat Pertumbuhan Pengguna yang Pesat', 'description' => 'Salah satu platform pembelajaran daring melaporkan peningkatan jumlah pengguna aktif yang signifikan dalam beberapa bulan terakhir.', 'content' => 'Lonjakan pengguna terutama berasal dari siswa di luar Jawa yang mengakses materi belajar tambahan secara mandiri. Pengembang platform menyatakan akan menambah konten lokal agar relevan dengan kurikulum di berbagai daerah.', 'source' => 'Kompas'],
            ['title' => 'Layanan Internet Satelit Mulai Menjangkau Desa Terpencil di Papua', 'description' => 'Program konektivitas digital pemerintah memperluas jangkauan internet satelit ke wilayah yang sebelumnya sulit mendapatkan sinyal.', 'content' => 'Pemasangan perangkat penerima satelit dilakukan secara bertahap di sekolah dan puskesmas terlebih dahulu. Warga setempat menyambut baik program ini karena memudahkan akses informasi dan layanan pendidikan jarak jauh.', 'source' => 'Antara News'],
            ['title' => 'Robot Pelayan Mulai Diuji Coba di Sejumlah Restoran Jakarta', 'description' => 'Beberapa restoran di Jakarta mulai menggunakan robot untuk membantu mengantarkan pesanan ke meja pelanggan.', 'content' => 'Penggunaan robot ini bertujuan mempercepat layanan pada jam sibuk, sementara pelayan manusia tetap bertugas untuk interaksi langsung dengan pelanggan. Sejumlah pengunjung mengaku tertarik mencoba pengalaman makan dengan sentuhan teknologi ini.', 'source' => 'CNN Indonesia'],
            ['title' => 'Pemerintah Dorong Transformasi Digital UMKM Lewat Platform Terpadu', 'description' => 'Sebuah platform digital baru diluncurkan untuk membantu pelaku usaha kecil memasarkan produk secara daring dengan lebih mudah.', 'content' => 'Platform ini menyediakan pelatihan dasar pemasaran digital, manajemen stok sederhana, dan integrasi dengan jasa pengiriman. Pemerintah menargetkan ribuan UMKM dapat bergabung dalam program ini hingga akhir tahun.', 'source' => 'Tempo'],
            ['title' => 'Peneliti Kampus Kembangkan Baterai Tahan Lama untuk Kendaraan Listrik', 'description' => 'Tim peneliti dari salah satu perguruan tinggi mengembangkan formula baterai yang diklaim lebih tahan lama dan ramah lingkungan.', 'content' => 'Riset ini masih dalam tahap pengujian skala laboratorium sebelum dapat diproduksi secara massal. Tim peneliti berharap inovasi ini dapat menurunkan biaya produksi kendaraan listrik dalam negeri di masa depan.', 'source' => 'BeritaKini Redaksi'],
            ['title' => 'Pakar Imbau Masyarakat Perkuat Keamanan Data Pribadi di Internet', 'description' => 'Meningkatnya kasus kebocoran data membuat para ahli mengingatkan pentingnya kebiasaan digital yang lebih aman.', 'content' => 'Beberapa langkah sederhana seperti menggunakan kata sandi unik, mengaktifkan verifikasi dua langkah, dan tidak membagikan informasi pribadi sembarangan dinilai dapat mengurangi risiko penyalahgunaan data secara signifikan.', 'source' => 'Detik'],
            ['title' => 'Platform E-commerce Lokal Uji Coba Pengiriman Menggunakan Drone', 'description' => 'Sebuah perusahaan e-commerce memulai uji coba terbatas pengiriman barang ringan menggunakan drone di kawasan perkotaan.', 'content' => 'Uji coba dilakukan untuk rute pendek dengan pengawasan ketat demi keselamatan. Jika berhasil, metode ini diharapkan dapat mempercepat pengiriman barang kecil di area dengan kepadatan lalu lintas tinggi.', 'source' => 'Kompas'],
            ['title' => 'Generasi Baru Laptop Hemat Energi Resmi Masuk Pasar Indonesia', 'description' => 'Produsen perangkat komputer memperkenalkan lini laptop baru dengan konsumsi daya lebih rendah namun performa yang tetap kompetitif.', 'content' => 'Laptop ini ditujukan untuk pelajar dan pekerja yang membutuhkan daya tahan baterai panjang. Selain efisiensi energi, produk juga menggunakan sebagian material daur ulang dalam proses produksinya.', 'source' => 'Antara News'],
        ],
        'olahraga' => [
            ['title' => 'Timnas Indonesia Melaju ke Babak Final Turnamen Persahabatan', 'description' => 'Tim nasional Indonesia memastikan tempat di partai final setelah meraih hasil positif pada laga semifinal.', 'content' => 'Pelatih menyebut kekompakan tim dan strategi bertahan yang rapi menjadi kunci keberhasilan melaju ke final. Para pemain dijadwalkan menjalani latihan intensif menjelang laga puncak yang akan digelar akhir pekan ini.', 'source' => 'CNN Indonesia'],
            ['title' => 'Liga 1 Memasuki Pekan Kesepuluh, Persaingan Papan Atas Memanas', 'description' => 'Sejumlah klub papan atas Liga 1 saling kejar poin menjelang paruh musim kompetisi.', 'content' => 'Beberapa pertandingan berlangsung ketat dengan selisih gol minim. Para analis memperkirakan persaingan gelar juara baru akan benar-benar terlihat jelas setelah jeda internasional mendatang.', 'source' => 'Tempo'],
            ['title' => 'Atlet Bulu Tangkis Indonesia Raih Medali di Kejuaraan Dunia', 'description' => 'Wakil Indonesia berhasil menyumbangkan medali pada ajang bulu tangkis internasional setelah penampilan konsisten sepanjang turnamen.', 'content' => 'Prestasi ini menambah daftar capaian atlet muda Indonesia di kancah dunia. Pelatih nasional menilai hasil ini menjadi modal positif menuju ajang multievent yang akan datang.', 'source' => 'Antara News'],
            ['title' => 'Pelatih Baru Resmi Tangani Tim Nasional Sepak Bola U-23', 'description' => 'Federasi sepak bola mengumumkan penunjukan pelatih baru untuk membina skuad muda menjelang turnamen regional mendatang.', 'content' => 'Pelatih baru tersebut akan langsung memimpin pemusatan latihan dengan menyeleksi sejumlah pemain muda berbakat dari berbagai klub. Fokus utama pelatihan adalah membangun pola permainan yang lebih cepat dan terstruktur.', 'source' => 'Detik'],
            ['title' => 'Maraton Jakarta Diikuti Ribuan Pelari dari Berbagai Daerah', 'description' => 'Ajang lari tahunan di Jakarta kembali digelar dengan jumlah peserta yang meningkat dibanding tahun sebelumnya.', 'content' => 'Rute lomba melintasi sejumlah landmark kota dan ditutup untuk kendaraan selama acara berlangsung. Panitia juga menyiapkan pos kesehatan di sepanjang rute untuk memastikan keselamatan peserta.', 'source' => 'Kompas'],
            ['title' => 'Esports Resmi Masuk Cabang Pertandingan Ajang Multievent Regional', 'description' => 'Cabang olahraga elektronik akan dipertandingkan secara resmi pada ajang multievent kawasan tahun depan.', 'content' => 'Beberapa judul game kompetitif telah ditetapkan sebagai cabang yang dipertandingkan. Tim nasional esports mulai melakukan seleksi pemain melalui turnamen kualifikasi tingkat nasional.', 'source' => 'CNN Indonesia'],
            ['title' => 'Tim Voli Putri Indonesia Juara Turnamen Tingkat Regional', 'description' => 'Tim bola voli putri nasional tampil dominan dan berhasil mengangkat trofi juara pada turnamen antarnegara di kawasan.', 'content' => 'Kemenangan ini diraih setelah melalui pertandingan ketat di babak final. Para pemain mengaku latihan intensif selama beberapa bulan terakhir membuahkan hasil yang menggembirakan.', 'source' => 'Antara News'],
            ['title' => 'Stadion Baru di Jawa Timur Siap Gelar Laga Tingkat Internasional', 'description' => 'Sebuah stadion modern di Jawa Timur telah menyelesaikan tahap akhir pembangunan dan siap digunakan untuk pertandingan internasional.', 'content' => 'Stadion ini dilengkapi fasilitas standar internasional, termasuk lapangan rumput hibrida dan tribun berkapasitas besar. Uji coba pertandingan pertama dijadwalkan dalam waktu dekat sebelum digunakan untuk laga resmi.', 'source' => 'Tempo'],
            ['title' => 'Petinju Muda Indonesia Tampil Mengesankan di Laga Debutnya', 'description' => 'Seorang petinju muda berhasil mencatatkan kemenangan meyakinkan pada pertandingan profesional pertamanya.', 'content' => 'Penampilan tersebut mendapat pujian dari para pengamat tinju nasional. Sang petinju menyebut akan terus berlatih lebih keras untuk menghadapi lawan yang lebih berpengalaman di pertandingan selanjutnya.', 'source' => 'Detik'],
            ['title' => 'Federasi Renang Luncurkan Program Pembinaan Atlet Usia Dini', 'description' => 'Program pembinaan jangka panjang diperkenalkan untuk menjaring talenta renang sejak usia sekolah dasar.', 'content' => 'Program ini akan dilaksanakan di beberapa kota besar dengan dukungan pelatih bersertifikat. Federasi berharap program ini dapat melahirkan generasi perenang kompetitif dalam lima hingga sepuluh tahun mendatang.', 'source' => 'Kompas'],
            ['title' => 'Turnamen Catur Nasional Digelar Secara Hybrid Online dan Offline', 'description' => 'Kompetisi catur tingkat nasional tahun ini menggabungkan format pertandingan langsung dan daring untuk menjangkau lebih banyak peserta.', 'content' => 'Format hybrid ini memungkinkan peserta dari daerah terpencil tetap dapat berkompetisi tanpa perlu melakukan perjalanan jauh. Babak final tetap akan dilaksanakan secara langsung di kota penyelenggara.', 'source' => 'BeritaKini Redaksi'],
            ['title' => 'Pebalap Muda Indonesia Tampil di Ajang Reli Internasional', 'description' => 'Seorang pebalap muda asal Indonesia mendapat kesempatan berkompetisi pada seri reli tingkat internasional.', 'content' => 'Pengalaman ini dinilai berharga untuk pengembangan kariernya di dunia balap. Tim pendukungnya menyebut hasil dari ajang ini akan menjadi evaluasi penting untuk persiapan musim berikutnya.', 'source' => 'Antara News'],
        ],
        'bisnis' => [
            ['title' => 'Rupiah Menguat Tipis Ditopang Sentimen Pasar Regional', 'description' => 'Nilai tukar rupiah terhadap dolar AS bergerak menguat dalam perdagangan menyusul perbaikan sentimen di pasar regional.', 'content' => 'Analis menyebut penguatan ini dipengaruhi oleh ekspektasi kebijakan moneter global yang lebih stabil. Pelaku pasar diperkirakan akan tetap berhati-hati menjelang rilis data ekonomi penting pekan ini.', 'source' => 'CNN Indonesia'],
            ['title' => 'IHSG Ditutup Menguat, Sektor Energi Jadi Penopang Utama', 'description' => 'Indeks Harga Saham Gabungan ditutup di zona hijau dengan saham-saham sektor energi mencatat kenaikan signifikan.', 'content' => 'Kenaikan harga komoditas global turut mendorong minat investor terhadap saham-saham berbasis sumber daya alam. Sejumlah analis memperkirakan tren ini masih dapat berlanjut dalam jangka pendek.', 'source' => 'Kompas'],
            ['title' => 'Pemerintah Tambah Insentif untuk Investasi Industri Hijau', 'description' => 'Sejumlah insentif fiskal baru ditawarkan kepada investor yang menanamkan modal pada sektor industri ramah lingkungan.', 'content' => 'Insentif tersebut mencakup keringanan pajak dan kemudahan perizinan bagi perusahaan yang menggunakan teknologi rendah emisi. Pemerintah berharap kebijakan ini dapat menarik lebih banyak investasi berkelanjutan.', 'source' => 'Tempo'],
            ['title' => 'Produk Kerajinan UMKM Indonesia Makin Diminati Pasar Eropa', 'description' => 'Permintaan produk kerajinan tangan dari pelaku usaha kecil Indonesia tercatat meningkat di sejumlah negara Eropa.', 'content' => 'Peningkatan ini didorong oleh promosi melalui pameran dagang internasional dan platform e-commerce lintas negara. Pelaku UMKM diharapkan dapat menjaga kualitas dan konsistensi produksi untuk memenuhi permintaan ekspor.', 'source' => 'Antara News'],
            ['title' => 'Bank Sentral Pertahankan Suku Bunga Acuan Bulan Ini', 'description' => 'Bank sentral memutuskan untuk tidak mengubah suku bunga acuan dengan pertimbangan menjaga stabilitas harga dan nilai tukar.', 'content' => 'Keputusan ini sejalan dengan ekspektasi sebagian besar pelaku pasar. Bank sentral menyatakan akan terus memantau perkembangan ekonomi global sebelum mengambil langkah kebijakan selanjutnya.', 'source' => 'Detik'],
            ['title' => 'Startup Lokal di Bidang Logistik Raih Pendanaan Tahap Lanjutan', 'description' => 'Sebuah perusahaan rintisan di sektor logistik mengumumkan perolehan pendanaan baru untuk memperluas jaringan operasionalnya.', 'content' => 'Dana segar tersebut rencananya digunakan untuk menambah armada pengiriman dan memperluas jangkauan layanan ke kota-kota lapis dua. Perusahaan menyebut permintaan layanan logistik terus meningkat sejalan dengan pertumbuhan e-commerce.', 'source' => 'CNN Indonesia'],
            ['title' => 'Harga Komoditas Perkebunan Naik, Petani Diuntungkan', 'description' => 'Kenaikan harga sejumlah komoditas perkebunan di pasar global memberikan dampak positif bagi pendapatan petani lokal.', 'content' => 'Para petani menyambut baik kenaikan harga ini setelah beberapa waktu mengalami fluktuasi yang cukup tajam. Pemerintah daerah juga mendorong petani untuk meningkatkan kualitas hasil panen agar nilai jualnya semakin baik.', 'source' => 'Kompas'],
            ['title' => 'Sektor Pariwisata Catat Pertumbuhan Pendapatan yang Menggembirakan', 'description' => 'Pendapatan dari sektor pariwisata domestik tercatat meningkat dibandingkan periode sebelumnya seiring tingginya minat wisata dalam negeri.', 'content' => 'Sejumlah destinasi wisata di luar Jawa dan Bali turut menikmati peningkatan kunjungan. Pelaku usaha pariwisata berharap tren positif ini dapat terus berlanjut hingga musim liburan mendatang.', 'source' => 'Tempo'],
            ['title' => 'Perusahaan Ritel Nasional Perluas Jaringan ke Kota Lapis Dua', 'description' => 'Sebuah perusahaan ritel besar mengumumkan rencana ekspansi gerai baru ke sejumlah kota berkembang di luar Jawa.', 'content' => 'Ekspansi ini dilakukan untuk menangkap potensi pasar di daerah dengan pertumbuhan penduduk yang tinggi. Perusahaan juga berencana merekrut tenaga kerja lokal untuk mendukung operasional gerai baru.', 'source' => 'Antara News'],
            ['title' => 'Investasi Asing Langsung ke Indonesia Meningkat Kuartal Ini', 'description' => 'Realisasi investasi asing langsung tercatat tumbuh dibandingkan periode sebelumnya, didorong oleh sektor manufaktur dan energi.', 'content' => 'Pemerintah menyebut perbaikan kemudahan berusaha menjadi salah satu faktor pendorong masuknya investasi baru. Sejumlah proyek investasi besar masih dalam proses negosiasi dan diperkirakan terealisasi dalam waktu dekat.', 'source' => 'Detik'],
            ['title' => 'Produksi Mobil Listrik Dalam Negeri Mulai Berjalan', 'description' => 'Sebuah pabrik di dalam negeri resmi memulai produksi kendaraan listrik dengan komponen yang sebagian diproduksi secara lokal.', 'content' => 'Langkah ini menjadi bagian dari upaya pemerintah mendorong ekosistem kendaraan listrik nasional. Produsen menargetkan peningkatan kandungan komponen lokal secara bertahap dalam beberapa tahun mendatang.', 'source' => 'Kompas'],
            ['title' => 'Harga Bahan Pangan Pokok Relatif Stabil Menjelang Akhir Tahun', 'description' => 'Pemantauan harga di sejumlah pasar tradisional menunjukkan stok bahan pangan pokok dalam kondisi aman menjelang akhir tahun.', 'content' => 'Pemerintah daerah menyatakan akan terus memantau distribusi agar harga tetap terjangkau. Operasi pasar murah juga disiapkan sebagai langkah antisipasi jika terjadi kenaikan permintaan yang signifikan.', 'source' => 'BeritaKini Redaksi'],
        ],
        'kesehatan' => [
            ['title' => 'Kementerian Kesehatan Perluas Program Vaksinasi ke Daerah Terpencil', 'description' => 'Program vaksinasi tambahan diperluas ke wilayah dengan akses layanan kesehatan yang masih terbatas.', 'content' => 'Tim kesehatan keliling dikirim ke sejumlah desa untuk memastikan masyarakat mendapatkan akses vaksinasi yang setara. Program ini diharapkan dapat menekan angka penyakit yang dapat dicegah dengan imunisasi.', 'source' => 'Antara News'],
            ['title' => 'Studi Terbaru Soroti Pentingnya Tidur Cukup bagi Kesehatan Jantung', 'description' => 'Penelitian kesehatan menunjukkan kaitan antara durasi tidur yang cukup dengan risiko gangguan jantung yang lebih rendah.', 'content' => 'Para peneliti menyarankan orang dewasa tidur antara tujuh hingga delapan jam setiap malam. Kebiasaan tidur yang teratur juga disebut dapat membantu menjaga tekanan darah dan kadar gula darah tetap stabil.', 'source' => 'Kompas'],
            ['title' => 'Layanan Konsultasi Kesehatan Daring Diperkenalkan di Rumah Sakit Daerah', 'description' => 'Sejumlah rumah sakit daerah mulai menyediakan layanan konsultasi jarak jauh untuk memudahkan pasien di wilayah terpencil.', 'content' => 'Layanan ini memungkinkan pasien berkonsultasi dengan dokter spesialis tanpa harus melakukan perjalanan jauh ke kota besar. Petugas kesehatan setempat akan membantu pasien yang belum familiar dengan teknologi digital.', 'source' => 'Tempo'],
            ['title' => 'Dinas Kesehatan Imbau Waspada Peningkatan Penyakit Musiman', 'description' => 'Pergantian musim memicu peningkatan kasus penyakit musiman, sehingga masyarakat diimbau menjaga daya tahan tubuh.', 'content' => 'Petugas kesehatan mengingatkan pentingnya pola makan seimbang, istirahat cukup, dan menjaga kebersihan lingkungan. Fasilitas kesehatan setempat juga disiagakan untuk mengantisipasi lonjakan kunjungan pasien.', 'source' => 'Detik'],
            ['title' => 'Olahraga Ringan Rutin Terbukti Bantu Turunkan Risiko Diabetes', 'description' => 'Aktivitas fisik ringan yang dilakukan secara teratur dapat membantu menjaga kadar gula darah tetap normal.', 'content' => 'Para ahli menyarankan aktivitas sederhana seperti berjalan kaki selama tiga puluh menit setiap hari. Kombinasi olahraga dan pola makan sehat dinilai efektif dalam mengurangi risiko penyakit tidak menular.', 'source' => 'CNN Indonesia'],
            ['title' => 'Program Gizi Anak Sekolah Diperluas ke Daerah Tertinggal', 'description' => 'Program pemberian makanan tambahan bergizi bagi siswa sekolah dasar kini menjangkau lebih banyak daerah tertinggal.', 'content' => 'Program ini bertujuan mengurangi angka kekurangan gizi pada anak usia sekolah. Selain makanan tambahan, sekolah juga diberikan edukasi mengenai pola makan sehat bagi siswa dan orang tua.', 'source' => 'Antara News'],
            ['title' => 'Peneliti Lokal Kembangkan Ramuan Herbal untuk Bantu Kontrol Tekanan Darah', 'description' => 'Sebuah tim peneliti meneliti potensi tanaman herbal lokal sebagai pelengkap terapi bagi penderita tekanan darah tinggi.', 'content' => 'Penelitian masih dalam tahap uji klinis awal dengan jumlah partisipan terbatas. Peneliti menekankan bahwa hasil ini belum dapat menggantikan pengobatan medis dan perlu dikonsultasikan lebih lanjut dengan dokter.', 'source' => 'BeritaKini Redaksi'],
            ['title' => 'Pentingnya Menjaga Kesehatan Mental di Tengah Kesibukan Kerja', 'description' => 'Para psikolog mengingatkan pekerja untuk meluangkan waktu istirahat guna menjaga kesehatan mental di tengah tekanan kerja.', 'content' => 'Beberapa langkah sederhana seperti mengatur waktu kerja, berbicara dengan orang terdekat, dan melakukan aktivitas yang menyenangkan dapat membantu mengurangi stres. Dukungan dari lingkungan kerja juga dinilai berperan penting.', 'source' => 'Kompas'],
            ['title' => 'Pemeriksaan Kesehatan Gratis Digelar di Puskesmas Seluruh Kota', 'description' => 'Pemerintah kota menggelar pemeriksaan kesehatan gratis bagi warga untuk mendeteksi dini berbagai penyakit tidak menular.', 'content' => 'Pemeriksaan meliputi pengukuran tekanan darah, gula darah, dan indeks massa tubuh. Warga yang ditemukan memiliki indikasi risiko akan dirujuk untuk pemeriksaan lebih lanjut di fasilitas kesehatan terdekat.', 'source' => 'Tempo'],
            ['title' => 'Konsumsi Air Putih yang Cukup Dukung Konsentrasi Sehari-hari', 'description' => 'Ahli gizi menekankan pentingnya menjaga asupan cairan harian untuk mendukung fungsi otak dan konsentrasi.', 'content' => 'Kekurangan cairan ringan saja dapat memengaruhi mood dan fokus seseorang. Para ahli menyarankan membawa botol minum sebagai pengingat untuk minum secara teratur sepanjang hari.', 'source' => 'Detik'],
            ['title' => 'Vaksinasi Influenza Disarankan Menjelang Pergantian Musim', 'description' => 'Tenaga medis menganjurkan kelompok rentan untuk melakukan vaksinasi influenza menjelang perubahan musim.', 'content' => 'Kelompok lanjut usia dan anak-anak disebut lebih rentan terhadap komplikasi akibat infeksi musiman. Fasilitas kesehatan setempat menyediakan layanan vaksinasi dengan jadwal yang dapat disesuaikan.', 'source' => 'CNN Indonesia'],
            ['title' => 'Aplikasi Kesehatan Digital Permudah Pemantauan Pasien Penyakit Kronis', 'description' => 'Sebuah aplikasi kesehatan baru membantu pasien penyakit kronis memantau kondisi dan jadwal pengobatan secara mandiri.', 'content' => 'Aplikasi ini memungkinkan pasien mencatat hasil pemeriksaan rutin dan mengatur pengingat minum obat. Data yang tercatat juga dapat dibagikan kepada dokter untuk membantu proses pemantauan jangka panjang.', 'source' => 'Antara News'],
        ],
        'hiburan' => [
            ['title' => 'Film Animasi Garapan Sineas Lokal Raih Sambutan Positif Penonton', 'description' => 'Sebuah film animasi produksi dalam negeri mendapat antusiasme tinggi dari penonton sejak hari pertama penayangan.', 'content' => 'Film ini mengangkat cerita rakyat yang dikemas dengan visual modern. Tim produksi menyebut proses pembuatan memakan waktu lebih dari dua tahun dengan melibatkan animator-animator muda Indonesia.', 'source' => 'CNN Indonesia'],
            ['title' => 'Konser Musik Tahunan Bertema Nostalgia Siap Digelar di Jakarta', 'description' => 'Sejumlah musisi lintas generasi akan tampil dalam konser musik tahunan yang mengusung tema nostalgia.', 'content' => 'Konser ini diharapkan dapat menjadi ajang reuni bagi penggemar musik dari berbagai era. Panitia menyiapkan tata panggung dan pencahayaan khusus untuk menghadirkan suasana yang berbeda dari konser sebelumnya.', 'source' => 'Detik'],
            ['title' => 'Serial Drama Baru Curi Perhatian Penonton Muda di Layanan Streaming', 'description' => 'Sebuah serial drama produksi lokal menjadi salah satu tontonan paling banyak dibicarakan di platform streaming dalam negeri.', 'content' => 'Cerita yang diangkat dianggap relevan dengan kehidupan sehari-hari anak muda perkotaan. Para pemeran utama menyebut proses syuting dilakukan dengan jadwal yang cukup padat namun tetap menyenangkan.', 'source' => 'Kompas'],
            ['title' => 'Festival Film Indonesia Umumkan Daftar Nominasi Tahun Ini', 'description' => 'Penyelenggara festival film tahunan resmi merilis daftar nominasi untuk berbagai kategori penghargaan.', 'content' => 'Sejumlah film dan aktor pendatang baru turut masuk dalam daftar nominasi tahun ini. Malam penganugerahan akan digelar dalam waktu dekat dan disiarkan secara langsung di televisi nasional.', 'source' => 'Tempo'],
            ['title' => 'Penyanyi Muda Rilis Single Terbaru dengan Aransemen Akustik', 'description' => 'Seorang penyanyi muda merilis lagu terbaru dengan sentuhan musik akustik yang berbeda dari karya-karya sebelumnya.', 'content' => 'Lagu ini terinspirasi dari pengalaman pribadi sang penyanyi selama masa pandemi. Penggemar menyambut positif perubahan gaya musik tersebut dan menilai liriknya cukup relate dengan kehidupan sehari-hari.', 'source' => 'Antara News'],
            ['title' => 'Pameran Seni Digital Interaktif Hadir di Pusat Perbelanjaan Jakarta', 'description' => 'Sebuah pameran seni digital interaktif dibuka untuk umum dan menampilkan karya seniman muda Indonesia.', 'content' => 'Pengunjung dapat berinteraksi langsung dengan karya seni melalui sentuhan dan gerakan tubuh. Pameran ini berlangsung selama beberapa minggu dan terbuka secara gratis bagi masyarakat umum.', 'source' => 'CNN Indonesia'],
            ['title' => 'Tur Stand Up Comedy Keliling Kota Disambut Antusias Penonton', 'description' => 'Sejumlah komika tampil dalam tur komedi tunggal yang berkeliling ke beberapa kota besar di Indonesia.', 'content' => 'Setiap kota menghadirkan materi yang disesuaikan dengan budaya lokal masing-masing daerah. Tiket pertunjukan dilaporkan terjual habis dalam waktu singkat di beberapa lokasi.', 'source' => 'Detik'],
            ['title' => 'Webtoon Karya Komikus Lokal Resmi Diadaptasi Jadi Serial Televisi', 'description' => 'Sebuah webtoon populer karya komikus dalam negeri kini diadaptasi menjadi serial televisi.', 'content' => 'Proses adaptasi dilakukan dengan tetap mempertahankan alur cerita asli namun disesuaikan untuk format layar kaca. Komikus asli turut dilibatkan sebagai konsultan kreatif selama proses produksi.', 'source' => 'Kompas'],
            ['title' => 'Komunitas Cosplay Gelar Acara Tahunan Bertema Fantasi', 'description' => 'Ratusan penggemar cosplay berkumpul dalam acara tahunan yang mengusung tema dunia fantasi.', 'content' => 'Acara ini menampilkan berbagai kostum kreatif hasil karya peserta sendiri. Selain kompetisi kostum, acara juga diisi dengan sesi foto bersama dan diskusi komunitas penggemar budaya populer.', 'source' => 'BeritaKini Redaksi'],
            ['title' => 'Podcast Bertema Sejarah Indonesia Makin Diminati Anak Muda', 'description' => 'Sejumlah podcast yang membahas sejarah Indonesia dengan gaya santai mendapat perhatian luas dari pendengar muda.', 'content' => 'Para pembuat konten menyebut format obrolan ringan membuat topik sejarah terasa lebih mudah dipahami. Banyak pendengar mengaku baru mengetahui sejumlah fakta sejarah setelah mendengarkan podcast tersebut.', 'source' => 'Tempo'],
            ['title' => 'Grup Band Indie Sukses Gelar Tur Konser di Lima Kota', 'description' => 'Sebuah grup musik indie menyelesaikan rangkaian tur konser di lima kota dengan sambutan hangat dari penggemar.', 'content' => 'Setiap kota menghadirkan suasana panggung yang berbeda sesuai karakter lokal masing-masing. Band ini menyebut tur kali ini menjadi pengalaman berharga sebelum mereka merilis album penuh berikutnya.', 'source' => 'Antara News'],
            ['title' => 'Bioskop Independen Gelar Maraton Penayangan Film Klasik Indonesia', 'description' => 'Sebuah bioskop independen mengadakan acara penayangan ulang sejumlah film klasik Indonesia yang sudah lama tidak tayang di bioskop.', 'content' => 'Acara ini bertujuan memperkenalkan kembali karya-karya sinema lawas kepada generasi muda. Beberapa sesi diskusi bersama pengamat film juga digelar setelah penayangan untuk membahas nilai sejarah dari masing-masing film.', 'source' => 'CNN Indonesia'],
        ],
        'sains' => [
            ['title' => 'Peneliti Temukan Spesies Katak Baru di Hutan Kalimantan', 'description' => 'Ekspedisi tim peneliti berhasil mengidentifikasi spesies katak yang belum pernah tercatat sebelumnya di kawasan hutan Kalimantan.', 'content' => 'Spesies ini ditemukan pada ketinggian tertentu dengan karakteristik warna kulit yang khas. Penemuan ini menambah daftar keanekaragaman hayati Indonesia dan menjadi pengingat pentingnya menjaga kelestarian hutan tropis.', 'source' => 'Antara News'],
            ['title' => 'Observatorium Nasional Catat Fenomena Hujan Meteor yang Cukup Langka', 'description' => 'Pengamatan astronomi mencatat fenomena hujan meteor dengan intensitas yang lebih tinggi dari biasanya pada periode tertentu.', 'content' => 'Fenomena ini dapat disaksikan dengan mata telanjang pada malam yang cerah dan minim cahaya kota. Pengamat astronomi amatir di berbagai daerah turut berbagi dokumentasi hasil pengamatan mereka secara daring.', 'source' => 'Kompas'],
            ['title' => 'Tim Peneliti Kembangkan Metode Daur Ulang Plastik yang Lebih Efisien', 'description' => 'Sebuah metode baru pengolahan sampah plastik dikembangkan untuk menghasilkan bahan baku yang lebih mudah digunakan kembali.', 'content' => 'Metode ini diklaim membutuhkan energi lebih rendah dibandingkan proses daur ulang konvensional. Tim peneliti berharap teknologi ini dapat diterapkan pada skala industri kecil di berbagai daerah.', 'source' => 'Tempo'],
            ['title' => 'Studi Iklim Tunjukkan Pola Curah Hujan di Jawa Mulai Berubah', 'description' => 'Penelitian iklim jangka panjang menemukan adanya perubahan pola curah hujan di sejumlah wilayah Jawa.', 'content' => 'Perubahan ini disebut dapat berdampak pada pola tanam petani jika tidak diantisipasi sejak dini. Peneliti menyarankan adanya penyesuaian kalender tanam berdasarkan data iklim terbaru.', 'source' => 'CNN Indonesia'],
            ['title' => 'Mahasiswa Indonesia Ciptakan Alat Pendeteksi Kualitas Udara Murah', 'description' => 'Sekelompok mahasiswa mengembangkan perangkat sederhana untuk memantau kualitas udara dengan biaya produksi yang terjangkau.', 'content' => 'Alat ini menggunakan sensor yang tersedia di pasaran namun dirancang ulang agar lebih hemat biaya. Prototipe alat telah diuji di beberapa titik perkotaan dan menunjukkan hasil yang cukup akurat dibandingkan alat standar.', 'source' => 'Detik'],
            ['title' => 'Penelitian Laut Dalam Temukan Terumbu Karang Baru di Sulawesi', 'description' => 'Ekspedisi penelitian laut menemukan kawasan terumbu karang yang belum pernah terdokumentasikan sebelumnya di perairan Sulawesi.', 'content' => 'Kawasan tersebut diketahui memiliki keanekaragaman biota laut yang cukup tinggi. Para peneliti merekomendasikan kawasan ini dijadikan sebagai area konservasi guna menjaga ekosistem laut di sekitarnya.', 'source' => 'Antara News'],
            ['title' => 'Program Riset Energi Terbarukan Skala Kecil Diluncurkan', 'description' => 'Sebuah lembaga riset meluncurkan program pengembangan energi terbarukan berskala kecil untuk diterapkan di desa-desa.', 'content' => 'Program ini mencakup pemanfaatan tenaga surya dan air dalam skala mikro yang disesuaikan dengan kebutuhan masing-masing desa. Warga setempat juga dilibatkan dalam pelatihan perawatan perangkat agar dapat dikelola secara mandiri.', 'source' => 'BeritaKini Redaksi'],
            ['title' => 'Eksperimen Pertanian Vertikal Berhasil Diterapkan di Lahan Perkotaan', 'description' => 'Uji coba pertanian vertikal di lahan terbatas perkotaan menunjukkan hasil panen yang menjanjikan untuk sayuran tertentu.', 'content' => 'Metode ini dinilai cocok untuk wilayah dengan lahan terbatas namun memiliki permintaan sayuran segar yang tinggi. Pengelola berencana mengembangkan model ini ke beberapa lokasi lain di area perkotaan.', 'source' => 'Tempo'],
            ['title' => 'Penemuan Fosil Purba di Nusa Tenggara Ungkap Sejarah Baru', 'description' => 'Tim arkeolog menemukan fragmen fosil yang diperkirakan berasal dari masa prasejarah di kawasan Nusa Tenggara.', 'content' => 'Penelitian lebih lanjut masih dilakukan untuk memastikan usia dan jenis fosil tersebut. Penemuan ini diharapkan dapat memberikan gambaran baru mengenai sejarah kehidupan purba di kawasan tersebut.', 'source' => 'Kompas'],
            ['title' => 'Satelit Cuaca Buatan Lokal Siap Diluncurkan Tahun Depan', 'description' => 'Lembaga riset dalam negeri menyatakan satelit cuaca hasil rancangan lokal telah memasuki tahap akhir pengujian.', 'content' => 'Satelit ini diharapkan dapat membantu meningkatkan akurasi prakiraan cuaca, khususnya untuk wilayah kepulauan. Proses peluncuran direncanakan dilakukan bekerja sama dengan mitra penyedia roket peluncur.', 'source' => 'CNN Indonesia'],
            ['title' => 'Riset Genetika Hasilkan Varietas Padi yang Lebih Tahan Kekeringan', 'description' => 'Penelitian pemuliaan tanaman berhasil mengembangkan varietas padi baru dengan toleransi lebih baik terhadap kondisi kekeringan.', 'content' => 'Varietas ini telah melalui beberapa kali uji tanam di lahan percontohan dengan hasil yang cukup memuaskan. Peneliti berharap varietas ini dapat membantu petani di wilayah rawan kekeringan menjaga produktivitas panen.', 'source' => 'Antara News'],
            ['title' => 'Komunitas Astronomi Amatir Gelar Pengamatan Bersama Gerhana Bulan', 'description' => 'Komunitas pegiat astronomi mengadakan acara pengamatan bersama saat fenomena gerhana bulan berlangsung.', 'content' => 'Acara ini terbuka untuk umum dan menyediakan teleskop bagi pengunjung yang ingin melihat fenomena tersebut secara lebih jelas. Anggota komunitas juga memberikan penjelasan sederhana mengenai proses terjadinya gerhana.', 'source' => 'Detik'],
        ],
    ];
}