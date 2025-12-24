<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Attempt login and return redirect path based on role.
     *
     * @param array $credentials ['email' => string, 'password' => string]
     * @return array ['success' => bool, 'redirect' => string|null, 'message' => string|null]
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return [
                'success' => false,
                'redirect' => null,
                'message' => 'User tidak ditemukan.',
            ];
        }

        if (!$user->is_active) {
            return [
                'success' => false,
                'redirect' => null,
                'message' => 'Akun Anda tidak aktif. Hubungi admin.',
            ];
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return [
                'success' => false,
                'redirect' => null,
                'message' => 'Password salah.',
            ];
        }

        Auth::login($user, $credentials['remember'] ?? false);

        // Determine redirect based on role
        $redirect = $this->getRedirectPath($user);

        return [
            'success' => true,
            'redirect' => $redirect,
            'message' => 'Login berhasil.',
        ];
    }

    /**
     * Get redirect path based on user role.
     */
    public function getRedirectPath(User $user): string
    {
        return match ($user->role) {
            'admin' => '/admin',
            'employee' => '/pos',
            default => '/dashboard',
        };
    }

    /**
     * Logout user.
     */
    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
