<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Investigacion\Entities\WeekActivity;
use ZipArchive;

class WeeklyPlanReportService
{
    /**
     * Genera la descarga individual de un Plan Semanal
     */
    public function generateReport(array $data)
    {
        $reportData = $this->prepareReportData($data['user_id'], $data['start_date'], $data['end_date']);

        $pdf = Pdf::loadView('reports.weekly_plan', $reportData)->setPaper('a4', 'landscape');

        $fileName = 'Plan Semanal_' . str_replace(' ', '_', $reportData['technician']->name) . '_' . $reportData['start_date_obj']->format('Ymd') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Genera un ZIP con los Planes Semanales de todos los técnicos de la estación
     */
    public function generateMassivePlanZip(User $admin, array $data): string
    {
        $users = User::where('location_id', $admin->location_id)->get();
        $hasData = false;

        $zipFileName = 'planes_masivos_estacion_' . $admin->location_id . '_' . now()->format('Ymd_His') . '.zip';
        $disk = Storage::disk('verificables_externos');

        if (!$disk->exists('temp_zips')) {
            $disk->makeDirectory('temp_zips');
        }

        $zipPath = $disk->path('temp_zips/' . $zipFileName);
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($users as $user) {
                $reportData = $this->prepareReportData($user->id, $data['start_date'], $data['end_date']);

                // Solución Puntos 1 y 2: Solo procesar si el usuario tiene actividades
                if ($reportData['weekActivities']->isNotEmpty()) {
                    $hasData = true;

                    // Solución Punto 3: Generar en RAM e inyectar al ZIP
                    $pdfContent = Pdf::loadView('reports.weekly_plan', $reportData)->setPaper('a4', 'landscape')->output();

                    $safeUserName = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $user->name);
                    $fileName = trim($safeUserName) . '/Plan_Semanal_' . $reportData['start_date_obj']->format('Ymd') . '.pdf';

                    $zip->addFromString($fileName, $pdfContent);
                }
            }
            $zip->close();
        } else {
            throw new \Exception("No se pudo inicializar el compilador ZIP masivo.");
        }

        // Limpiar el ZIP defectuoso si nadie tuvo planificaciones
        if (!$hasData) {
            if ($disk->exists('temp_zips/' . $zipFileName)) {
                $disk->delete('temp_zips/' . $zipFileName);
            }
            throw new \Exception("Ningún usuario en la estación tiene planificaciones en el rango seleccionado.");
        }

        return $zipFileName;
    }

    /**
     * Centraliza la recolección y formateo de datos para la vista Blade
     */
    private function prepareReportData(int $userId, string $startDate, string $endDate): array
    {
        Carbon::setLocale('es');

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $technician = User::with('location')->find($userId);
        if (!$technician) {
            throw new \Exception('Técnico no encontrado.');
        }

        $ratedStatuses = ['approved', 'completed', 'partial', 'not completed', 'rated'];

        $weekActivities = WeekActivity::whereBetween('date', [$start, $end])
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

        return [
            'iniap_logo_path'     => public_path('storage/images/iniap_logo.png'),
            'ecuador_shield_path' => public_path('storage/images/ecuador_shield.jpg'),
            'technician'          => $technician,
            'technician_location' => $technician->location->name ?? 'Ubicación Desconocida',
            'program_rubro'       => $mainRubro,
            'presentation_date'   => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
            'week_range'          => 'Del ' . $start->translatedFormat('d \d\e F \d\e Y') . ' al ' . $end->translatedFormat('d \d\e F \d\e Y'),
            'weekActivities'      => $groupedActivities,
            'start_date_obj'      => $start,
            'visibility'          => ['support' => $hasSupport, 'indicators' => $hasIndicators],
            'widths'              => $widths,
            'omittedColumnsText'  => $omittedColumnsText
        ];
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
