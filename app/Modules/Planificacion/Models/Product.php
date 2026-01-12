<?php

namespace App\Modules\Planificacion\Models;

use App\Models\Budget_Type;
use App\Models\Crops;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $fillable = [
        'name',
        'budget',
        'user_id',
        'rubro_id',
        'location_id',
        'budget_types_id',
        'crop_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rubro()
    {
        return $this->belongsTo(Rubro::class);
    }

    public function activity()
    {
        return $this->hasMany(Activity::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function weekPlanner()
    {
        return $this->hasMany(WeekPlanner::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function budget_type()
    {
        return $this->belongsTo(Budget_Type::class,'budget_types_id');
    }

    public function crop()
    {
        return $this->belongsTo(Crops::class,'crop_id');
    }

    public function scopeWhereUserRelated($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('activity.users', function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                })
            ->orWhere('name', 'Actividades Extra POA');
        });
    }

}
