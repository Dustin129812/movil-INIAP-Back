<?php

namespace App\Http\Controllers;

use App\Models\Multidisciplinary_Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    // Crear un grupo con un líder y miembros
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'required|exists:users,id',
            'members' => 'required|array|min:2|max:6',
            'members.*' => 'exists:users,id',
            'location_id' => 'required|exists:locations,id',
            'rubro_id' => 'required|exists:rubros,id',
        ]);

        $group = Multidisciplinary_Group::create([
            'name' => $request->name,
            'leader_id' => $request->leader_id,
            'location_id' => $request->location_id,
            'rubro_id' => $request->rubro_id,
        ]);

        // Añadir miembros al grupo
        $group->members()->attach($request->members);

        return response()->json($group, 201);
    }

    // Mostrar un grupo con su líder y miembros
    public function show($id)
    {
        $group = Multidisciplinary_Group::with('leader', 'members')->findOrFail($id);

        return response()->json($group);
    }

    // Actualizar los miembros de un grupo
    public function update(Request $request, $id)
    {
        $request->validate([
            'members' => 'required|array|min:2|max:6',
            'members.*' => 'exists:users,id',
        ]);

        $group = Multidisciplinary_Group::findOrFail($id);

        // Actualizar los miembros del grupo
        $group->members()->sync($request->members);

        return response()->json($group);
    }

    // Eliminar un grupo
    public function destroy($id)
    {
        $group = Multidisciplinary_Group::findOrFail($id);
        $group->delete();

        return response()->json(['message' => 'Grupo eliminado con éxito']);
    }
}
