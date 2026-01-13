<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::create(['name' => $request->name, 'guard_name' => 'api']);

        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $role->id . '|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->update(['name' => $request->name]);

        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return response()->json(['error' => 'No se puede eliminar el rol porque está asignado a usuarios.'], 409); // 409 Conflict
        }

        if (in_array($role->name, ['admin', 'user'])) {
            return response()->json(['error' => 'Este rol principal no puede ser eliminado.'], 403);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado con éxito.']);
    }

    public function permissions(Role $role)
    {
        return $role->permissions;
    }

    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array'
        ]);

        $permissions = Permission::whereIn('id', $request->permissions)->get();
        $role->syncPermissions($permissions);

        return response()->json(['message' => 'Permisos actualizados con éxito.']);
    }
}
