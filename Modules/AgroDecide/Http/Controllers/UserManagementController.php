<?php

namespace Modules\AgroDecide\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserManagementController extends Controller
{
    /**
     * Listar usuarios (solo admin)
     */
    public function index(): JsonResponse
    {
        $users = User::select(
            'id', 'name', 'email', 'dni', 'phone',
            'location_id', 'created_at'
        )->with('location:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Crear un nuevo usuario (solo admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'dni' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'location_id' => 'nullable|exists:locations,id',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'ethnic_id' => 'nullable|exists:ethnic_groups,id',
            'position_id' => 'nullable|exists:positions,id',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|in:Masculino,Femenino,Otro',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'dni' => $data['dni'] ?? '0000000000',
            'phone' => $data['phone'] ?? '0000000000',
            'location_id' => $data['location_id'] ?? 5, // Default: PORTOVIEJO
            'nationality_id' => $data['nationality_id'] ?? 1, // Default: ECUATORIANA
            'ethnic_id' => $data['ethnic_id'] ?? 1, // Default: AFROECUATORIANO
            'position_id' => $data['position_id'] ?? 1, // Default: OFICINISTA 2
            'birth_date' => $data['birth_date'] ?? '1990-01-01',
            'gender' => $data['gender'] ?? 'Otro',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }

    /**
     * Obtener un usuario específico
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['location:id,name', 'position:id,name'])
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
            'dni' => 'nullable|string|max=20',
            'phone' => 'nullable|string|max=20',
            'location_id' => 'nullable|exists:locations,id',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'ethnic_id' => 'nullable|exists:ethnic_groups,id',
            'position_id' => 'nullable|exists:positions,id',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|in:Masculino,Femenino,Otro',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // No permitir eliminar oneself
        $currentUserId = (int) JWTAuth::parseToken()->getPayload()->get('sub');
        if ($currentUserId === $id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminarte a ti mismo'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }
}
