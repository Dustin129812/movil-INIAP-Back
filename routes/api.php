<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\GlobalAlertController;
use App\Http\Controllers\Admin\RoleController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('fiasa/login', [AuthController::class, 'fiasaLogin']);

Route::apiResource('incidents', IncidentController::class);
Route::post('/import-fiasa-users', [ImportController::class, 'importFiasaFile']);

Route::middleware(['auth:api', 'role:administrador'])->prefix('admin')->group(function () {

    // Gestión de Usuarios y Roles
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::post('/import-users', [ImportController::class, 'importUserFile']);

    // Gestión de Roles (CRUD)
    Route::apiResource('roles', RoleController::class)->except(['show']);

    // Gestión de Funcionalidades (Feature Flags)
    Route::put('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update']);

    Route::get('/conversations', [ConversationController::class, 'index']);

    Route::apiResource('patch-notes', PatchNoteController::class);
});

Route::middleware('auth:api', 'throttle:60,1')->group(callback: function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read/{notificationId}', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/mark-as-read-batch', [NotificationController::class, 'markAsReadBatch']);
    Route::post('/notifications/mark-as-unread-batch', [NotificationController::class, 'markAsUnreadBatch']); // Si necesitas desmarcar
    Route::delete('/notifications/batch', [NotificationController::class, 'destroyBatch']); // Para eliminar o archivar

    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user/roles', [AuthController::class, 'getUserRoles']);

    Route::post('users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::get('getUsers', [UserController::class, 'getUsers']);
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::put('profile/update-password', [UserController::class, 'updatePassword']);
    Route::get('getUsersbyLocation',[UserController::class, 'getUsersbyLocation']);

    Route::post('addProductAndActivity', [PlannerController::class, 'addProductAndActivity']);
    Route::put('week-activities/{activityId}/approve', [PlannerController::class, 'approveActivity']);
    Route::get('getWeeklyPlanningByResponsible', [PlannerController::class, 'getWeeklyPlanningByResponsible']);
    Route::get('getProductsWithActivities', [PlannerController::class, 'getProductsWithActivities']);
    Route::get('getProductsWithActivitiesExtraPoa', [PlannerController::class, 'getProductsWithActivitiesExtraPoa']);
    Route::get('/plannable-products', [PlannerController::class, 'getPlannableProductsForCurrentUser']);
    Route::put('/products/{id}', [PlannerController::class, 'updateProductAndActivity']);

    Route::resource('materials', MaterialController::class);
    Route::apiResource('rubros', RubroController::class);
    Route::apiResource('indicators', IndicatorController::class);

    Route::get('user-associated-counts', [PlannerController::class, 'getUserAssociatedCounts']);

    Route::get('getProducts', [GeneralController::class, 'getProducts']);
    Route::get('products-with-activities', [GeneralController::class, 'getProductsWithActivities']);
    Route::get('getUniqueLocations', [PlannerController::class, 'getUniqueLocations']);
    Route::get('products-by-location/{locationId}', [PlannerController::class, 'getProductsByLocationId']);
    Route::get('activities', [GeneralController::class, 'getActivitiesByProduct'] );
    Route::get('activities/{productId}', [GeneralController::class, 'getActivitiesByProduct']);
    Route::get('getProductsByLocation',[GeneralController::class,'getProductsByLocation']);
    Route::get('getRubrosByLocation',[GeneralController::class,'getRubrosByLocation']);

    Route::get('chart-data', [ChartController::class, 'getChartData']);

    Route::post('weeklyPlanner', [WeekActivityController::class, 'weeklyPlanner']);
    Route::get('week-activities/previous', [WeekActivityController::class, 'getPreviousWeekActivities']);
    Route::put('week-activities/progress', [WeekActivityController::class, 'updateWeeklyProgress']);
    Route::get('activities/progress', [WeekActivityController::class, 'getActivitiesWithProgress']);
    Route::get('/previous-week-activities', [WeekActivityController::class, 'getPreviousWeekActivitiesForReview']);

    Route::post('addLogistic', [GeneralController::class, 'addLogisticSupport']);

    Route::get('getLocations', [GeneralController::class, 'getLocations']);
    Route::get('getNationality', [GeneralController::class, 'getNationality']);
    Route::get('getEthnics', [GeneralController::class, 'getEthnics']);
    Route::get('getPositions', [GeneralController::class, 'getPositions']);
    Route::get('getIndicators', [GeneralController::class, 'getIndicators']);
    Route::get('getLogistic', [GeneralController::class, 'getLogistic']);
    Route::get('adminMaterials',[ChartController::class,'adminMaterials']);

    Route::get('weekly-plan-report', [ReportController::class, 'generateWeeklyPlanReport']);
    Route::get('user-weekly-plans', [ReportController::class, 'getUserWeeklyPlans']);
    Route::get('getUserWeeklyPlansbyLocation',[ReportController::class, 'getUserWeeklyPlansbyLocation']);
    Route::get('generateWeeklyMonitoringReport',[ReportController::class, 'generateWeeklyMonitoringReport']);

    Route::get('/wordpress-posts', [WordPressController::class, 'getPosts']);

    Route::get('/feature-flags', [FeatureFlagController::class, 'index']);

    Route::get('/unified-poa-by-station', [PlannerController::class, 'getUnifiedPoaData']);

    Route::post('/conversations', [ConversationController::class, 'create']);
    Route::get('/conversations/{id}/messages', [MessageController::class, 'list']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);

    //--GESTION DE GRUPOS--
    Route::put('/groups/{group}/members', [GroupController::class, 'syncMembers'])->name('groups.syncMembers');
    Route::put('/groups/{group}/responsible', [GroupController::class, 'changeResponsible'])->name('groups.changeResponsible');
    Route::apiResource('groups', GroupController::class);

    Route::post('/weekly-pulse', [PulseController::class, 'store']);

    Route::get('/dashboard/researcher', [DashboardController::class, 'getResearcherDashboardData']);
    Route::get('/dashboard/product-manager', [DashboardController::class, 'getProductManagerDashboardData']);

    Route::get('team-pulse-report', [ReportController::class, 'generateTeamPulseReport']);

    Route::get('/patch-notes/latest', [PatchNoteController::class, 'getLatest']);

    Route::get('/station-needs/create', [StationNeedController::class, 'create']);

    Route::apiResource('station-needs', StationNeedController::class);

    Route::get('/expense-types', [ExpenseTypeController::class, 'index']);
    Route::get('/expense-types/search', [ExpenseTypeController::class, 'search']);

    Route::get('/monthly-report/activities', [PlannerController::class, 'getActivitiesForMonthlyReport']);
    Route::post('/store-monthly-execution', [PlannerController::class, 'storeMonthlyExecution']);

    Route::apiResource('reusable-activities', ReusableActivityController::class);
});
