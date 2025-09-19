<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;

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
