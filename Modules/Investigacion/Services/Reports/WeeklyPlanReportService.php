<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Modules\Investigacion\Entities\WeekActivity;

class WeeklyPlanReportService
{
    public function generateReport(array $data)
    {
        Carbon::setLocale('es');

        $userId    = $data['user_id'];
        $startDate = Carbon::parse($data['start_date']);
        $endDate   = Carbon::parse($data['end_date']);

        $technician = User::with('location')->find($userId);
        if (!$technician) {
            throw new \Exception('Técnico no encontrado.'); // El controlador manejará la excepción
        }

        $ratedStatuses = ['approved', 'completed', 'partial', 'not completed', 'rated'];

        $weekActivities = WeekActivity::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', $ratedStatuses)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('logisticSupportUsers', function ($q) use ($userId) {
                        $q->where('users.id', $userId)
                            ->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                    });
            })
            ->with([
                'activity.product.rubro',
                'activity.users',
                'user',
                'materials',
                'performanceIndicators',
                'logisticSupportUsers'
            ])
            ->orderBy('date')
            ->get();

        $weekActivities->each(function ($weekActivity) use ($userId) {
            $isOwner = ($weekActivity->user_id == $userId);
            $weekActivity->setAttribute('is_owner', $isOwner);

            $this->formatActivityDescription($weekActivity);

            if (!$isOwner) {
                $ownerName = mb_strtoupper($weekActivity->user->name ?? 'Compañero');
                $weekActivity->setAttribute('description', "[ APOYANDO A: " . $ownerName . " ]\n" . $weekActivity->description);
            }
        });

        $hasSupport    = $weekActivities->contains(fn($a) => $a->logisticSupportUsers && $a->logisticSupportUsers->isNotEmpty());
        $hasIndicators = $weekActivities->contains(fn($a) => $a->performanceIndicators && $a->performanceIndicators->isNotEmpty());

        $widths = ['date' => 7, 'product' => 12, 'rubro' => 12, 'activity' => 15, 'description' => 16, 'support' => 10, 'indicator' => 10, 'observations' => 18];
        $hiddenMessages = [];

        if (!$hasSupport) {
            $widths['description'] += $widths['support'];
            $widths['support'] = 0;
            $hiddenMessages[] = 'Personal de Apoyo';
        }
        if (!$hasIndicators) {
            $widths['description'] += $widths['indicator'];
            $widths['indicator'] = 0;
            $hiddenMessages[] = 'Indicador Asociado';
        }

        $omittedColumnsText = !empty($hiddenMessages) ? 'Nota: Se han omitido las columnas: ' . implode(', ', $hiddenMessages) . ' por falta de datos, ampliando el espacio.' : null;

        $mainRubro = 'Varios Rubros';
        if ($weekActivities->isNotEmpty()) {
            $rubros = $weekActivities->map(fn($item) => $item->activity->product->rubro->name ?? null)->filter()->unique();
            if ($rubros->count() === 1) $mainRubro = $rubros->first();
            else if ($rubros->isEmpty()) $mainRubro = 'Sin Rubro Asociado';
        }

        $groupedActivities = $weekActivities->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $reportData = [
            'iniap_logo_path'     => public_path('storage/images/iniap_logo.png'),
            'ecuador_shield_path' => public_path('storage/images/ecuador_shield.jpg'),
            'technician'          => $technician,
            'technician_location' => $technician->location->name ?? 'Ubicación Desconocida',
            'program_rubro'       => $mainRubro,
            'presentation_date'   => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
            'week_range'          => 'Del ' . $startDate->translatedFormat('d \d\e F \d\e Y') . ' al ' . $endDate->translatedFormat('d \d\e F \d\e Y'),
            'weekActivities'      => $groupedActivities,
            'start_date_obj'      => $startDate,
            'visibility'          => ['support' => $hasSupport, 'indicators' => $hasIndicators],
            'widths'              => $widths,
            'omittedColumnsText'  => $omittedColumnsText
        ];

        $pdf = Pdf::loadView('reports.weekly_plan', $reportData)->setPaper('a4', 'landscape');

        return $pdf->download('Plan Semanal_' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf');
    }

    private function formatActivityDescription($item)
    {
        $productInitialCode = '';
        $activityInitialCode = '';
        $combinedCodePrefix = '';

        if (optional(optional($item->activity)->product)->name) {
            $productInitialCode = strtoupper(substr($item->activity->product->name, 0, 2));
        }
        if (optional($item->activity)->description) {
            $activityInitialCode = strtoupper(substr($item->activity->description, 0, 2));
        }

        if ($productInitialCode && $activityInitialCode) {
            $combinedCodePrefix = "{$productInitialCode}{$activityInitialCode}: ";
        } elseif ($productInitialCode) {
            $combinedCodePrefix = "{$productInitialCode}: ";
        } elseif ($activityInitialCode) {
            $combinedCodePrefix = "{$activityInitialCode}: ";
        }

        $item->formatted_description = $combinedCodePrefix . ($item->description ?? '');
    }
}
