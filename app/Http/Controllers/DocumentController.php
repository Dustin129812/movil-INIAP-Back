<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentWorkflow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /**
     * Obtiene los documentos en la bandeja de entrada del usuario.
     */
    public function inbox(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        $documents = Document::where('status', 'enviado')
            ->where(function ($query) use ($userId) {
                $query->whereHas('workflows', function ($subQuery) use ($userId) {
                    $subQuery->where('recipient_id', $userId);
                })
                    ->orWhereHas('workflows', function ($subQuery) use ($userId) {
                        $subQuery->where('reassigned_to_id', $userId);
                    });
            })
            ->with([
                'creator',
                'documentType',
                'workflows.recipient',
                'workflows.sender',
                'workflows.reassignedToUser',
            ])
            ->latest()
            ->get();

        return response()->json($documents);
    }

    /**
     * Obtiene los documentos enviados por el usuario.
     */
    public function sent(Request $request)
    {
        $documents = $request->user()->documents()
            ->where('status', 'enviado')
            ->with([
                'creator',
                'documentType',
                'workflows.recipient',
                'workflows.reassignedToUser'
            ])
            ->latest()
            ->get();

        return response()->json($documents);
    }

    /**
     * Obtiene los documentos en estado de borrador del usuario.
     */
    public function drafts(Request $request)
    {
        $documents = $request->user()->documents()
            ->where('status', 'borrador')
            ->with('creator')
            ->latest()
            ->get();

        return response()->json($documents);
    }

    /**
     * Muestra un documento específico con todos sus detalles.
     */
    public function show(Request $request, Document $document)
    {
        // Cargar todas las relaciones necesarias para la vista de detalle
        $document->load(['creator', 'attachments', 'workflows.sender', 'workflows.recipient', 'documentType']);

        $user = $request->user();
        $isRecipient = $document->workflows->contains('recipient_id', $user->id);

        // Autorización: solo el creador o un destinatario pueden ver el documento.
        if ($document->user_id !== $user->id && !$isRecipient) {
            return response()->json(['message' => 'No autorizado para ver este documento.'], 403);
        }

        return response()->json($document);
    }

    /**
     * Crea un nuevo documento en estado "borrador".
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'content' => 'nullable|string',
            'document_type_id' => 'required|integer|exists:document_types,id',
            'category' => 'required|string',
            'typification' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:documents,id',
            'on_behalf_of_user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $document = $request->user()->documents()->create([
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'document_type_id' => $request->input('document_type_id'),
            'category' => $request->input('category'),
            'typification' => $request->input('typification'),
            'reference_number' => $request->input('reference_number'),
            'parent_id' => $request->input('parent_id'),
            'on_behalf_of_user_id' => $request->input('on_behalf_of_user_id'),
            'status' => 'borrador',
        ]);

        return response()->json($document, 201);
    }

    /**
     * Adjunta uno o más archivos a un documento en borrador.
     */
    public function attach(Request $request, Document $document)
    {
        if ($request->user()->id !== $document->user_id || $document->status !== 'borrador') {
            return response()->json(['message' => 'Acción no autorizada.'], 403);
        }

        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'required|file|max:20480', // max:20MB por archivo
        ]);

        $attachments = [];
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('attachments', 'public');
            $attachments[] = $document->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_in_bytes' => $file->getSize(),
            ]);
        }

        return response()->json($attachments, 201);
    }

    /**
     * Envía un documento en borrador a sus destinatarios.
     */
    public function send(Request $request, Document $document)
    {
        $user = $request->user();

        $isCreator = $user->id === $document->user_id;
        $isDelegate = false;
        $originalWorkflow = null;

        if ($document->on_behalf_of_user_id) {
            $originalWorkflow = DocumentWorkflow::where('document_id', $document->parent_id)
                ->where('recipient_id', $document->on_behalf_of_user_id)
                ->where('reassigned_to_id', $user->id)
                ->first();
            if ($originalWorkflow) {
                $isDelegate = true;
            }
        }

        if (!$isCreator && !$isDelegate) {
            return response()->json(['message' => 'Acción no autorizada.'], 403);
        }

        if ($document->status !== 'borrador') {
            return response()->json(['message' => 'Este documento ya no es un borrador.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::transaction(function () use ($request, $document, $user, $isDelegate, $originalWorkflow) {
            $senderId = $document->on_behalf_of_user_id ?? $user->id;

            foreach ($request->recipients as $recipientId) {
                if ($recipientId != $senderId) {
                    $document->workflows()->create([
                        'sender_id' => $senderId,
                        'recipient_id' => $recipientId,
                        'status' => 'pending',
                    ]);
                }
            }
            $document->update(['status' => 'enviado']);

            if ($isDelegate && $originalWorkflow) {
                $originalWorkflow->update(['status' => 'answered']);
            }
        });

        return response()->json(['message' => 'Documento enviado con éxito.']);
    }

    /**
     * Marca un documento como leído por el destinatario.
     */
    public function markAsRead(Request $request, Document $document)
    {
        $user = $request->user();
        $workflow = $document->workflows()
            ->where(function ($query) use ($user) {
                $query->where('recipient_id', $user->id)
                    ->orWhere('reassigned_to_id', $user->id);
            })
            ->whereNull('read_at') // Y que aún no haya sido leído
            ->first();

        if ($workflow) {
            $workflow->update(['read_at' => now()]);
            return response()->json(['message' => 'Documento marcado como leído.']);
        }

        return response()->json(['message' => 'Acción no requerida o no autorizada.'], 200);
    }

    /**
     * Permite a un destinatario informar sobre un documento a otros usuarios.
     */
    public function inform(Request $request, Document $document)
    {
        $user = $request->user();

        $isRecipient = $document->workflows()->where('recipient_id', $user->id)->exists();

        if (!$isRecipient) {
            return response()->json(['message' => 'Acción no autorizada. Solo los destinatarios pueden informar este documento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::transaction(function () use ($request, $document, $user) {
            foreach ($request->recipients as $recipientId) {
                if ($recipientId != $user->id) {
                    $document->workflows()->create([
                        'sender_id' => $user->id,
                        'recipient_id' => $recipientId,
                        'status' => 'pending',
                        'action_type' => 'for_information',
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Documento informado con éxito.']);
    }

    public function reassign(Request $request, Document $document)
    {
        $user = $request->user();

        $workflow = $document->workflows()->where('recipient_id', $user->id)->first();

        if (!$workflow || $workflow->status === 'reassigned') {
            return response()->json(['message' => 'Acción no autorizada o ya reasignada.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reassignedToId = $request->input('user_id');

        $workflow->update([
            'status' => 'reassigned',
            'reassigned_to_id' => $reassignedToId,
        ]);

        return response()->json(['message' => 'Documento reasignado con éxito.']);
    }
}
