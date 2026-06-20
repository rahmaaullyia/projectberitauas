<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin ──────────────────────────────────────────────────────────────
        User::create([
            'name'                => 'Administrator',
            'email'               => 'admin@beritakini.id',
            'password'            => Hash::make('password'),
            'favorite_categories' => json_encode(['teknologi', 'bisnis']),
            'is_active'           => true,
            'is_admin'            => true,
        ]);

        // ─── Mhs 1 (User biasa dengan kategori favorit) ─────────────────────────
        $user1 = User::create([
            'name'                => 'Budi Santoso',
            'email'               => 'budi@example.com',
            'password'            => Hash::make('password'),
            'favorite_categories' => json_encode(['olahraga', 'teknologi']),
            'is_active'           => true,
            'is_admin'            => false,
        ]);

        // ─── Mhs 2 ──────────────────────────────────────────────────────────────
        $user2 = User::create([
            'name'                => 'Siti Rahayu',
            'email'               => 'siti@example.com',
            'password'            => Hash::make('password'),
            'favorite_categories' => json_encode(['bisnis', 'kesehatan']),
            'is_active'           => true,
            'is_admin'            => false,
        ]);

        // ─── Mhs 3 (User nonaktif untuk demo admin) ─────────────────────────────
        $user3 = User::create([
            'name'                => 'Agus Wijaya',
            'email'               => 'agus@example.com',
            'password'            => Hash::make('password'),
            'favorite_categories' => json_encode(['hiburan']),
            'is_active'           => false,
            'is_admin'            => false,
        ]);

        // ─── Sample Comments ─────────────────────────────────────────────────────
        $newsIds = [
            md5('teknologi0'), md5('teknologi1'),
            md5('olahraga0'), md5('bisnis0'),
        ];

        $comments = [
            ['Artikel yang sangat informatif! Terima kasih sudah berbagi.', $user1->id],
            ['Wah, perkembangan yang luar biasa ya.', $user2->id],
            ['Setuju dengan pendapat penulis.', $user1->id],
            ['Semoga berita ini bisa jadi motivasi buat kita semua.', $user2->id],
        ];

        foreach ($newsIds as $i => $newsId) {
            foreach (array_slice($comments, 0, 2) as $comment) {
                Comment::create([
                    'user_id' => $comment[1],
                    'news_id' => $newsId,
                    'body'    => $comment[0],
                ]);
            }
        }

        $this->command->info('✅ Seeder selesai!');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',    'admin@beritakini.id', 'password'],
                ['User Mhs1', 'budi@example.com',   'password'],
                ['User Mhs2', 'siti@example.com',   'password'],
                ['User Mhs3', 'agus@example.com',   'password (nonaktif)'],
            ]
        );
    }
}
