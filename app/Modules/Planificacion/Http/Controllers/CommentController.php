<?php
namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Lista los comentarios de un documento.
     */
    public function index(Document $document)
    {
        // Carga los comentarios con la información del usuario que lo creó.
        $comments = $document->comments()->with('user')->latest()->get();
        return response()->json($comments);
    }

    /**
     * Almacena un nuevo comentario en un documento.
     */
    public function store(Request $request, Document $document)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Crea el comentario asociado al usuario autenticado y al documento.
        $comment = $document->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
        ]);

        // Carga la relación con el usuario para devolverlo completo al frontend.
        $comment->load('user');

        return response()->json($comment, 201);
    }
}
