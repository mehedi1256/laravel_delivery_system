<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/register",
        operationId: "registerUser",
        tags: ["Authentication"],
        summary: "Register a new user",
        description: "Register a new user and return a Sanctum token."
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "email", "password", "password_confirmation"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "John Doe"),
                new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "password"),
                new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Successful registration",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "token", type: "string"),
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "email", type: "string")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Validation error")]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
    #[OA\Post(
        path: "/api/login",
        operationId: "loginUser",
        tags: ["Authentication"],
        summary: "Log in a user",
        description: "Authenticate a user and return a Sanctum token."
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "password")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Successful login",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "token", type: "string"),
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "email", type: "string")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Invalid credentials")]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    #[OA\Get(
        path: "/api/me",
        operationId: "getMe",
        tags: ["Authentication"],
        summary: "Get authenticated user details",
        description: "Returns the currently authenticated user based on the Sanctum token.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Response(response: 200, description: "Successful operation")]
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    #[OA\Post(
        path: "/api/refresh",
        operationId: "refreshToken",
        tags: ["Authentication"],
        summary: "Refresh API token",
        description: "Revokes the current Sanctum token and issues a new one.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Response(
        response: 200, 
        description: "Successful operation",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "token", type: "string")
            ]
        )
    )]
    public function refresh(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        // Issue a new token
        $token = $request->user()->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token
        ]);
    }

    #[OA\Post(
        path: "/api/logout",
        operationId: "logoutUser",
        tags: ["Authentication"],
        summary: "Log out user",
        description: "Revokes the current Sanctum token, logging the user out.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Response(
        response: 200, 
        description: "Successfully logged out"
    )]
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
