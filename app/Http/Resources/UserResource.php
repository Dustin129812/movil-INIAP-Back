<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request){
        return[
            'id' => $this -> id,
            'dni' => $this -> dni,
            'name' => $this -> name,
            'email' => $this -> email,
            'password' => $this -> password,
            'birth_date' => $this -> birth_date,
            'gender' => $this -> gender,
            'phone' => $this -> phone,
            'location' => $this-> location,
            'roles' => $this->getRoleNames(),
        ];
    }
}
