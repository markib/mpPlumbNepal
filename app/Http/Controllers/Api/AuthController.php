<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:customer,admin,service_provider,plumber,shop_keeper',
            'phone' => 'required|string|max:20|unique:users,phone',
            'locale' => 'nullable|in:en,ne',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'phone' => $data['phone'],
            'locale' => $data['locale'] ?? 'en',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $this->formatUser($user), 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $this->formatUser($user), 'token' => $token]);
    }

    public function me(Request $request)
    {
        return response()->json($this->formatUser($request->user()));
    }

    protected function formatUser(User $user): array
    {
        $userData = $user->only(['id', 'name', 'email', 'phone', 'role', 'locale']);

        if ($user->role === 'plumber') {
            $profile = $user->plumberProfile;
            if ($profile) {
                $userData['plumber_profile_id'] = $profile->id;
                $coordinates = DB::getDriverName() === 'pgsql'
                    ? DB::table('plumber_profiles')
                        ->where('id', $profile->id)
                        ->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude')
                        ->first()
                    : (object) [
                        'latitude' => $profile->latitude,
                        'longitude' => $profile->longitude,
                    ];

                if ($coordinates && $coordinates->latitude !== null && $coordinates->longitude !== null) {
                    $userData['location'] = [
                        'latitude' => (float) $coordinates->latitude,
                        'longitude' => (float) $coordinates->longitude,
                        'description' => 'Plumber base location',
                    ];
                }
            }
        } else {
            $latestBooking = Booking::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestBooking) {
                $userData['location'] = [
                    'latitude' => $latestBooking->latitude,
                    'longitude' => $latestBooking->longitude,
                    'address' => trim(implode(', ', array_filter([
                        $latestBooking->landmark,
                        $latestBooking->tole_name,
                        $latestBooking->ward_number,
                    ]))),
                ];
            }
        }

        return $userData;
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
