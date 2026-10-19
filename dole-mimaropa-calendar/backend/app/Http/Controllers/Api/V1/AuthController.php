<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $user = User::where('email', $data['email'])->first();

        // Same generic message for unknown user, wrong password and inactive account.
        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => ['code' => 'invalid_credentials', 'message' => 'Invalid credentials.']], 422);
        }

        return response()->json(['data' => [
            'token' => $user->createToken($data['device_name'], ['*'], now()->addDays(14))->plainTextToken,
            'user' => $this->profile($user),
        ]]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->profile($request->user())]);
    }

    private function profile(User $u): array
    {
        return [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            'primary_office_id' => $u->primary_office_id,
            'permissions' => $u->permissionSlugs(),
            'office_ids' => $u->memberships()->pluck('office_id'),
        ];
    }
}
