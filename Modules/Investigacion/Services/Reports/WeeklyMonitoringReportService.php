<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Modules\Investigacion\Entities\NoveltyActivity;
use Modules\Investigacion\Entities\WeekActivity;

class WeeklyMonitoringReportService
{
    public function generateReport(array $validatedData)
    {
        Carbon::setLocale('es');

        $userId = $validatedData['user_id'];
        $startDate = Carbon::parse($validatedData['start_date']);
        $endDate = Carbon::parse($validatedData['end_date']);

        $technician = User::with('location')->findOrFail($userId);
        $ratedStatuses = ['completed', 'partial', 'rated', 'not completed'];

        $plannedActivities = WeekActivity::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', $ratedStatuses)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('logisticSupportUsers', function ($q) use ($userId) {
                        $q->where('users.id', $userId)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                    });
            })
            ->with(['activity.product.rubro', 'activity.users', 'user', 'materials', 'performanceIndicators', 'logisticSupportUsers'])
            ->get()
            ->each(function ($item) use ($userId) {
                $item->is_novelty = false;
                $item->is_owner = ($item->user_id == $userId);
                $this->formatActivityDescription($item);

                if (!$item->is_owner) {
                    $ownerName = mb_strtoupper($item->user->name ?? 'Compañero');
                    $item->formatted_description = "【 APOYANDO A: " . $ownerName . " 】\n" . $item->formatted_description;
                }
            });

        $noveltyActivities = NoveltyActivity::whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('logisticSupport', function ($q) use ($userId) { $q->where('users.id', $userId); });
            })
            ->with(['activity.product.rubro', 'user', 'materials', 'indicators', 'logisticSupport'])
            ->get()
            ->each(function ($item) use ($userId) {
                $item->is_novelty = true;
                $item->is_owner = ($item->user_id == $userId);
                $this->formatActivityDescription($item);

                if (!$item->is_owner) {
                    $ownerName = mb_strtoupper($item->user->name ?? 'Compañero');
                    $item->formatted_description = "【 APOYANDO A: " . $ownerName . " 】\n" . $item->formatted_description;
                }
            });

        $allActivities = $plannedActivities->concat($noveltyActivities)->sortBy('date')->values();

        $hasMaterials = $allActivities->contains(fn($a) => $a->materials && $a->materials->isNotEmpty());
        $hasIndicators = $allActivities->contains(fn($a) => ($a->is_novelty ? $a->indicators : $a->performanceIndicators)->isNotEmpty());
        $hasLogistics = $allActivities->contains(fn($a) => ($a->is_novelty ? $a->logisticSupport : $a->logisticSupportUsers)->isNotEmpty());

        $widths = ['date' => 7, 'activity' => 33, 'verification' => 15, 'materials' => 15, 'logistics' => 10, 'status' => 8, 'observations' => 12];
        $hiddenMessages = [];

        if (!$hasMaterials) { $widths['activity'] += $widths['materials']; $widths['materials'] = 0; $hiddenMessages[] = 'Materiales'; }
        if (!$hasIndicators) { $widths['activity'] += $widths['verification']; $widths['verification'] = 0; $hiddenMessages[] = 'Verificación'; }
        if (!$hasLogistics) { $widths['activity'] += $widths['logistics']; $widths['logistics'] = 0; $hiddenMessages[] = 'Apoyo Logístico'; }

        $omittedColumnsText = !empty($hiddenMessages) ? 'Nota: Se han omitido: ' . implode(', ', $hiddenMessages) . '.' : null;

        $totalPlanned = $plannedActivities->count();
        $summary = [
            'completed' => $plannedActivities->where('percentage', 100)->count(),
            'partial' => $plannedActivities->where('percentage', '>', 0)->where('percentage', '<', 100)->count(),
            'not_done' => $plannedActivities->where('percentage', 0)->count(),
            'overall_compliance' => ($totalPlanned > 0) ? ($plannedActivities->sum('percentage') / $totalPlanned) : 0,
            'total_novelties' => $noveltyActivities->count(),
        ];

        $mainRubro = 'Varios Rubros';
        if ($plannedActivities->isNotEmpty()) {
            $rubros = $plannedActivities->map(fn($item) => $item->activity->product->rubro->name ?? null)->filter()->unique();
            if ($rubros->count() === 1) $mainRubro = $rubros->first();
            elseif ($rubros->isEmpty()) $mainRubro = 'Sin Rubro Asociado';
        }

        $reportData = [
            'iniap_logo_path' => public_path('storage/images/iniap_logo.png'),
            'ecuador_shield_path' => public_path('storage/images/ecuador_shield.jpg'),
            'technician' => $technician, 'startDate' => $startDate, 'endDate' => $endDate,
            'summary' => $summary, 'weekActivities' => $allActivities, 'program_rubro' => $mainRubro,
            'visibility' => ['materials' => $hasMaterials, 'indicators' => $hasIndicators, 'logistics' => $hasLogistics],
            'widths' => $widths, 'omittedColumnsText' => $omittedColumnsText
        ];

        return Pdf::loadView('reports.weekly_monitoring_report', $reportData)
            ->setPaper('a4', 'landscape')
            ->download('Informe_Monitoreo_' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf');
    }

    private function formatActivityDescription($item)
    {
        $productInitialCode = ''; $activityInitialCode = ''; $combinedCodePrefix = '';
        if (optional(optional($item->activity)->product)->name) $productInitialCode = strtoupper(substr($item->activity->product->name, 0, 2));
        if (optional($item->activity)->description) $activityInitialCode = strtoupper(substr($item->activity->description, 0, 2));

        if ($productInitialCode && $activityInitialCode) $combinedCodePrefix = "{$productInitialCode}{$activityInitialCode}: ";
        elseif ($productInitialCode) $combinedCodePrefix = "{$productInitialCode}: ";
        elseif ($activityInitialCode) $combinedCodePrefix = "{$activityInitialCode}: ";

        $item->formatted_description = $combinedCodePrefix . ($item->description ?? '');
    }
}
