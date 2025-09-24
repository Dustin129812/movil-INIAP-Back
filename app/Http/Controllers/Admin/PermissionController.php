<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name|max:100',
        ]);

        return Permission::create(['name' => $validated['name'], 'guard_name' => 'api']);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions')->ignore($permission->id),
            ],
        ]);

        $permission->update(['name' => $validated['name']]);
        return $permission;
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar. El permiso está asignado a uno o más roles.'
            ], 409);
        }

        $permission->delete();

        return response()->json(['message' => 'Permiso eliminado con éxito.']);
    }
}
