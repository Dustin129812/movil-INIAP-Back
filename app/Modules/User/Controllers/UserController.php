<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function profile(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => Auth::guard('api')->user(),
        ]);
    }
}
