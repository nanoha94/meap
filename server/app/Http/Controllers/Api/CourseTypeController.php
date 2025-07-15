<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/course-types",
     *     summary="コースタイプ一覧を取得",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/CourseTypeIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index()
    {
        //
    }
}
