<?php

namespace Modules\Investigacion\Entities;

use App\Models\Budget_Type;
use App\Models\Crops;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';
    protected $fillable = [
        'name',
        'budget',
        'rubro_id',
        'location_id',
        'budget_types_id',
        'crop_id',
        'funding_source_name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'product_user');
    }

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
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
                ->orWhereHas('activity.users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                })
                ->orWhere('name', 'Actividades Extra POA');
        });
    }

}
