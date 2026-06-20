<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('news_id')
                  ->comment('Hash MD5 dari URL berita NewsAPI');
            $table->timestamps();

            // Satu user hanya bisa simpan satu berita yang sama sekali
            $table->unique(['user_id', 'news_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_news');
    }
};
