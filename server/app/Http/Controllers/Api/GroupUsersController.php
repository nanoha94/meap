<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupUsersController extends Controller
{


    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        // 同じグループに属するユーザーデータを取得
        $users = GroupUser::where('group_id', $groupId)
            ->with('user:id,custom_id,name') 
            ->get()
            ->pluck('user'); 

        return response()->json($users);
    }
}
