<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\Ethnic_Group;
use App\Models\Location;
use App\Models\Multidisciplinary_Group;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getUsers()
    {
        $users = User::all();
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
        $user->name= $request->input('name');
        $user->email= $request->input('email');
        $user->password= Hash::make ($request->input('dni'));
        $user->birth_date= $request->input('birth_date');
        $user->gender= $request->input('gender');
        $user->phone= $request->input('phone');

        $user->locations()->associate(Location::find($request->input('location')));
        $user->nationalities()->associate(Nationality::find($request->input('nationality')));
        $user->ethnic_groups()->associate(Ethnic_Group::find($request->input('ethnic')));
        $user->positions()->associate(Position::find($request->input('position')));
        $user->save();

        return (new UserResource($user))->additional([
            'msg'=>[
                'summary' => 'success',
                'detail' => 'El usuario a sido creado',
                'code' => '200'
            ]
        ])->response()->setStatusCode(200);
    }

    public function getLocations(){
        $locations = Location::get();
        return $locations;
    }

    public function getNationality(){
        $nationality = Nationality::get();
        return $nationality;
    }

    public function getEthnics(){
        $ethnic = Ethnic_Group::get();
        return $ethnic;
    }

    public function getPositions(){
        $position = Position::get();
        return $position;
    }
}
