<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Mail\ResetPasswordMail;
use App\Mail\SetPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string', 'unique:users'],
            'role'  => ['required', Rule::enum(UserRole::class)],
        ]);

        $user = User::create([...$data, 'password' => Hash::make(Str::random(40))]);

        $token = Password::broker()->createToken($user);
        Mail::to($user)->send(new SetPasswordMail($user, $token));

        return response()->json(['user' => $user], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json(['user' => $user]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::enum(UserRole::class)],
        ]);

        $user->update($data);

        return response()->json(['user' => $user->fresh()]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(null, 204);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['user' => $user->fresh()]);
    }

    public function sendResetLink(User $user): JsonResponse
    {
        $this->authorize('sendResetLink', $user);

        $token = Password::broker()->createToken($user);
        Mail::to($user)->send(new ResetPasswordMail($user, $token));

        return response()->json(['message' => 'Email reset password berhasil dikirim.']);
    }
}
