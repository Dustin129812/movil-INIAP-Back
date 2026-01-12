<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Http\Resources\GroupResource;
use App\Modules\Planificacion\Models\Group;
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
        $groups = Group::with(['rubro', 'location', 'members', 'responsible', 'creator'])->withCount('members')->latest()->paginate(15);

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
            'responsible_id' => 'required|exists:users,id',
        ], [
            'name.unique' => 'Ya existe un grupo con este nombre para el mismo rubro y ubicación.',
            'responsible_id.required' => 'Debes seleccionar un responsable para el grupo.',
        ]);

        $members = collect($validated['members']);
        $responsibleId = $validated['responsible_id'];

        if (!$members->contains($responsibleId)) {
            $members->push($responsibleId);
        }

        $group = Group::create([
            'name' => $validated['name'],
            'rubro_id' => $validated['rubro_id'],
            'location_id' => $validated['location_id'],
            'creator_id' => Auth::id(),
            'responsible_id' => $responsibleId,
        ]);

        $group->members()->sync($members->unique()->all());
        return new GroupResource($group->load(['rubro', 'location', 'members', 'creator', 'responsible']));
    }

    public function changeResponsible(Request $request, Group $group)
    {
        $validated = $request->validate([
            'responsible_id' => [
                'required',
                'exists:users,id',
                Rule::exists('group_user', 'user_id')->where('group_id', $group->id),
            ]
        ], [
            'responsible_id.exists' => 'El usuario seleccionado no es un miembro válido de este grupo.'
        ]);

        $group->update([
            'responsible_id' => $validated['responsible_id']
        ]);

        return new GroupResource($group->load('responsible', 'members'));
    }

    /**
     * Muestra un grupo específico con todos sus detalles.
     * GET /api/groups/{group}
     */
    public function show(Group $group)
    {
        $group->load(['rubro', 'location', 'creator', 'members', 'responsible']);

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

        return new GroupResource($group->load('members'));
    }

    /**
     * Elimina un grupo de la base de datos.
     * DELETE /api/groups/{group}
     */
    public function destroy(Group $group)
    {
        $group->delete();

        return response()->noContent();
    }
}
