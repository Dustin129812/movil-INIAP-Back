<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Muestra una lista de todos los grupos.
     * GET /api/groups
     */
    public function index()
    {
        // Carga eficiente de relaciones y conteo
        $groups = Group::with(['rubro', 'location', 'members'])->withCount('members')->latest()->paginate(15);

        return GroupResource::collection($groups);
    }

    /**
     * Crea un nuevo grupo en la base de datos.
     * POST /api/groups
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups')->where(function ($query) use ($request) {
                return $query->where('rubro_id', $request->rubro_id)->where('location_id', $request->location_id);
            })],
            'rubro_id' => 'required|exists:rubros,id',
            'location_id' => 'required|exists:locations,id',
            'members' => 'present|array',
            'members.*' => 'required|exists:users,id',
        ], [
            'name.unique' => 'Ya existe un grupo con este nombre para el mismo rubro y ubicación.'
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'rubro_id' => $validated['rubro_id'],
            'location_id' => $validated['location_id'],
            'creator_id' => Auth::id(),
        ]);

        // Sincroniza los miembros iniciales
        if (!empty($validated['members'])) {
            $group->members()->sync($validated['members']);
        }

        return new GroupResource($group->load(['rubro', 'location', 'members']));
    }

    /**
     * Muestra un grupo específico con todos sus detalles.
     * GET /api/groups/{group}
     */
    public function show(Group $group)
    {
        // Carga todas las relaciones necesarias para la vista detallada
        $group->load(['rubro', 'location', 'creator', 'members']);

        return new GroupResource($group);
    }

    /**
     * Actualiza la información principal de un grupo.
     * PUT /api/groups/{group}
     */
    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups')->ignore($group->id)->where(function ($query) use ($request) {
                return $query->where('rubro_id', $request->rubro_id)->where('location_id', $request->location_id);
            })],
            'rubro_id' => 'required|exists:rubros,id',
            'location_id' => 'required|exists:locations,id',
        ]);

        $group->update($validated);

        return new GroupResource($group->load(['rubro', 'location', 'members']));
    }

    /**
     * Sincroniza (reemplaza) los miembros de un grupo.
     * PUT /api/groups/{group}/members
     */
    public function syncMembers(Request $request, Group $group)
    {
        $validated = $request->validate([
            'members' => 'present|array',
            'members.*' => 'required|exists:users,id',
        ]);

        $group->members()->sync($validated['members']);

        // Devolvemos el grupo con la lista de miembros actualizada
        return new GroupResource($group->load('members'));
    }

    /**
     * Elimina un grupo de la base de datos.
     * DELETE /api/groups/{group}
     */
    public function destroy(Group $group)
    {
        // Opcional: añadir una política de autorización para ver quién puede eliminar
        // $this->authorize('delete', $group);

        $group->delete();

        // Devolvemos una respuesta vacía con código 204 (No Content)
        return response()->noContent();
    }
}
