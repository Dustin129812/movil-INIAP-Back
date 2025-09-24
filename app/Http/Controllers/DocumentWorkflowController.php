<?php

namespace App\Http\Controllers;

use App\Models\DocumentWorkflow;
use Illuminate\Http\Request;

class DocumentWorkflowController extends Controller
{
    // Método genérico para cambiar el estado
    private function updateState(DocumentWorkflow $workflow, $newState)
    {
        if (request()->user()->id !== $workflow->recipient_id && request()->user()->id !== $workflow->reassigned_to_id) {
            return response()->json(['message' => 'Acción no autorizada.'], 403);
        }
        $workflow->update(['state' => $newState]);
        return response()->json(['message' => 'Estado del documento actualizado.']);
    }

    public function archive(DocumentWorkflow $workflow)
    {
        return $this->updateState($workflow, 'archived');
    }

    public function trash(DocumentWorkflow $workflow)
    {
        return $this->updateState($workflow, 'trashed');
    }

    public function restore(DocumentWorkflow $workflow)
    {
        return $this->updateState($workflow, 'active');
    }
}
