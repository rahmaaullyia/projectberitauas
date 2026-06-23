<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{

    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'favorite_categories',
        'is_active',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'password'            => 'hashed',
        'is_active'           => 'boolean',
        'is_admin'            => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function savedNews()
    {
        return $this->hasMany(SavedNews::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFavoriteCategoriesArrayAttribute(): array
    {
        return json_decode($this->favorite_categories ?? '[]', true);
    }

    public function getAvatarAttribute(): string
    {
        $initials = collect(explode(' ', $this->name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->join('');

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name)
            . '&background=6366f1&color=fff&bold=true&size=128';
    }
}
