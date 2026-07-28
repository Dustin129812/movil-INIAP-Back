<?php

namespace Modules\DireccionInvestigaciones\Entities\Protocolos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Crops;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\ResearchLine;
use Modules\Investigacion\Entities\Canton;

class IdiProtocol extends Model
{
    protected $table = 'investigaciones.idi_protocols';
    protected $guarded = ['id'];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'budget_total'    => 'decimal:2',
        'external_amount' => 'decimal:2',
        'trl_current'     => 'integer',
        'trl_target'      => 'integer',
    ];

    /*
     * ─── RELACIONES BELONGS TO ────────────────────────────────────────────
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'station_id');
    }

    public function researchLine(): BelongsTo
    {
        return $this->belongsTo(ResearchLine::class, 'research_line_id');
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crops::class, 'crop_id');
    }

    /*
     * ─── RELACIONES BELONGS TO MANY (Regla estricta) ──────────────────────
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'investigaciones.user_protocol',
            'idi_protocol_id',
            'user_id'
        );
    }

    public function influenceCantons(): BelongsToMany
    {
        return $this->belongsToMany(
            Canton::class,
            'investigaciones.canton_protocol',
            'idi_protocol_id',
            'canton_id'
        );
    }

    /*
     * ─── RELACIONES HAS MANY ──────────────────────────────────────────────
     */
    public function annexes(): HasMany
    {
        return $this->hasMany(ProtocolAnnex::class, 'protocol_id');
    }
}
