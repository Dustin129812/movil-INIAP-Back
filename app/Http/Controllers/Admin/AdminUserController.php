<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Muestra una lista paginada de usuarios para el panel de admin.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Lógica de búsqueda
        if ($request->has('search')) {
            $searchTerm = strtolower($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereRaw('LOWER(dni) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        // Paginamos los resultados
        $users = $query->paginate(15);

        return new UserCollection($users);
    }
}
