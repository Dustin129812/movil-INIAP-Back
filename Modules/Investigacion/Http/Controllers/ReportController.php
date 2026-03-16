<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\NoveltyActivity;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Entities\Survey;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeeklyPulse;
use Modules\Investigacion\Http\Controllers\Traits\CalculatesProgress;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ReportController extends Controller
{
    use CalculatesProgress;

    // ===================================================================
    // === MÉTODO AUXILIAR PARA FORMATEAR NOMBRES EN LOS REPORTES PDF ===
    // ===================================================================
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

    public function generateWeeklyPlanReport(Request $request)
    {
        Carbon::setLocale('es');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $iniap_logo_path = public_path('storage/images/iniap_logo.png');
        $ecuador_shield_path = public_path('storage/images/ecuador_shield.jpg');

        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $technician = User::with('location')->find($userId);
        if (!$technician) {
            return response()->json(['error' => 'Técnico no encontrado.'], 404);
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
                'user', // Aseguramos traer al dueño
                'materials',
                'performanceIndicators',
                'logisticSupportUsers'
            ])
            ->orderBy('date')
            ->get();

        $weekActivities->each(function ($weekActivity) use ($userId) {
            $isOwner = ($weekActivity->user_id == $userId);

            // Usamos setAttribute para asegurar que isset() en Blade funcione
            $weekActivity->setAttribute('is_owner', $isOwner);
            $this->formatActivityDescription($weekActivity);

            // INYECCIÓN VISUAL PARA EL PDF (Sin caracteres que rompan DOMPDF)
            if (!$isOwner) {
                $ownerName = mb_strtoupper($weekActivity->user->name ?? 'Compañero');
                $weekActivity->setAttribute('description', "[ APOYANDO A: " . $ownerName . " ]\n" . $weekActivity->description);
            }
        });

        $totalActivities = $weekActivities->count();
        $hasSupport = $weekActivities->contains(fn($a) => $a->logisticSupportUsers && $a->logisticSupportUsers->isNotEmpty());
        $hasIndicators = $weekActivities->contains(fn($a) => $a->performanceIndicators && $a->performanceIndicators->isNotEmpty());

        $widths = ['date' => 7, 'product' => 12, 'rubro' => 12, 'activity' => 15, 'description' => 16, 'support' => 10, 'indicator' => 10, 'observations' => 18];
        $hiddenMessages = [];

        if (!$hasSupport) { $widths['description'] += $widths['support']; $widths['support'] = 0; $hiddenMessages[] = 'Personal de Apoyo'; }
        if (!$hasIndicators) { $widths['description'] += $widths['indicator']; $widths['indicator'] = 0; $hiddenMessages[] = 'Indicador Asociado'; }

        $omittedColumnsText = !empty($hiddenMessages) ? 'Nota: Se han omitido las columnas: ' . implode(', ', $hiddenMessages) . ' por falta de datos, ampliando el espacio.' : null;

        $mainRubro = 'Varios Rubros';
        if ($weekActivities->isNotEmpty()) {
            $rubros = $weekActivities->map(fn($item) => $item->activity->product->rubro->name ?? null)->filter()->unique();
            if ($rubros->count() === 1) $mainRubro = $rubros->first();
            else if ($rubros->isEmpty()) $mainRubro = 'Sin Rubro Asociado';
        }

        $groupedActivities = $weekActivities->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $reportData = [
            'iniap_logo_path' => $iniap_logo_path,
            'ecuador_shield_path' => $ecuador_shield_path,
            'technician' => $technician,
            'technician_location' => $technician->location->name ?? 'Ubicación Desconocida',
            'program_rubro' => $mainRubro,
            'presentation_date' => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
            'week_range' => 'Del ' . $startDate->translatedFormat('d \d\e F \d\e Y') . ' al ' . $endDate->translatedFormat('d \d\e F \d\e Y'),
            'weekActivities' => $groupedActivities,
            'start_date_obj' => $startDate,
            'visibility' => ['support' => $hasSupport, 'indicators' => $hasIndicators],
            'widths' => $widths,
            'omittedColumnsText' => $omittedColumnsText
        ];

        $pdf = Pdf::loadView('reports.weekly_plan', $reportData)->setPaper('a4', 'landscape');
        return $pdf->download('Plan Semanal_' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf');
    }

    // ===================================================================
    // === HISTORIAL JSON PARA REACT (Con clonación inteligente) ===
    // ===================================================================

    public function getUserWeeklyPlans(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'No autenticado.'], 401);

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status' => 'nullable|string',
        ]);

        $baseRelations = ['activity.product.rubro', 'activity.users', 'materials', 'performanceIndicators', 'logisticSupportUsers', 'user'];

        // 1. Tareas propias
        $ownQuery = WeekActivity::where('user_id', $user->id)->with($baseRelations);
        // 2. Tareas de apoyo
        $supportQuery = WeekActivity::whereHas('logisticSupportUsers', function ($q) use ($user) {
            $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
        })->with($baseRelations);

        // Filtros de fecha
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $ownQuery->whereBetween('date', [$startDate, $endDate]);
            $supportQuery->whereBetween('date', [$startDate, $endDate]);
        }

        // Filtros de estado
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statuses = explode(',', $request->input('status'));
            $ownQuery->whereIn('status', $statuses);
            $supportQuery->whereIn('status', $statuses);
        } else {
            $allVisibleStatuses = ['approved', 'rated', 'reassigned', 'in progress', 'pending', 'completed', 'partial', 'not completed'];
            $ownQuery->whereIn('status', $allVisibleStatuses);
            $supportQuery->whereIn('status', $allVisibleStatuses);
        }

        $ownActivities = $ownQuery->get()->map(function($act) {
            $act->setAttribute('is_owner_flag', true);
            return $act;
        });

        $supportActivities = $supportQuery->get()->map(function($act) use ($user) {
            $act->setAttribute('is_owner_flag', false);
            $act->setAttribute('supported_owner_name', $act->user->name ?? 'Compañero');
            return $act;
        });

        $weeklyPlans = $ownActivities->merge($supportActivities)->sortByDesc('date')->values();

        $formattedPlans = $weeklyPlans->map(function ($plan) {
            $productInitialCode = ''; $activityInitialCode = ''; $combinedCodePrefix = '';
            if ($plan->activity && $plan->activity->product && !empty($plan->activity->product->name)) $productInitialCode = strtoupper(substr($plan->activity->product->name, 0, 2));
            if ($plan->activity && !empty($plan->activity->description)) $activityInitialCode = strtoupper(substr($plan->activity->description, 0, 2));

            if (!empty($productInitialCode) && !empty($activityInitialCode)) $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
            elseif (!empty($productInitialCode)) $combinedCodePrefix = $productInitialCode . ': ';
            elseif (!empty($activityInitialCode)) $combinedCodePrefix = $activityInitialCode . ': ';

            return [
                'id' => $plan->id,
                'date' => Carbon::parse($plan->date)->isoFormat('dddd, D [de] MMMM [de]YYYY'),
                'description' => $combinedCodePrefix . ($plan->description ?? 'N/A'),
                'estimated_hours' => $plan->estimated_hours,
                'work_location' => $plan->work_location,
                'observations' => $plan->observations,
                'status' => $plan->status,
                'activity_base_id' => $plan->activity->id ?? null,
                'product_name' => $plan->activity->product->name ?? 'N/A',
                'rubro_name' => $plan->activity->product->rubro->name ?? 'N/A',
                'responsables' => $plan->activity->users->pluck('name')->implode(', ') ?? 'N/A',

                // Nuevas banderas para el frontend
                'is_owner' => $plan->is_owner_flag ?? true,
                'owner_name' => $plan->supported_owner_name ?? null,

                'materials' => $plan->materials->map(fn($material) => [
                    'name' => $material->name, 'quantity' => $material->pivot->quantity, 'description' => $material->pivot->description,
                ]),
                'indicators' => $plan->performanceIndicators->pluck('name')->implode(' - '),
                'logistic_supports' => $plan->logisticSupportUsers->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->toArray(),
            ];
        });

        return response()->json($formattedPlans);
    }

    public function getUserWeeklyPlansbyLocation(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'No autenticado.'], 401);

        $userIdsToQuery = $request->filled('id')
            ? [$request->input('id')]
            : User::where('location_id', $user->location_id)->pluck('id')->toArray();

        if (empty($userIdsToQuery)) return response()->json([]);

        $baseRelations = ['activity.product.rubro', 'activity.users', 'materials', 'performanceIndicators', 'logisticSupportUsers', 'user'];

        // 1. Propias
        $ownQuery = WeekActivity::whereIn('user_id', $userIdsToQuery)->with($baseRelations);
        // 2. Apoyo
        $supportQuery = WeekActivity::whereHas('logisticSupportUsers', function ($q) use ($userIdsToQuery) {
            $q->whereIn('users.id', $userIdsToQuery)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
        })->with($baseRelations);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $ownQuery->whereBetween('date', [$startDate, $endDate]);
            $supportQuery->whereBetween('date', [$startDate, $endDate]);
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statuses = explode(',', $request->input('status'));
            $ownQuery->whereIn('status', $statuses);
            $supportQuery->whereIn('status', $statuses);
        } else {
            $allVisibleStatuses = ['approved', 'rated', 'reassigned', 'in progress', 'pending', 'completed', 'partial', 'not completed'];
            $ownQuery->whereIn('status', $allVisibleStatuses);
            $supportQuery->whereIn('status', $allVisibleStatuses);
        }

        $ownActivities = $ownQuery->get()->map(function($act) {
            $act->setAttribute('is_owner_flag', true);
            $act->setAttribute('display_user_name', $act->user->name ?? 'N/A');
            return $act;
        });

        $supportActivities = $supportQuery->get()->flatMap(function($act) use ($userIdsToQuery) {
            $clones = collect();
            foreach ($act->logisticSupportUsers as $supportUser) {
                if (in_array($supportUser->id, $userIdsToQuery) && in_array($supportUser->pivot->status, ['accepted', 'pending'])) {
                    $clonedAct = clone $act;
                    $clonedAct->setAttribute('is_owner_flag', false);
                    $clonedAct->setAttribute('display_user_name', $supportUser->name);
                    $clonedAct->setAttribute('supported_owner_name', $act->user->name ?? 'Compañero');
                    $clones->push($clonedAct);
                }
            }
            return $clones;
        });

        $weeklyPlans = $ownActivities->merge($supportActivities)->sortByDesc('date')->values();

        $formattedPlans = $weeklyPlans->map(function ($plan) {
            $productInitialCode = ''; $activityInitialCode = ''; $combinedCodePrefix = '';
            if ($plan->activity && $plan->activity->product && !empty($plan->activity->product->name)) $productInitialCode = strtoupper(substr($plan->activity->product->name, 0, 2));
            if ($plan->activity && !empty($plan->activity->description)) $activityInitialCode = strtoupper(substr($plan->activity->description, 0, 2));
            if (!empty($productInitialCode) && !empty($activityInitialCode)) $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
            elseif (!empty($productInitialCode)) $combinedCodePrefix = $productInitialCode . ': ';
            elseif (!empty($activityInitialCode)) $combinedCodePrefix = $activityInitialCode . ': ';

            return [
                'id' => $plan->id,
                'date' => Carbon::parse($plan->date)->isoFormat('dddd, D [de] MMMM [de]YYYY'),
                'description' => $combinedCodePrefix . ($plan->description ?? 'N/A'),
                'estimated_hours' => $plan->estimated_hours,
                'work_location' => $plan->work_location,
                'observations' => $plan->observations,
                'status' => $plan->status,
                'activity_base_id' => $plan->activity->id ?? null,
                'product_name' => $plan->activity->product->name ?? 'N/A',
                'rubro_name' => $plan->activity->product->rubro->name ?? 'N/A',
                'responsables' => $plan->activity->users->pluck('name')->implode(', ') ?? 'N/A',

                // Mapeo Inteligente
                'user_name' => $plan->display_user_name ?? ($plan->user->name ?? 'N/A'),
                'is_owner' => $plan->is_owner_flag ?? true,
                'owner_name' => $plan->supported_owner_name ?? null,

                'materials' => $plan->materials->map(fn($material) => [
                    'name' => $material->name, 'quantity' => $material->pivot->quantity, 'description' => $material->pivot->description,
                ]),
                'indicators' => $plan->performanceIndicators->pluck('name')->implode(' - '),
                'logistic_supports' => $plan->logisticSupportUsers->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->toArray(),
            ];
        });

        return response()->json($formattedPlans);
    }

    public function generateTeamPulseReport(Request $request)
    {
        Carbon::setLocale('es');
        $manager = $request->user();
        $manager->load('groups.members');

        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate = Carbon::now()->subWeek()->endOfWeek();

        $teamMemberIds = $manager->groups->flatMap(fn($group) => $group->members->pluck('id'))->unique();
        $teamMembers = User::whereIn('id', $teamMemberIds)->get();

        $pulses = WeeklyPulse::whereIn('user_id', $teamMemberIds)->where('week_start_date', $startDate->toDateString())->get()->keyBy('user_id');

        $teamPulseData = $teamMembers->map(function ($member) use ($pulses) {
            $pulse = $pulses->get($member->id);
            return [
                'name' => $member->name,
                'status' => $pulse->status ?? 'gray',
                'comment' => $pulse->comment ?? null,
            ];
        });

        $counts = $teamPulseData->countBy('status');
        $total = $teamMembers->count() > 0 ? $teamMembers->count() : 1;
        $summary = [
            'total' => $teamMembers->count(),
            'counts' => [
                'green' => $counts->get('green', 0), 'yellow' => $counts->get('yellow', 0), 'red' => $counts->get('red', 0), 'gray' => $counts->get('gray', 0),
            ],
            'percentages' => [
                'green' => round(($counts->get('green', 0) / $total) * 100), 'yellow' => round(($counts->get('yellow', 0) / $total) * 100),
                'red' => round(($counts->get('red', 0) / $total) * 100), 'gray' => round(($counts->get('gray', 0) / $total) * 100),
            ]
        ];

        $data = [
            'iniap_logo_path' => public_path('storage/images/iniap_logo.png'),
            'teamName' => $manager->groups->first()->name ?? 'Equipo',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamPulseData' => $teamPulseData,
            'summary' => $summary,
        ];

        return Pdf::loadView('reports.team_pulse_report', $data)->download('informe-pulso-semanal-' . $startDate->format('Y-m-d') . '.pdf');
    }

    public function generateWeeklyMonitoringReport(Request $request)
    {
        Carbon::setLocale('es');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $technician = User::with('location')->find($userId);
        if (!$technician) return response()->json(['error' => 'Técnico no encontrado.'], 404);

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

                // INYECCIÓN VISUAL PARA EL PDF
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

    public function generateUserDeepDivePdf(Request $request, User $user)
    {
        $validated = $request->validate(['start_date' => 'required|date_format:Y-m-d', 'end_date' => 'required|date_format:Y-m-d']);
        $startDate = $validated['start_date']; $endDate = $validated['end_date'];

        $allActivities = WeekActivity::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('logisticSupportUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                });
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->orderBy('date', 'desc')
            ->get();

        $groupedActivities = $allActivities->groupBy(fn($activity) => Carbon::parse($activity->date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'));

        $data = [
            'reportDate' => Carbon::now()->locale('es')->isoFormat('LL'),
            'user' => $user, 'startDate' => Carbon::parse($startDate)->locale('es')->isoFormat('LL'), 'endDate' => Carbon::parse($endDate)->locale('es')->isoFormat('LL'),
            'performanceStats' => $this->getPerformanceStatsForUser($user, $startDate, $endDate),
            'weeklyLoadChart' => $this->getWeeklyLoadForUser($user, $startDate, $endDate),
            'pulseHistory' => $this->getPulseHistoryForUser($user, $startDate, $endDate),
            'collaborationStats' => $this->getCollaborationStatsForUser($user, $startDate, $endDate),
            'groupedActivities' => $groupedActivities,
        ];

        return Pdf::loadView('reports.user_deep_dive_report', $data)->download('informe-detallado-' . $user->name . '.pdf');
    }

    // ===================================================================
    // === MÉTODOS PRIVADOS AUXILIARES PARA RECOPILAR LOS DATOS ===
    // ===================================================================

    private function getPerformanceStatsForUser(User $user, $startDate, $endDate)
    {
        // AHORA SÍ TOMA EN CUENTA EL APOYO PARA SUS KPIS PERSONALES
        return WeekActivity::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('logisticSupportUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                });
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->select(
                DB::raw("COUNT(*) as total_activities"),
                DB::raw("COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed"),
                DB::raw("COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as partial"),
                DB::raw("COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_completed"),
                DB::raw("AVG(percentage) as average_compliance")
            )->first()->toArray();
    }

    private function getProductBreakdownForUser(User $user, $startDate, $endDate)
    {
        return Product::whereHas('activities.users', fn($q) => $q->where('users.id', $user->id))
            ->with(['activities' => function ($query) use ($user, $startDate, $endDate) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id))
                    ->with(['weeklyActivities' => function($q) use ($user, $startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate])
                            ->where(function($q2) use ($user) {
                                $q2->where('user_id', $user->id)->orWhereHas('logisticSupportUsers', function ($q3) use ($user) {
                                    $q3->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                                });
                            });
                    }]);
            }])->get();
    }

    private function getWeeklyLoadForUser(User $user, $startDate, $endDate)
    {
        $weeks = WeekActivity::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('logisticSupportUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                });
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_TRUNC('week', date) AS week_start"),
                DB::raw("COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed"),
                DB::raw("COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as partial"),
                DB::raw("COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_completed")
            )
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get();

        return $weeks->map(fn($week) => [
            'week' => 'Sem. ' . Carbon::parse($week->week_start)->format('W'),
            'completed' => (int) $week->completed,
            'partial' => (int) $week->partial,
            'not_completed' => (int) $week->not_completed,
        ]);
    }

    private function getPulseHistoryForUser(User $user, $startDate, $endDate)
    {
        return WeeklyPulse::where('user_id', $user->id)
            ->whereBetween('week_start_date', [Carbon::parse($startDate)->startOfWeek(), Carbon::parse($endDate)->endOfWeek()])
            ->orderBy('week_start_date', 'desc')
            ->select('week_start_date', 'status', 'comment')
            ->get();
    }

    private function getCollaborationStatsForUser(User $user, $startDate, $endDate)
    {
        $activities = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('logisticSupportUsers')->get();

        $supportGiven = DB::table('week_activity_logistic_support_user')
            ->join('weekly_activities', 'weekly_activities.id', '=', 'week_activity_logistic_support_user.weekly_activity_id')
            ->join('users', 'users.id', '=', 'weekly_activities.user_id')
            ->where('week_activity_logistic_support_user.user_id', $user->id)
            ->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending'])
            ->whereBetween('weekly_activities.date', [$startDate, $endDate])
            ->select('users.name')
            ->get()->countBy('name');

        return [
            'support_requested' => $activities->flatMap->logisticSupportUsers->countBy('name'),
            'support_given' => $supportGiven
        ];
    }

    public function getUserDeepDiveData(Request $request, User $user)
    {
        $validated = $request->validate(['start_date' => 'required|date_format:Y-m-d', 'end_date' => 'required|date_format:Y-m-d']);
        $startDate = $validated['start_date']; $endDate = $validated['end_date'];

        $allActivities = WeekActivity::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('logisticSupportUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                });
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->orderBy('date', 'desc')
            ->get();

        $data = [
            'performanceStats' => $this->getPerformanceStatsForUser($user, $startDate, $endDate),
            'productBreakdown' => $this->getProductBreakdownForUser($user, $startDate, $endDate),
            'weeklyLoadChart' => $this->getWeeklyLoadForUser($user, $startDate, $endDate),
            'pulseHistory' => $this->getPulseHistoryForUser($user, $startDate, $endDate),
            'collaborationStats' => $this->getCollaborationStatsForUser($user, $startDate, $endDate),
            'allActivities' => $allActivities,
        ];

        return response()->json(['data' => $data]);
    }

    public function generateRubroDeepDivePdf(Request $request, Rubro $rubro)
    {
        $rubro->load([
            'groups' => function ($query) { $query->where('location_id', Auth::user()->location_id); },
            'products' => function ($query) {
                $query->where('location_id', Auth::user()->location_id)
                    ->with(['activities.weeklyActivities' => function ($q) { $q->with('user:id,name')->orderBy('date', 'desc'); }]);
            }
        ]);

        $reportData = [
            'rubro' => [
                'name' => $rubro->name,
                'total_budget' => $rubro->products->sum('budget'),
                'groups' => $rubro->groups->toArray(),
                'products' => $rubro->products->toArray(),
            ]
        ];

        return Pdf::loadView('reports.rubro_deep_dive_report', $reportData)->download('informe_detallado_' . Str::slug($rubro->name) . '.pdf');
    }

    public function generateNationalExecutiveSummary(Request $request)
    {
        $locations = Location::all();
        $allProducts = Product::with(['activities.monthlyExecutionProgress'])->get();
        $allUsers = User::whereHas('roles', fn($q) => $q->where('name', 'researcher'))->get();
        $officialRubroId = Rubro::where('name', 'OFICIAL')->value('id');

        $productsByLocation = $allProducts->groupBy('location_id');
        $usersByLocation = $allUsers->groupBy('location_id');

        $detailedStationData = $locations->map(function ($location) use ($productsByLocation, $usersByLocation, $officialRubroId) {
            $stationId = $location->id;
            $locationProducts = $productsByLocation->get($stationId) ?? collect();
            $locationUsers = $usersByLocation->get($stationId) ?? collect();

            $poaProducts = $locationProducts->where('rubro_id', '!=', $officialRubroId);
            $progress = $this->calculateTotalProgress($poaProducts);
            $totalBudget = $locationProducts->sum('budget');
            $recentDate = Carbon::now()->subDays(30);

            $activeProjectsCount = Product::where('location_id', $stationId)
                ->whereHas('activities.weeklyActivities', fn($q) => $q->where('date', '>=', $recentDate))->count();

            $fourWeeksAgo = Carbon::now()->subWeeks(4)->startOfWeek();
            $recentProgress = WeekActivity::whereIn('user_id', $locationUsers->pluck('id'))->where('date', '>=', $fourWeeksAgo)->avg('percentage');

            return [
                'name' => $location->name, 'poa_progress' => round($progress * 100, 2), 'total_budget' => $totalBudget,
                'project_count' => $locationProducts->count(), 'active_projects_count' => $activeProjectsCount,
                'researcher_count' => $locationUsers->count(), 'monthly_progress_estimate' => round($recentProgress, 2) ?: 0,
                'researchers' => $locationUsers->pluck('name')->toArray(),
            ];
        });

        $kpis = [
            'poa_progress' => round($detailedStationData->avg('poa_progress'), 2), 'total_budget' => $detailedStationData->sum('total_budget'),
            'total_projects' => $detailedStationData->sum('project_count'), 'total_researchers' => $detailedStationData->sum('researcher_count'),
            'active_stations' => $locations->count(),
        ];

        return Pdf::loadView('reports.national_executive_summary', ['kpis' => $kpis, 'stationData' => $detailedStationData->sortByDesc('poa_progress')->values()])
            ->setPaper('a4', 'portrait')->download('informe_situacion_nacional.pdf');
    }

    public function generateStationComparisonReport(Request $request)
    {
        $dashboardController = new NationalDashboardController();
        $performanceData = collect($dashboardController->getStationPerformance($request)->getData()->data);
        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();

        $enrichedData = $performanceData->map(function ($stationData) use ($lastWeekStartDate) {
            $stationId = $stationData->location_id;
            $stationData->total_budget = Product::where('location_id', $stationId)->sum('budget');

            $memberIds = User::where('location_id', $stationId)->pluck('id');
            $pulses = WeeklyPulse::whereIn('user_id', $memberIds)->where('week_start_date', $lastWeekStartDate->toDateString())->get();

            if ($pulses->isEmpty() || $memberIds->isEmpty()) {
                $stationData->average_pulse_score = 0;
            } else {
                $pulseScoreMap = ['green' => 3, 'yellow' => 2, 'red' => 1];
                $totalScore = $pulses->reduce(fn($sum, $pulse) => $sum + ($pulseScoreMap[$pulse->status] ?? 0), 0);
                $stationData->average_pulse_score = $totalScore / $memberIds->count();
            }

            return (array) $stationData;
        });

        $sortedData = $enrichedData->sortByDesc('poa_progress')->values();

        $dataForView = [
            'performanceData' => $sortedData, 'topPerformer' => $sortedData->first(),
            'lowPerformer' => $sortedData->last(), 'pulseAlert' => $sortedData->where('average_pulse_score', '>', 0)->sortBy('average_pulse_score')->first(),
        ];

        return Pdf::loadView('reports.station_comparison_report', $dataForView)->download('reporte_comparativo_estaciones.pdf');
    }

    public function exportPdf(Request $request, Survey $survey)
    {
        $surveyController = new SurveyController();
        $resultsResponse = $surveyController->results($request, $survey);
        $data = json_decode($resultsResponse->getContent(), true);

        return Pdf::loadView('reports.survey_summary', ['data' => $data])->setPaper('a4', 'landscape')->download('resumen-' . \Str::slug($survey->title) . '.pdf');
    }

    public function exportExcel(Request $request, Survey $survey)
    {
        $fileName = 'respuestas-detalladas-' . \Str::slug($survey->title) . '.xlsx';
        $results = DB::table('responses')
            ->join('answers', 'responses.id', '=', 'answers.response_id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->leftJoin('users', 'responses.user_id', '=', 'users.id')
            ->where('responses.survey_id', $survey->id)
            ->select('responses.id as response_id', 'responses.created_at as date', 'users.name as user_name', 'users.email as user_email', 'questions.text as question_text', 'questions.type as question_type', 'answers.value as answer_value')
            ->orderBy('responses.id')->cursor();

        $userMap = []; $participantCounter = 1;

        return response()->streamDownload(function () use ($results, &$userMap, &$participantCounter) {
            $writer = SimpleExcelWriter::streamDownload('php://output', 'xlsx');
            $writer->addHeader(['ID Participante (Anónimo)', 'Fecha', 'Nombre Participante', 'Email Participante', 'Pregunta', 'Respuesta']);

            foreach ($results as $row) {
                if (!isset($userMap[$row->user_email])) $userMap[$row->user_email] = 'Participante ' . $participantCounter++;
                $participantId = $userMap[$row->user_email];

                $formattedValue = $row->answer_value;
                if ($row->question_type == 'checkbox') $formattedValue = implode(', ', json_decode($row->answer_value) ?? []);
                elseif ($row->question_type == 'boolean') $formattedValue = $row->answer_value == 1 ? 'Sí' : 'No';

                $writer->addRow([$participantId, $row->date, $row->user_name, $row->user_email, $row->question_text, $formattedValue]);
            }
        }, $fileName);
    }
}
