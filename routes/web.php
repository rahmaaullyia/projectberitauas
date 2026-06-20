<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
//use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ───────────────────────────────────────────────────────────
Route::get('/', [NewsController::class, 'index'])->name('home');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');
Route::get('/category/{category}', [NewsController::class, 'byCategory'])->name('news.category');
Route::get('/search', [NewsController::class, 'search'])->name('news.search');

// ─── Auth Routes (Guest only) ─────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Mhs 1: Auth + Kategori Favorit + Simpan Berita ──────────────────────────
Route::middleware('auth')->group(function () {
    // Profile & Kategori Favorit
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/favorite-categories', [ProfileController::class, 'updateFavoriteCategories'])->name('profile.favorites');

    // Simpan / Unsave Berita
    Route::post('/news/{id}/save', [NewsController::class, 'saveNews'])->name('news.save');
    Route::delete('/news/{id}/unsave', [NewsController::class, 'unsaveNews'])->name('news.unsave');
    Route::get('/saved-news', [NewsController::class, 'savedNews'])->name('news.saved');

    // Feed Berdasarkan Kategori Favorit
    Route::get('/my-feed', [NewsController::class, 'myFeed'])->name('news.feed');

    // Mhs 3: Komentar
    //Route::post('/news/{id}/comments', [CommentController::class, 'store'])->name('comments.store');
    //Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// ─── Mhs 3: Admin Panel ───────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/data', [AdminController::class, 'getUsersData'])->name('users.data');
    Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::get('/comments', [AdminController::class, 'comments'])->name('comments');
    Route::get('/comments/data', [AdminController::class, 'getCommentsData'])->name('comments.data');
    Route::delete('/comments/{id}', [AdminController::class, 'deleteComment'])->name('comments.delete');
});
