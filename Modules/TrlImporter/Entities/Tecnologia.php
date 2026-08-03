<?php

namespace Modules\TrlImporter\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tecnologia extends Model
{
    use HasUuids;

    protected $table = 'trl.tecnologias';

    protected $guarded = [];
}
