<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration and token creation.
     */
    public function register(Request $request)
    {
        // 1. Validate incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 2. Create the new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            // Password hashing is handled by the 'password' => 'hashed' cast in the User model
            'password' => $request->password,
        ]);

        // 3. Create a Sanctum token for the newly registered user
        // We grant it the ability 'access:api'
        $token = $user->createToken('auth_token', ['access:api'])->plainTextToken;

        return response()->json([
            'message' => 'User successfully registered.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    /**
     * Handle user login and token creation.
     */
    public function login(Request $request)
    {
        // 1. Validate incoming request data
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Find the user by email
        $user = User::where('email', $request->email)->first();

        // 3. Check credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Throw a validation exception for better error handling in the frontend
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // 4. If credentials are valid, delete old tokens (optional, but good for security)
        $user->tokens()->delete();

        // 5. Create a new token
        $token = $user->createToken('auth_token', ['access:api'])->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Log the user out (Revoke the current access token).
     */
    public function logout(Request $request)
    {
        // Delete the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out.'
        ]);
    }
}
