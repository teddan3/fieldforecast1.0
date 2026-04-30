<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'users' => User::query()
                ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = User::query()->create($this->validated($request));

        return response()->json(['user' => $this->payload($user)], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['user' => $this->payload($user)]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $this->validated($request, $user);

        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(['user' => $this->payload($user->fresh())]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $id = $user?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,editor,viewer'],
            'is_active' => ['boolean'],
        ]);
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ];
    }
}
