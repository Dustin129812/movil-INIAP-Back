<?php

namespace App\Http\Controllers;

use App\Events\RoleUpdated;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        $query = User::with('roles');

        // Verifica si se proporcionó un término de búsqueda
        if ($request->has('search')) {
            $searchTerm = strtolower($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereRaw('LOWER(dni) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        $users = $query->get();
        $totalUsers = $users->count();

        return (new UserCollection($users))->additional([
            'msg' => [
                'summary' => 'success',
                'detail' => 'Usuarios devueltos correctamente',
                'code' => 200
            ],
            'totalUsers' => $totalUsers,
        ])->response()->setStatusCode(200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni'            => 'required|string|unique:users,dni',
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'birth_date'     => 'required|date_format:Y-m-d',
            'gender'         => 'required|string|max:50',
            'phone'          => 'nullable|string|max:50',
            'location_id'    => 'required|integer|exists:locations,id',
            'nationality_id' => 'required|integer|exists:nationalities,id',
            'ethnic_id'      => 'required|integer|exists:ethnic_groups,id',
            'position_id'    => 'required|integer|exists:areas,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error de validación',
                    'detail' => 'Los datos proporcionados no son válidos.',
                    'code' => '422'
                ],
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();
            $validatedData['password'] = Hash::make($validatedData['dni']);
            $user = User::create($validatedData);
            $user->assignRole('user');

            return (new UserResource($user))->additional([
                'msg' => [
                    'summary' => 'Usuario Creado',
                    'detail' => 'El usuario ha sido creado exitosamente.',
                    'code' => '201'
                ]
            ])->response()->setStatusCode(Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error Interno del Servidor',
                    'detail' => 'Ocurrió un error inesperado al procesar la solicitud.',
                    'code' => '500'
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'confirmed'],
        ]);

        $user = auth()->user(); // usuario autenticado

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.'
            ], 422); // Unprocessable Entity
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente.'
        ]);
    }

    public function updateRoles(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $roles = $request->input('roles', []);
        $user->syncRoles($roles);
        broadcast(new RoleUpdated($user, $roles)); // Emitiendo el nuevo evento
        return response()->json([
            'msg' => [
                'summary' => 'Roles actualizados',
                'detail' => 'Los roles fueron actualizados correctamente',
                'code' => 200,
            ],
        ]);
    }

    public function getProfile()
    {
        $user = auth()->user()->load('location'); // <- carga relación 'location'

        return response()->json([
            'id' => $user->id,
            'dni' => $user->dni,
            'name' => $user->name,
            'email' => $user->email,
            'location' => $user->location->name ?? null,
        ]);
    }
}
