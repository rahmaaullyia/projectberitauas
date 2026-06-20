<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedNews extends Model
{
    use HasFactory;

    protected $table = 'saved_news';

    protected $fillable = [
        'user_id',
        'news_id',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
