<?php

namespace Modules\TrlImporter\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function getTecnologias(): JsonResponse
    {
        $tecnologias = DB::table('trl.tecnologias')->get();
        return response()->json(['success' => true, 'data' => $tecnologias]);
    }

    public function getMatriz(): JsonResponse
    {
        $matriz = DB::table('trl.matriz_trl')->get();
        return response()->json(['success' => true, 'data' => $matriz]);
    }
}
