<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile()
    {
        return response()->json([
            'success' => true,
            'user' => \Illuminate\Support\Facades\Auth::guard('api')->user()
        ]);
    }
}