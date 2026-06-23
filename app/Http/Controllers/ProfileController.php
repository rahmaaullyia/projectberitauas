<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    const AVAILABLE_CATEGORIES = [
        'teknologi' => 'Teknologi',
        'olahraga'  => 'Olahraga',
        'bisnis'    => 'Bisnis',
        'kesehatan' => 'Kesehatan',
        'hiburan'   => 'Hiburan',
        'sains'     => 'Sains',
    ];

    /**
     * Form edit profil & kategori favorit
     */
    public function edit()
    {
        $user               = Auth::user();
        $favoriteCategories = json_decode($user->favorite_categories ?? '[]', true);
        $availableCategories = self::AVAILABLE_CATEGORIES;

        return view('profile.edit', compact('user', 'favoriteCategories', 'availableCategories'));
    }

    /**
     * Update profil (nama, email, password)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'password'              => 'nullable|min:8|confirmed',
            'current_password'      => 'nullable|required_with:password',
        ]);

        // Verifikasi password lama jika ingin ganti password
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
            }
            $user->password = Hash::make($validated['password']);
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Update kategori favorit (Mhs 1: Sistem Kategori Favorit)
     */
    public function updateFavoriteCategories(Request $request)
    {
        $validated = $request->validate([
            'categories'   => 'required|array|min:1|max:6',
            'categories.*' => 'in:' . implode(',', array_keys(self::AVAILABLE_CATEGORIES)),
        ]);

        $user                     = Auth::user();
        $user->favorite_categories = json_encode($validated['categories']);
        $user->save();

        return back()->with('success', 'Kategori favorit berhasil disimpan!');
    }
}
