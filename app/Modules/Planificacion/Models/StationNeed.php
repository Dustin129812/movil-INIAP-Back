<?php

namespace App\Modules\Planificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationNeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'fill_date',
        'expense_type_id',
        'description',
        'estimated_amount',
        'priority',
        'expected_impact',
        'impact_type',
        'problem_to_solve',
        'investment_risk',
        'administrative_time_months',
        'execution_time_months',
        'has_supporting_documents',
        'requires_technical_studies',
        'has_technical_studies',
    ];

    protected $casts = [
        'has_supporting_documents' => 'boolean',
        'requires_technical_studies' => 'boolean',
        'has_technical_studies' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
