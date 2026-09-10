<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }

    public function setPassword(SetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            fn ($user, $password) => $user->update(['password' => $password])
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Link tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        return response()->json(['message' => 'Password berhasil diatur.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json(['user' => $user->fresh()]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json(['user' => $user->fresh()]);
    }
}
