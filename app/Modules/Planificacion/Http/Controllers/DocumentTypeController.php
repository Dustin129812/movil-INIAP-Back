<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Models\DocumentType;

class DocumentTypeController extends Controller
{
    /**
     * Devuelve una lista de los tipos de documentos activos.
     */
    public function index()
    {
        $types = DocumentType::where('is_active', true)->orderBy('name')->get();
        return response()->json($types);
    }
}
