<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvitationToken;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class InvitationTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $expiresAt = Carbon::now()->addHour(); // 現在時刻から1時間後
            // トークンをデータベースに保存
            $invitationToken = InvitationToken::createWithExpiration(auth()->id(), $expiresAt);
            return response()->json(['token' =>  $invitationToken, 'expires_at' => $expiresAt], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show(string $token): JsonResponse
    {
        $invitationToken = InvitationToken::all()->first(function ($record) use ($token) {
            return Hash::check($token, $record->token);
        });

        return response()->json([
            'token' => $invitationToken->token,
            'expires_at' => $invitationToken->expires_at,
            'inviter' => [
                'id' => $invitationToken->inviter->id,
                'custom_id' => $invitationToken->inviter->custom_id,
                'name' => $invitationToken->inviter->name
            ]
        ], 200);
    }
}
