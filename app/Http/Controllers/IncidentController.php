<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\User;
use App\Notifications\NewIncidentReported;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IncidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtiene todas las incidencias de la base de datos
        $incidents = Incident::latest()->get();
        return response()->json($incidents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Valida los datos de entrada
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:Baja,Media,Alta,Crítica',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Crea una nueva incidencia
        $incident = Incident::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'Abierta', // Estado por defecto
        ]);

        $admins = User::role('administrador')->get();
        if ($admins->isNotEmpty()) {
            foreach ($admins as $admin) {
            }
        }

        return response()->json([
            'message' => 'Incidencia reportada con éxito.',
            'incident' => $incident
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Incident $incident)
    {
        // Muestra una incidencia específica
        return response()->json($incident);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Incident $incident)
    {
        // Valida los datos de entrada
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:Baja,Media,Alta,Crítica',
            'status' => 'sometimes|required|in:Abierta,En Proceso,Resuelta,Cerrada',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Actualiza la incidencia
        $incident->update($request->all());

        return response()->json([
            'message' => 'Incidencia actualizada con éxito.',
            'incident' => $incident
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Incident $incident)
    {
        // Elimina la incidencia
        $incident->delete();

        return response()->json([
            'message' => 'Incidencia eliminada con éxito.'
        ], 200);
    }
}
