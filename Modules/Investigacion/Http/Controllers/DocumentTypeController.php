<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\Investigacion\Entities\DocumentType;

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
