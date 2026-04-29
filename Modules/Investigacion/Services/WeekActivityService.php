<?php

namespace Modules\Investigacion\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\Material;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeekPlanner;
use Modules\Investigacion\Notifications\OursWeekPlanner;
use Modules\Investigacion\Notifications\RateWeeklyActivityNo;

class WeekActivityService
{
    /**
     * Guarda la planificación semanal (futura o pasada).
     */
    public function saveWeeklyPlan(array $weeklyPlansData, User $user, ?Carbon $baseMonday = null): array
    {
        if (empty($weeklyPlansData)) {
            throw new \Exception("Formato de datos de planificación semanal inválido o vacío.");
        }

        if (!$baseMonday) {
            $today = Carbon::now();
            $baseMonday = ($today->dayOfWeek >= Carbon::FRIDAY || $today->dayOfWeek == Carbon::SUNDAY)
                ? $today->copy()->addWeek()->startOfWeek(Carbon::MONDAY)
                : $today->copy()->startOfWeek(Carbon::MONDAY);
        }

        $entries = [];
        $product = null;

        return DB::transaction(function () use ($weeklyPlansData, $user, $baseMonday, &$entries, &$product) {
            $dayOffsets = [
                'lunes' => 0, 'martes' => 1, 'miercoles' => 2,
                'jueves' => 3, 'viernes' => 4, 'sábado' => 5, 'domingo' => 6,
            ];

            foreach ($weeklyPlansData as $data) {
                $activity = Activity::findOrFail($data['activityId']);

                if (!$product) {
                    $product = $activity->product;
                }

                $activityDate = $baseMonday->copy()->addDays($dayOffsets[$data['day']] ?? 0);

                $weekActivity = new WeekActivity();
                $weekActivity->description = $data['description'];
                $weekActivity->date = $activityDate;
                $weekActivity->status = 'pending';
                $weekActivity->work_location = $activity->work_location ?? 'Oficina';
                $weekActivity->observations = $data['observations'] ?? null;
                $weekActivity->percentage = 0;
                $weekActivity->activity_id = $activity->id;
                $weekActivity->user_id = $user->id;
                $weekActivity->activity_type = $data['activity_type'];
                $weekActivity->save();

                $entries[] = $weekActivity;

                $this->syncMaterials($weekActivity, $data['materials'] ?? []);

                $this->syncIndicators($weekActivity, $data['indicators'] ?? []);

                $this->syncLogisticSupport($weekActivity, $data['logisticSupports'] ?? []);

                $planner = new WeekPlanner();
                $planner->product()->associate($activity->product);
                $planner->weekActivity()->associate($weekActivity);
                $planner->save();
            }

            if ($product) {
                $productManager = User::find($product->user_id);
                if ($productManager) {
                    $productManager->notify(new OursWeekPlanner($entries, $user));
                }
            }

            return $entries;
        });
    }

