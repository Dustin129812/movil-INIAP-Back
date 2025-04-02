<?php

namespace App\Http\Controllers;

use App\Models\Ethnic_Group;
use App\Models\Location;
use App\Models\Nationality;
use App\Models\Performance_Indicator;
use App\Models\Position;
use App\Models\Rubro;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
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
    public function getRubros(){
        $rubro = Rubro::get();
        return $rubro;
    }

    public function getIndicators(){
        $indicators = Performance_Indicator::get();
        return $indicators;
    }
}
