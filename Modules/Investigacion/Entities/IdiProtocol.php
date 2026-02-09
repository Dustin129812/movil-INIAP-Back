<?php

namespace Modules\Investigacion\Entities;

use App\Models\Crops;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdiProtocol extends Model
{
    use SoftDeletes;

    protected $table = 'idi_protocols';

    protected $fillable = [
        'project_name',
        'activity_title',
        'station_id',
        'research_line_id',
        'crop_id',
        'trl_current',
        'trl_justification',
        'trl_supports',
        'trl_target',
        'responsible_id',
        'start_date',
        'end_date',
        'external_collaborators',
        'funding_source',
        'donor_name',
        'iniap_role',
        'budget_total',
        'external_amount',
        'external_percent',
        'iniap_percent',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_total' => 'decimal:2',
        'external_amount' => 'decimal:2',
    ];

    /* -------------------------------------------------------------------------- */
    /* RELACIONES                                 */
    /* -------------------------------------------------------------------------- */

    public function station()
    {
        return $this->belongsTo(Location::class, 'station_id');
    }

    // 2. Clasificación Técnica
    public function researchLine()
    {
        return $this->belongsTo(ResearchLine::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crops::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'user_protocol', 'idi_protocol_id', 'user_id')
            ->withTimestamps();
    }

    public function influenceCantons()
    {
        return $this->belongsToMany(Canton::class, 'canton_protocol', 'idi_protocol_id', 'canton_id')
            ->withTimestamps();
    }
}