    /**
     * Obtiene las actividades para la vista del usuario (propias y apoyos).
     */
    public function getUserActivities(User $user, ?int $offset = null)
    {
        $query = WeekActivity::with([
            'user',
            'activity.product',
            'activity.monthlyProgress',
            'activity.weeklyActivities',
            'logisticSupportUsers'
        ]);

        if ($offset !== null) {
            $targetDate = Carbon::now()->addWeeks($offset);
            $startOfWeek = $targetDate->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $targetDate->copy()->endOfWeek(Carbon::SUNDAY);

            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('logisticSupportUsers', function ($q2) use ($user) {
                        $q2->where('users.id', $user->id)
                            ->where('week_activity_logistic_support_user.status', '!=', 'rejected');
                    });
            })->whereBetween('date', [$startOfWeek, $endOfWeek]);
        } else {
            $targetSunday = Carbon::now()->endOfWeek(Carbon::SUNDAY);
            $ratedStatuses = ['not completed', 'completed', 'partial', 'rated', 'rejected'];

            $query->where(function ($q) use ($user, $ratedStatuses) {
                $q->where(function ($q1) use ($user, $ratedStatuses) {
                    $q1->where('user_id', $user->id)
                        ->whereNotIn('status', $ratedStatuses);
                })
                    ->orWhereHas('logisticSupportUsers', function ($q2) use ($user) {
                        $q2->where('users.id', $user->id)
                            ->where('week_activity_logistic_support_user.status', 'pending');
                    });
            })->where('date', '<=', $targetSunday);
        }

        return $query->orderBy('date', 'asc')->get();
    }

    /**
     * Actualiza el progreso de las actividades.
     */
    public function updateProgress(array $progressData, User $investigador): void
    {
        DB::transaction(function () use ($progressData, $investigador) {
            foreach ($progressData as $progressItem) {
                $weekActivity = WeekActivity::with(['activity.product.user'])
                    ->findOrFail($progressItem['week_activity_id']);

                $oldStatus = $weekActivity->status;
                $updatedStatus = $progressItem['status'];
                $observations = $progressItem['observations'] ?? null;

                $dbStatus = match ($updatedStatus) {
                    'yes' => 'completed',
                    'no' => 'not completed',
                    'partial' => 'partial',
                    default => 'pending',
                };

                $numericPercentage = match ($updatedStatus) {
                    'yes' => 100,
                    'no' => 0,
                    'partial' => 50,
                    default => 0,
                };

                $weekActivity->update([
                    'observations' => $observations,
                    'status' => $dbStatus,
                    'percentage' => $numericPercentage
                ]);

                $wasAlreadyNegative = in_array($oldStatus, ['not completed', 'partial']);

                if ($numericPercentage >= 0 && $numericPercentage < 100 && !$wasAlreadyNegative) {
                    $responsable = $weekActivity->activity?->product?->user;
                    if ($responsable && $responsable->id !== $investigador->id) {
                        $responsable->notify(
                            new RateWeeklyActivityNo($weekActivity->activity, $investigador, $numericPercentage, $observations)
                        );
                    }
                }
            }
        });
    }

    /**
     * Actualiza o reprograma una actividad semanal existente.
     */
    public function updateActivity(int $id, array $data, User $user): WeekActivity
    {
        return DB::transaction(function () use ($id, $data, $user) {
            $weekActivity = WeekActivity::where('user_id', $user->id)->findOrFail($id);

            if (!in_array($weekActivity->status, ['pending', 'approved'])) {
                throw new \Exception("Solo se pueden modificar o reprogramar actividades en estado pendiente o aprobado.");
            }

            if (isset($data['day'])) {
                $baseMonday = Carbon::parse($weekActivity->date)->startOfWeek(Carbon::MONDAY);
                $dayOffsets = [
                    'lunes' => 0, 'martes' => 1, 'miercoles' => 2,
                    'jueves' => 3, 'viernes' => 4, 'sábado' => 5, 'domingo' => 6,
                ];
                $newDate = $baseMonday->copy()->addDays($dayOffsets[$data['day']] ?? 0);

                if (Carbon::parse($weekActivity->date)->format('Y-m-d') !== $newDate->format('Y-m-d')) {
                    $weekActivity->date = $newDate;
                    $weekActivity->is_rescheduled = true;
                }
            }

            if ($weekActivity->status === 'pending') {
                if (isset($data['description'])) $weekActivity->description = $data['description'];
                if (isset($data['work_location'])) $weekActivity->work_location = $data['work_location'];
                if (array_key_exists('observations', $data)) $weekActivity->observations = $data['observations'];
                if (isset($data['activity_type'])) $weekActivity->activity_type = $data['activity_type'];

                if (isset($data['activityId']) && $data['activityId'] != $weekActivity->activity_id) {
                    $newActivity = Activity::findOrFail($data['activityId']);
                    $weekActivity->activity_id = $newActivity->id;

                    $planner = WeekPlanner::where('week_activity_id', $weekActivity->id)->first();
                    if ($planner) {
                        $planner->product_id = $newActivity->product_id;
                        $planner->save();
                    }
                }

                if (isset($data['materials'])) {
                    $this->syncMaterials($weekActivity, $data['materials']);
                }
                if (isset($data['indicators'])) {
                    $this->syncIndicators($weekActivity, $data['indicators']);
                }
                if (isset($data['logisticSupports'])) {
                    $this->syncLogisticSupport($weekActivity, $data['logisticSupports']);
                }
            }

            $weekActivity->save();

            $weekActivity->load([
                'user',
                'activity.product',
                'logisticSupportUsers',
                'activity.monthlyProgress',
                'activity.weeklyActivities'
            ]);

            return $weekActivity;
        });
    }

    /**
     * Responde a una solicitud de apoyo.
     */
    public function respondToSupportRequest(int $activityId, User $user, string $status): void
    {
        $activity = WeekActivity::findOrFail($activityId);
        $activity->logisticSupportUsers()->updateExistingPivot($user->id, [
            'status' => $status,
            'updated_at' => now()
        ]);
    }

    // --- Métodos Privados Auxiliares para limpieza ---

    private function syncMaterials(WeekActivity $weekActivity, array $materialsData): void
    {
        if (empty($materialsData)) {
            $weekActivity->materials()->detach();
            return;
        }

        $materialNames = array_column($materialsData, 'name');
        $materialsFromDb = Material::whereIn('name', $materialNames)->get()->keyBy('name');

        $syncData = [];
        foreach ($materialsData as $materialInput) {
            $name = $materialInput['name'] ?? null;

            if ($name && $materialsFromDb->has($name)) {
                $materialId = $materialsFromDb->get($name)->id;

                $quantity = !empty($materialInput['quantity']) && is_numeric($materialInput['quantity'])
                    ? (int) $materialInput['quantity']
                    : 1;

                $description = !empty($materialInput['description'])
                    ? (string) $materialInput['description']
                    : '';

                $syncData[$materialId] = [
                    'quantity' => $quantity,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        $weekActivity->materials()->sync($syncData);
    }

    private function syncIndicators(WeekActivity $weekActivity, array $selectedIndicators): void
    {
        if (empty($selectedIndicators)) {
            $weekActivity->performanceIndicators()->detach();
            return;
        }

        $syncIndicators = [];
        foreach ($selectedIndicators as $indicatorId) {
            $syncIndicators[$indicatorId] = ['created_at' => now(), 'updated_at' => now()];
        }
        $weekActivity->performanceIndicators()->sync($syncIndicators);
    }

    private function syncLogisticSupport(WeekActivity $weekActivity, array $supportUserIds): void
    {
        $validUserIds = array_filter($supportUserIds);
        if (empty($validUserIds)) {
            $weekActivity->logisticSupportUsers()->detach();
            return;
        }

        $syncData = [];
        foreach ($validUserIds as $supportId) {
            $syncData[$supportId] = ['status' => 'pending'];
        }
        $weekActivity->logisticSupportUsers()->sync($syncData);
    }

    /**
     * Obtiene las actividades para revisión dependiendo del rol jerárquico.
     */
    public function getActivitiesForReview($period, User $reviewer)
    {
        // 1. Iniciamos los Query Builders base
        $ownActivitiesQuery = WeekActivity::with(['user', 'activity.product']);

        $supportActivitiesQuery = WeekActivity::whereHas('logisticSupportUsers', function($q) {
            $q->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
        })->with(['user', 'activity.product', 'logisticSupportUsers']);

        // 2. Aplicamos el Bypass Jerárquico según el rol
        if ($reviewer->hasRole('station-director')) {
            // El Director ve a los Product Managers de su misma localidad (estación)
            $targetUserIds = User::whereHas('roles', function ($q) {
                $q->where('name', 'product-manager');
            })
                ->where('location_id', $reviewer->location_id) // Usamos location_id como vimos en tu GroupService
                ->pluck('id');

            // Filtramos las actividades donde el dueño o el apoyo sea uno de esos Product Managers
            $ownActivitiesQuery->whereIn('user_id', $targetUserIds);

            $supportActivitiesQuery->whereHas('logisticSupportUsers', function($q) use ($targetUserIds) {
                $q->whereIn('users.id', $targetUserIds);
            });

        } elseif ($reviewer->hasRole('product-manager')) {
            // El PM ve las actividades atadas a los productos que él administra
            $ownActivitiesQuery->whereHas('activity.product', function ($q) use ($reviewer) {
                $q->where('user_id', $reviewer->id);
            });

            // Para los apoyos, vemos si apoyan en una actividad de un producto del PM
            $supportActivitiesQuery->whereHas('activity.product', function ($q) use ($reviewer) {
                $q->where('user_id', $reviewer->id);
            });
        } else {
            // Si no tiene rol de revisión, devolvemos colecciones vacías por seguridad
            return collect([]);
        }

        // 3. Ejecutamos las consultas a la DB
        $ownActivities = $ownActivitiesQuery->get()->map(function ($act) {
            $act->display_user_id = $act->user_id;
            $act->display_user_name = $act->user->name;
            $act->is_owner_flag = true;
            return $act;
        });

        $supportActivities = $supportActivitiesQuery->get()->flatMap(function ($act) use ($reviewer) {
            return $act->logisticSupportUsers->map(function ($supportUser) use ($act) {
                $clonedAct = clone $act;
                $clonedAct->display_user_id = $supportUser->id;
                $clonedAct->display_user_name = $supportUser->name;
                $clonedAct->is_owner_flag = false;
                $clonedAct->supported_owner_name = $act->user->name;

                return $clonedAct;
            });
        });
        if ($reviewer->hasRole('station-director')) {
            $supportActivities = $supportActivities->whereIn('display_user_id', $targetUserIds);
        }

        return $ownActivities->merge($supportActivities);
    }
}
