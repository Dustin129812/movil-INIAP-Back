<?php

namespace App\Http\Controllers;

use App\Events\RoleUpdated;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\Ethnic_Group;
use App\Models\Location;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    public function addUser(Request $request)
    {
        $user = new User();
        $user->dni = $request->input('dni');
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('dni'));
        $user->birth_date = $request->input('birth_date');
        $user->gender = $request->input('gender');
        $user->phone = $request->input('phone');

        $user->locations()->associate(Location::find($request->input('location')));
        $user->nationalities()->associate(Nationality::find($request->input('nationality')));
        $user->ethnic_groups()->associate(Ethnic_Group::find($request->input('ethnic')));
        $user->positions()->associate(Position::find($request->input('position')));

        $user->save();

        $user->assignRole('user');

        return (new UserResource($user))->additional([
            'msg' => [
                'summary' => 'success',
                'detail' => 'El usuario a sido creado',
                'code' => '200'
            ]
        ])->response()->setStatusCode(200);
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
            'location' => $user->location->name ?? null, // <- retornamos el nombre de la ubicación
        ]);
    }
}
