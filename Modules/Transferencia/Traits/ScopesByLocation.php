<?php

namespace Modules\Transferencia\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesByLocation
{
    /**
     * Aplica el aislamiento por estación experimental a menos que el usuario
     * tenga el permiso de seguimiento a nivel nacional.
     */
    protected function applyLocationScope(Builder $query): Builder
    {
        $user = request()->user();

        if (!$user || $user->can('transferencia.seguimiento_general')) {
            return $query;
        }

        return $query->where('location_id', $user->location_id);
    }
}
