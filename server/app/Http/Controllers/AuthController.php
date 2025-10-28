<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
// We no longer need Hash
// use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
// We don't strictly need this if we use the auth() helper
// use Illuminate\Support\Facades\Auth;
// We can import this to make the type-hint cleaner
use Tymon\JWTAuth\JWTGuard;
use Illuminate\Routing\Controller as BaseController; // <-- ADD THIS LINE

class AuthController extends BaseController // <-- CHANGE THIS LINE
{
    /**
     * Create a new AuthController instance.
     * We apply the 'auth:api' middleware to all methods
     * except for 'login' and 'register'.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

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

        // 3. Log in the new user and get a JWT
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        // 4. Return the response with the token
        return response()->json([
            'message' => 'User successfully registered.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            // 'expires_in' is helpful for the frontend
            'expires_in' => $guard->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * Handle user login and token creation.
     */
    public function login(Request $request)
    {
        // 1. Validate incoming request data
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Attempt to authenticate using the 'api' guard and get a token
        if (! $token = auth('api')->attempt($credentials)) {
            // 3. If authentication fails, throw an exception
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // 4. If credentials are valid, return the formatted token response
        return $this->respondWithToken($token);
    }

    /**
     * Log the user out (Revoke the current access token).
     */
    public function logout(Request $request)
    {
        // Invalidate the token (adds it to a blacklist)
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return response()->json([
            'message' => 'Successfully logged out.'
        ]);
    }

    /**
     * Get the authenticated User's profile.
     */
    public function me()
    {
        // Returns the currently authenticated user
        return response()->json(auth('api')->user());
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        // Refresh the token and return the new one
        return $this->respondWithToken($guard->refresh());
    }

    /**
     * Helper function to format the token response.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $guard->user(), // Use $guard here for consistency
            'expires_in' => $guard->factory()->getTTL() * 60
        ]);
    }
}


