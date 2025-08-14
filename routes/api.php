<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\WeekActivityController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\WordPressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:api', 'role:administrador'])->prefix('admin')->group(function () {

    // Gestión de Usuarios y Roles
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::post('/import-users', [ImportController::class, 'importUserFile']);

    // Gestión de Roles (CRUD)
    Route::apiResource('roles', RoleController::class)->except(['show']);

    // Gestión de Tickets de Soporte (CRUD)
    Route::apiResource('incidents', IncidentController::class);

    // Gestión de Funcionalidades (Feature Flags)
    Route::put('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update']);

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

    Route::post('addProductAndActivity', [PlannerController::class, 'addProductAndActivity']);
    Route::put('week-activities/{activityId}/approve', [PlannerController::class, 'approveActivity']);
    Route::get('getWeeklyPlanningByResponsible', [PlannerController::class, 'getWeeklyPlanningByResponsible']);
    Route::get('getProductsWithActivities', [PlannerController::class, 'getProductsWithActivities']);
    Route::get('getProductsWithActivitiesExtraPoa', [PlannerController::class, 'getProductsWithActivitiesExtraPoa']);


    Route::get('materials', [PlannerController::class, 'getMaterial']);
    Route::put('/products/{id}', [PlannerController::class, 'updateProductAndActivity']);

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

    Route::post('addRubro', [GeneralController::class, 'addRubro']);
    Route::post('addIndicator', [GeneralController::class, 'addIndicator']);
    Route::post('addLogistic', [GeneralController::class, 'addLogisticSupport']);
    Route::get('getLocations', [GeneralController::class, 'getLocations']);
    Route::get('getNationality', [GeneralController::class, 'getNationality']);
    Route::get('getEthnics', [GeneralController::class, 'getEthnics']);
    Route::get('getPositions', [GeneralController::class, 'getPositions']);
    Route::get('getRubros', [GeneralController::class, 'getRubros']);
    Route::get('getIndicators', [GeneralController::class, 'getIndicators']);
    Route::get('getLogistic', [GeneralController::class, 'getLogistic']);
    Route::get('adminMaterials',[ChartController::class,'adminMaterials']);

    Route::get('weekly-plan-report', [ReportController::class, 'generateWeeklyPlanReport']);
    Route::get('user-weekly-plans', [ReportController::class, 'getUserWeeklyPlans']);
    Route::get('getUserWeeklyPlansbyLocation',[ReportController::class, 'getUserWeeklyPlansbyLocation']);
    Route::get('getUsersbyLocation',[UserController::class, 'getUsersbyLocation']);
    Route::get('getUserWeeklyPlansbyLocation',[ReportController::class, 'getUserWeeklyPlansbyLocation']);

    Route::get('/wordpress-posts', [WordPressController::class, 'getPosts']);

    Route::get('/feature-flags', [FeatureFlagController::class, 'index']);
});
