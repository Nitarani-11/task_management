<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RecomputeTaskEligibilityJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in(['admin', 'manager', 'user'])],
            'department' => ['sometimes', 'nullable', 'string', Rule::in(['Finance', 'HR', 'IT', 'Operation'])],
            'years_of_experience' => ['sometimes', 'integer', 'min:0'],
            'location' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'user',
            'department' => $validated['department'] ?? null,
            'years_of_experience' => $validated['years_of_experience'] ?? 0,
            'location' => $validated['location'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Dispatches asynchronous job to recompute task eligibility in case new user matches unassigned tasks
        RecomputeTaskEligibilityJob::dispatch(user: $user);

        return response()->json([
            'message' => 'User registered successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    /**
     * Authenticate user & generate Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Logout & revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update user profile attributes & trigger async recomputation (Story 3).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'department' => ['sometimes', 'nullable', 'string', Rule::in(['Finance', 'HR', 'IT', 'Operation'])],
            'years_of_experience' => ['sometimes', 'integer', 'min:0'],
            'location' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        // Async eligibility recomputation when user attributes change
        RecomputeTaskEligibilityJob::dispatch(user: $user);

        return response()->json([
            'message' => 'Profile updated successfully. Asynchronous task eligibility recomputation triggered.',
            'user' => $user->fresh(),
        ]);
    }
}
