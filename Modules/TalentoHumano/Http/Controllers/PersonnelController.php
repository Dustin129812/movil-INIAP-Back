<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\TalentoHumano\Entities\AdministrativeUnit;
use Modules\TalentoHumano\Entities\Management;
use Modules\TalentoHumano\Entities\Process;

class PersonnelController extends Controller
{
    /**
     * Listar todo el personal con sus relaciones TH
     */
    public function index(Request $request)
    {
        $query = User::with(['process', 'administrativeUnit', 'management'])
            ->orderBy('name', 'asc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Paginación para no saturar la vista
        return response()->json($query->paginate(20));
    }

    /**
     * Crear nuevo empleado
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'dni' => 'required|string|unique:users,dni|max:20',
            'email' => 'required|email|unique:users,email',
            'th_process_id' => 'nullable|exists:th_processes,id',
            'th_administrative_unit_id' => 'nullable|exists:th_administrative_units,id',
            'th_management_id' => 'nullable|exists:th_managements,id',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'dni' => $request->dni,
                'email' => $request->email,
                'password' => Hash::make($request->dni), // Contraseña default = Cédula
                'th_process_id' => $request->th_process_id,
                'th_administrative_unit_id' => $request->th_administrative_unit_id,
                'th_management_id' => $request->th_management_id,
                'is_active' => true
            ]);

            $user->assignRole('User');

            DB::commit();
            return response()->json(['message' => 'Personal creado correctamente', 'data' => $user]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar datos del empleado
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'th_process_id' => 'nullable|exists:th_processes,id',
            'th_administrative_unit_id' => 'nullable|exists:th_administrative_units,id',
            'th_management_id' => 'nullable|exists:th_managements,id',
        ]);

        $user->update($request->only([
            'name', 'email', 'th_process_id', 'th_administrative_unit_id', 'th_management_id'
        ]));

        return response()->json(['message' => 'Ficha actualizada correctamente']);
    }

    /**
     * Alternar estado activo/inactivo (Soft Delete lógico)
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['message' => 'Estado del empleado actualizado']);
    }

    /**
     * Obtener catálogos para el formulario
     */
    public function getCatalogs()
    {
        return response()->json([
            'processes' => Process::orderBy('name')->get(),
            'units' => AdministrativeUnit::orderBy('name')->get(),
            'managements' => Management::orderBy('name')->get(),
        ]);
    }
}
