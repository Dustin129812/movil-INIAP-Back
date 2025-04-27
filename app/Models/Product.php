<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'name',
        'budget',
        'user_id',
        'rubro_id',
        'location_id'
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

    public function weekPlanner()
    {
        return $this->hasMany(WeekPlanner::class);
    }

    // app/Models/Product.php

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeWhereUserRelated($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->orWhereHas('activity', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }

}
