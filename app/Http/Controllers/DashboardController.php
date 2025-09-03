<?php
// Archivo: app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\WeekActivity;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Obtiene los datos para el dashboard del rol 'researcher'.
     */
    public function getResearcherDashboardData(Request $request)
    {
        $user = $request->user();

        // Widget "Mi Semana": Contar actividades planificadas para la semana actual
        $currentWeekStart = Carbon::now()->startOfWeek()->toDateString();
        $currentWeekEnd = Carbon::now()->endOfWeek()->toDateString();

        $weeklyActivitiesCount = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$currentWeekStart, $currentWeekEnd])
            ->count();

        // Widget "Mis Proyectos": Obtener los 3 proyectos más relevantes
        $myProjects = Product::where('user_id', $user->id)
            ->orWhereHas('activities.users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with('activities.weeklyActivities') // Cargar para calcular progreso
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        // Calcular el progreso de cada proyecto
        $projectsWithProgress = $myProjects->map(function ($product) {
            // Lógica simple de progreso: porcentaje de actividades con al menos un reporte
            $totalActivities = $product->activities->count();
            if ($totalActivities === 0) {
                $progress = 0;
            } else {
                $activitiesWithProgress = $product->activities->filter(function ($activity) {
                    return $activity->weeklyActivities->isNotEmpty();
                })->count();
                $progress = round(($activitiesWithProgress / $totalActivities) * 100);
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'progress' => $progress,
            ];
        });

        return response()->json([
            'myWeek' => [
                'weeklyActivitiesCount' => $weeklyActivitiesCount,
            ],
            'myProjects' => $projectsWithProgress,
        ]);
    }

    /**
     * Obtiene los datos para el dashboard del rol 'product-manager'.
     */
    public function getProductManagerDashboardData(Request $request)
    {
        $manager = $request->user();
        $manager->load('groups.members'); // Cargar grupos y sus miembros

        // Obtener los IDs de todos los miembros en los grupos que el manager lidera
        $teamMemberIds = $manager->groups->flatMap(function ($group) {
            return $group->members->pluck('id');
        })->unique();

        // Widget "Planificaciones por Revisar"
        $pendingReviews = WeekActivity::whereIn('user_id', $teamMemberIds)
            ->where('status', 'pending')
            ->with('user:id,name') // Cargar solo id y nombre del usuario
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('user_id') // Obtener solo una entrada por usuario
            ->map(fn ($activity) => [
                'id' => $activity->user->id,
                'userName' => $activity->user->name,
                'submissionDate' => Carbon::parse($activity->created_at)->toDateString(),
            ]);

        // Widget "Pulso del Equipo"
        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();
        $teamMembers = User::whereIn('id', $teamMemberIds)->with(['weeklyPulses' => function ($query) {
            $query->orderBy('week_start_date', 'desc')->limit(4); // Cargar los últimos 4 pulsos
        }])->get();

        $teamPulse = $teamMembers->map(function ($member) use ($lastWeekStartDate) {
            $latestPulse = $member->weeklyPulses->where('week_start_date', $lastWeekStartDate->toDateString())->first();
            return [
                'id' => $member->id,
                'name' => $member->name,
                'status' => $latestPulse->status ?? 'gray', // 'gray' si no hay reporte
                'trend' => $member->weeklyPulses->pluck('status')->toArray(),
                'hasComment' => !empty($latestPulse->comment),
            ];
        });

        // Widget "Métricas Clave"
        $stats = [
            'activeProjects' => Product::whereIn('user_id', $teamMemberIds)->count(),
            'pendingActivities' => $pendingReviews->count(),
            'overdueActivities' => 0, // Lógica a implementar si es necesaria
            'teamMembers' => $teamMemberIds->count(),
        ];


        return response()->json([
            'reviewQueue' => $pendingReviews,
            'teamPulse' => $teamPulse,
            'programStats' => $stats,
        ]);
    }
}
