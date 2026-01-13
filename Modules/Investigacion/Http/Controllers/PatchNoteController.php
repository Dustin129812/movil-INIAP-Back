<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Investigacion\Entities\PatchNote;

class PatchNoteController extends Controller
{
    // --- MÉTODOS PARA EL PANEL DE ADMINISTRACIÓN (CRUD) ---

    public function index()
    {
        // Devuelve todas las notas, ordenadas por la más reciente primero
        return PatchNote::latest('release_date')->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'release_date' => 'required|date',
            'is_published' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $patchNote = PatchNote::create($validator->validated());

        return response()->json($patchNote, 201);
    }

    public function update(Request $request, PatchNote $patchNote)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'release_date' => 'required|date',
            'is_published' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $patchNote->update($validator->validated());

        return response()->json($patchNote);
    }

    public function destroy(PatchNote $patchNote)
    {
        $patchNote->delete();
        return response()->json(null, 204); // Sin contenido, éxito
    }


    public function getLatest()
    {
        $latestPatchNote = PatchNote::where('is_published', true)
            ->latest('release_date')
            ->latest('id')
            ->first();

        if (!$latestPatchNote) {
            return response()->json(null, 404);
        }

        return response()->json($latestPatchNote);
    }
}
